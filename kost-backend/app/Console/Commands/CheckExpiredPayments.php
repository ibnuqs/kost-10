<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-expired 
                            {--dry-run : Show what would be updated without making changes}
                            {--notify : Send notifications to tenants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update expired payments status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $shouldNotify = $this->option('notify');

        $this->info('🔍 Checking for expired payments...');

        // Find pending payments that should be marked as expired
        $expiredPayments = Payment::where('status', 'pending')
            ->where(function ($query) {
                // Snap token expired (24 hours)
                $query->where('snap_token_created_at', '<', now()->subHours(24))
                    // OR payment is old (7 days)
                    ->orWhere('created_at', '<', now()->subDays(7));
            })
            ->get();

        if ($expiredPayments->isEmpty()) {
            $this->info('✅ No expired payments found.');

            return self::SUCCESS;
        }

        $this->info("Found {$expiredPayments->count()} expired payments:");

        $updatedCount = 0;
        $notificationsSent = 0;

        foreach ($expiredPayments as $payment) {
            $expirationReason = $this->getExpirationReason($payment);

            // Fix: Convert decimal amount to float for number_format
            $amount = (float) $payment->amount;

            $this->line("  📋 Payment #{$payment->id} (Order: {$payment->order_id})");
            $this->line('     Amount: Rp '.number_format($amount, 0, ',', '.'));
            $this->line("     Created: {$payment->created_at->format('Y-m-d H:i:s')}");
            $this->line("     Reason: {$expirationReason}");

            if (! $isDryRun) {
                try {
                    $payment->update([
                        'status' => 'expired',
                        'expired_at' => now(),
                        'failure_reason' => $expirationReason,
                        'notes' => 'Automatically expired by system check on '.now()->format('Y-m-d H:i:s'),
                    ]);

                    $updatedCount++;
                    $this->line('     ✅ Updated to expired');

                    // Send notification if requested
                    if ($shouldNotify) {
                        $notified = $this->sendExpirationNotification($payment);
                        if ($notified) {
                            $notificationsSent++;
                            $this->line('     📧 Notification sent');
                        }
                    }

                } catch (\Exception $e) {
                    $this->error('     ❌ Failed to update: '.$e->getMessage());
                    Log::error('Failed to update expired payment', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->line('     🔄 Would be marked as expired (dry-run mode)');
            }

            $this->line('');
        }

        // Summary
        if ($isDryRun) {
            $this->warn('🔄 DRY RUN MODE: No changes were made');
            $this->info("Would update {$expiredPayments->count()} payments to expired status");
        } else {
            $this->info("✅ Updated {$updatedCount} payments to expired status");

            if ($shouldNotify) {
                $this->info("📧 Sent {$notificationsSent} notifications to tenants");
            }

            // Log the operation
            Log::info('Expired payments check completed', [
                'total_found' => $expiredPayments->count(),
                'updated_count' => $updatedCount,
                'notifications_sent' => $notificationsSent,
                'execution_time' => now()->toDateTimeString(),
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * Determine why payment expired
     */
    private function getExpirationReason(Payment $payment): string
    {
        if ($payment->snap_token_created_at &&
            Carbon::parse($payment->snap_token_created_at)->lt(now()->subHours(24))) {
            return 'Snap token expired (24 hours)';
        }

        if (Carbon::parse($payment->created_at)->lt(now()->subDays(7))) {
            return 'Payment too old (7 days)';
        }

        return 'Payment expired';
    }

    /**
     * Send expiration notification to tenant
     */
    private function sendExpirationNotification(Payment $payment): bool
    {
        try {
            $tenant = $payment->tenant()->with('user')->first();

            if (! $tenant || ! $tenant->user) {
                return false;
            }

            // Create in-app notification for the tenant
            \App\Models\Notification::create([
                'user_id' => $tenant->user_id,
                'title' => 'Tagihan Kedaluwarsa',
                'message' => 'Tagihan Anda sebesar Rp '.number_format($payment->amount, 0, ',', '.').' untuk bulan '.date('F Y', strtotime($payment->payment_month.'-01')).' telah kedaluwarsa.',
                'type' => 'payment',
                'data' => json_encode([
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => $payment->amount,
                ]),
            ]);

            Log::info('Payment expiration notification sent', [
                'payment_id' => $payment->id,
                'tenant_id' => $tenant->id,
                'user_email' => $tenant->user->email,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send expiration notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
