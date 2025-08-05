<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Tenant;
use App\Services\TenantAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPaymentStatus extends Command
{
    protected $signature = 'payments:process-status 
                          {--grace-days=7 : Number of grace days before suspension}
                          {--dry-run : Run without actually updating statuses}';

    protected $description = 'Process payment statuses and update tenant access accordingly';

    private $tenantAccessService;

    public function __construct(TenantAccessService $tenantAccessService)
    {
        parent::__construct();
        $this->tenantAccessService = $tenantAccessService;
    }

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $graceDays = (int) $this->option('grace-days');

        $this->info("🔄 Processing payment statuses with {$graceDays} grace days");

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No statuses will be updated');
        }

        try {
            $result = $this->processStatuses($graceDays, $isDryRun);

            $this->info('✅ Payment status processing completed!');
            $this->table(['Action', 'Count'], [
                ['Tenants Suspended', $result['suspended']],
                ['Tenants Reactivated', $result['reactivated']],
                ['Payments Marked Overdue', $result['overdue']],
                ['Access Updates', $result['access_updates']],
                ['Errors', $result['errors']],
            ]);

            return $result['errors'] > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('❌ Fatal error: '.$e->getMessage());
            Log::error('Payment status processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    private function processStatuses(int $graceDays, bool $isDryRun = false): array
    {
        $stats = [
            'suspended' => 0,
            'reactivated' => 0,
            'overdue' => 0,
            'access_updates' => 0,
            'errors' => 0,
        ];

        // Process overdue payments
        $stats['overdue'] += $this->markOverduePayments($isDryRun);

        // Process tenant suspensions using new service
        $accessResults = $this->processTenantsWithAccessService($isDryRun);
        $stats['suspended'] += $accessResults['suspended'];
        $stats['reactivated'] += $accessResults['activated'];
        $stats['access_updates'] += $accessResults['updated'];

        return $stats;
    }

    private function processTenantsWithAccessService(bool $isDryRun = false): array
    {
        if ($isDryRun) {
            $this->info('ℹ️  Would update tenant access using TenantAccessService (dry run)');

            return ['suspended' => 0, 'activated' => 0, 'updated' => 0];
        }

        $this->info('🔄 Updating tenant access using TenantAccessService...');

        try {
            $results = $this->tenantAccessService->updateAllTenantsAccess();

            $this->info('✅ Tenant access service completed:');
            $this->line("  • Processed: {$results['processed']} tenants");
            $this->line("  • Updated: {$results['updated']} tenants");
            $this->line("  • Suspended: {$results['suspended']} tenants");
            $this->line("  • Activated: {$results['activated']} tenants");
            $this->line("  • Errors: {$results['errors']} tenants");

            return [
                'suspended' => $results['suspended'],
                'activated' => $results['activated'],
                'updated' => $results['updated'],
            ];

        } catch (\Exception $e) {
            $this->error('❌ Error in TenantAccessService: '.$e->getMessage());

            return ['suspended' => 0, 'activated' => 0, 'updated' => 0];
        }
    }

    private function markOverduePayments(bool $isDryRun = false): int
    {
        $today = now();
        $currentMonth = $today->format('Y-m');
        $gracePeriodDays = 7; // Grace period 7 hari setelah payment dibuat

        $overduePayments = Payment::where('status', 'pending')
            ->where(function ($query) use ($currentMonth, $gracePeriodDays) {
                // Scenario 1: Payment untuk bulan sebelumnya DAN sudah lewat grace period
                $query->where('payment_month', '<', $currentMonth)
                    ->where('created_at', '<', now()->subDays($gracePeriodDays));
            })
            ->orWhere(function ($query) use ($currentMonth, $gracePeriodDays) {
                // Scenario 2: Payment bulan ini tapi sudah lebih dari grace period
                $query->where('payment_month', '=', $currentMonth)
                    ->where('created_at', '<', now()->subDays($gracePeriodDays));
            })
            ->get();

        $count = 0;
        foreach ($overduePayments as $payment) {
            try {
                if (! $isDryRun) {
                    $payment->update(['status' => 'overdue']);
                }

                $this->line("📅 Payment {$payment->id} marked as overdue (month: {$payment->payment_month})");
                $count++;

            } catch (\Exception $e) {
                $this->error("❌ Error updating payment {$payment->id}: ".$e->getMessage());
            }
        }

        return $count;
    }

    private function processTenantSuspensions(int $graceDays, bool $isDryRun = false): int
    {
        $cutoffDate = now()->subDays($graceDays)->toDateString();

        // Find tenants with overdue payments beyond grace period
        $cutoffMonth = now()->subDays($graceDays)->format('Y-m');
        
        $tenantsToSuspend = Tenant::where('status', 'active')
            ->whereHas('payments', function ($query) use ($cutoffMonth) {
                $query->where('status', 'overdue')
                    ->where('payment_month', '<=', $cutoffMonth);
            })
            ->with(['user', 'room', 'payments' => function ($query) {
                $query->where('status', 'overdue')->orderBy('payment_month');
            }])
            ->get();

        $count = 0;
        foreach ($tenantsToSuspend as $tenant) {
            try {
                // Check if tenant has any current overdue payments
                $overduePayments = $tenant->payments->where('status', 'overdue');
                $oldestOverdue = $overduePayments->first();

                if ($oldestOverdue && $oldestOverdue->payment_month <= $cutoffMonth) {
                    if (! $isDryRun) {
                        $tenant->update([
                            'status' => 'suspended',
                            'suspended_at' => now(),
                            'suspension_reason' => "Overdue payment beyond {$graceDays} days grace period",
                        ]);

                        // Log the suspension
                        Log::info('Tenant suspended due to overdue payment', [
                            'tenant_id' => $tenant->id,
                            'user_name' => $tenant->user->name,
                            'room' => $tenant->room->room_number,
                            'overdue_since' => $oldestOverdue->payment_month,
                            'overdue_amount' => $overduePayments->sum('amount'),
                        ]);
                    }

                    $this->warn("⚠️  Suspended tenant: {$tenant->user->name} (Room {$tenant->room->room_number}) - Overdue since {$oldestOverdue->payment_month}");
                    $count++;
                }

            } catch (\Exception $e) {
                $this->error("❌ Error suspending tenant {$tenant->id}: ".$e->getMessage());
            }
        }

        return $count;
    }

    private function processTenantReactivations(bool $isDryRun = false): int
    {
        // Find suspended tenants with no overdue payments
        $tenantsToReactivate = Tenant::where('status', 'suspended')
            ->whereDoesntHave('payments', function ($query) {
                $query->whereIn('status', ['pending', 'overdue']);
            })
            ->with(['user', 'room'])
            ->get();

        $count = 0;
        foreach ($tenantsToReactivate as $tenant) {
            try {
                if (! $isDryRun) {
                    $tenant->update([
                        'status' => 'active',
                        'suspended_at' => null,
                        'suspension_reason' => null,
                        'reactivated_at' => now(),
                    ]);

                    // Log the reactivation
                    Log::info('Tenant reactivated - all payments current', [
                        'tenant_id' => $tenant->id,
                        'user_name' => $tenant->user->name,
                        'room' => $tenant->room->room_number,
                    ]);
                }

                $this->info("✅ Reactivated tenant: {$tenant->user->name} (Room {$tenant->room->room_number}) - All payments current");
                $count++;

            } catch (\Exception $e) {
                $this->error("❌ Error reactivating tenant {$tenant->id}: ".$e->getMessage());
            }
        }

        return $count;
    }
}
