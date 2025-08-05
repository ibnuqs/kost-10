<?php

namespace App\Services;

use App\Events\TenantAccessChanged;
use App\Models\Payment;
use App\Models\RfidCard;
use App\Models\Tenant;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TenantAccessService
{
    /**
     * Check and update tenant access based on payment status
     */
    public function updateTenantAccess(int $tenantId): array
    {
        try {
            $tenant = Tenant::with(['user', 'room', 'payments'])->findOrFail($tenantId);

            $accessInfo = $this->calculateAccessStatus($tenant);
            $previousStatus = $tenant->status;

            // Update tenant status based on payment status
            if ($accessInfo['should_suspend'] && $tenant->status === 'active') {
                $this->suspendTenant($tenant, $accessInfo['reason']);
            } elseif ($accessInfo['should_activate'] && $tenant->status === 'suspended') {
                $this->activateTenant($tenant);
            }

            // Update RFID card access
            $this->updateRfidAccess($tenant, $accessInfo['has_access']);

            // Broadcast access change event if status changed
            if ($previousStatus !== $tenant->fresh()->status) {
                event(new TenantAccessChanged($tenant, $accessInfo));
            }

            Log::info('Tenant access updated', [
                'tenant_id' => $tenantId,
                'previous_status' => $previousStatus,
                'new_status' => $tenant->fresh()->status,
                'has_access' => $accessInfo['has_access'],
                'reason' => $accessInfo['reason'],
            ]);

            return [
                'success' => true,
                'tenant_id' => $tenantId,
                'previous_status' => $previousStatus,
                'current_status' => $tenant->fresh()->status,
                'has_access' => $accessInfo['has_access'],
                'access_info' => $accessInfo,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update tenant access', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate access status based on tenant's payment history
     */
    private function calculateAccessStatus(Tenant $tenant): array
    {
        $overduePayments = $tenant->payments()
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('payment_month') // Order by payment_month instead of non-existent due_date
            ->get();

        $totalOverdue = $overduePayments->sum('amount');
        $oldestOverdue = $overduePayments->first();

        // Grace period: 7 days after due date
        $gracePeriodDays = 7;
        $hasCriticalOverdue = false;
        $calculatedDueDate = null;

        if ($oldestOverdue) {
            // Dynamically calculate due date (e.g., 10th of the payment month)
            $calculatedDueDate = Carbon::parse($oldestOverdue->payment_month . '-10');
            $cutoffDate = $calculatedDueDate->addDays($gracePeriodDays);
            $hasCriticalOverdue = now()->gt($cutoffDate);
        }

        // Determine access status
        $hasAccess = ! $hasCriticalOverdue;
        $shouldSuspend = $hasCriticalOverdue && $tenant->status === 'active';
        $shouldActivate = ! $overduePayments->count() > 0 && $tenant->status === 'suspended';

        $reason = $this->getAccessReason($overduePayments->count() > 0, $hasCriticalOverdue, $totalOverdue, $calculatedDueDate);

        return [
            'has_access' => $hasAccess,
            'should_suspend' => $shouldSuspend,
            'should_activate' => $shouldActivate,
            'overdue_count' => $overduePayments->count(),
            'overdue_amount' => $totalOverdue,
            'oldest_overdue_date' => $calculatedDueDate ? $calculatedDueDate->toDateString() : null,
            'days_overdue' => $calculatedDueDate ? $calculatedDueDate->diffInDays(now(), false) : 0,
            'grace_period_expired' => $hasCriticalOverdue,
            'reason' => $reason,
        ];
    }

    /**
     * Get human-readable reason for access status
     */
    private function getAccessReason(bool $hasOverdue, bool $hasCritical, float $totalOverdue, $oldestOverdue): string
    {
        if (! $hasOverdue) {
            return 'All payments are current - Full access granted';
        }

        if ($hasOverdue && ! $hasCritical) {
            $daysOverdue = $oldestOverdue ? Carbon::parse($oldestOverdue->due_date)->diffInDays(now()) : 0;

            return "Payment overdue by {$daysOverdue} days - Still within grace period";
        }

        if ($hasCritical) {
            $daysOverdue = Carbon::parse($oldestOverdue->due_date)->diffInDays(now());
            $formattedAmount = 'Rp '.number_format($totalOverdue, 0, ',', '.');

            return "Payment overdue by {$daysOverdue} days - Grace period expired - Total overdue: {$formattedAmount}";
        }

        return 'Unknown status';
    }

    /**
     * Suspend tenant due to overdue payments
     */
    private function suspendTenant(Tenant $tenant, string $reason): void
    {
        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        Notification::create([
            'user_id' => $tenant->user_id,
            'title' => 'Akses Ditangguhkan',
            'message' => 'Akses Anda ditangguhkan karena pembayaran terlambat. Alasan: ' . $reason,
            'type' => 'access',
        ]);

        Log::warning('Tenant suspended due to overdue payments', [
            'tenant_id' => $tenant->id,
            'user_name' => $tenant->user->name,
            'room' => $tenant->room->room_number,
            'reason' => $reason,
        ]);
    }

    /**
     * Reactivate tenant after payments are current
     */
    private function activateTenant(Tenant $tenant): void
    {
        $tenant->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
            'reactivated_at' => now(),
        ]);

        Notification::create([
            'user_id' => $tenant->user_id,
            'title' => 'Akses Dipulihkan',
            'message' => 'Akses Anda telah berhasil dipulihkan. Terima kasih telah menyelesaikan pembayaran.',
            'type' => 'access',
        ]);

        Log::info('Tenant reactivated - payments are current', [
            'tenant_id' => $tenant->id,
            'user_name' => $tenant->user->name,
            'room' => $tenant->room->room_number,
        ]);
    }

    /**
     * Update RFID card access based on tenant status
     */
    private function updateRfidAccess(Tenant $tenant, bool $hasAccess): void
    {
        $rfidCards = RfidCard::where('tenant_id', $tenant->id)->get();

        foreach ($rfidCards as $card) {
            $previousStatus = $card->status;
            $newStatus = $hasAccess ? 'active' : 'suspended';

            if ($previousStatus !== $newStatus) {
                $card->update([
                    'status' => $newStatus,
                    'suspended_at' => $hasAccess ? null : now(),
                    'suspension_reason' => $hasAccess ? null : 'Tenant suspended due to overdue payments',
                ]);

                Log::info('RFID card access updated', [
                    'card_id' => $card->id,
                    'card_number' => $card->card_number,
                    'tenant_id' => $tenant->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'has_access' => $hasAccess,
                ]);

                // Broadcast to IoT devices via MQTT if needed
                $this->notifyIoTDevices($card, $hasAccess, $tenant);
            }
        }
    }

    /**
     * Notify IoT devices about access changes via MQTT
     */
    private function notifyIoTDevices(RfidCard $card, bool $hasAccess, Tenant $tenant): void
    {
        try {
            $message = [
                'action' => 'update_card_access',
                'card_number' => $card->card_number,
                'tenant_id' => $tenant->id,
                'room_number' => $tenant->room->room_number,
                'access_granted' => $hasAccess,
                'reason' => $hasAccess ? 'Payment current' : 'Payment overdue',
                'timestamp' => now()->toISOString(),
            ];

            // Publish to room-specific device topic
            $topic = "kost_system/room/{$tenant->room->room_number}/access_update";

            if (class_exists('\App\Services\MqttService')) {
                $mqttService = app(\App\Services\MqttService::class);
                $mqttService->publish($topic, json_encode($message));
            }

            Log::info('IoT access notification sent', [
                'topic' => $topic,
                'card_number' => $card->card_number,
                'access_granted' => $hasAccess,
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to notify IoT devices', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bulk update access for all tenants
     */
    public function updateAllTenantsAccess(): array
    {
        $tenants = Tenant::whereIn('status', ['active', 'suspended'])->get();
        $results = [
            'processed' => 0,
            'updated' => 0,
            'errors' => 0,
            'suspended' => 0,
            'activated' => 0,
        ];

        foreach ($tenants as $tenant) {
            $results['processed']++;

            $result = $this->updateTenantAccess($tenant->id);

            if ($result['success']) {
                if ($result['previous_status'] !== $result['current_status']) {
                    $results['updated']++;

                    if ($result['current_status'] === 'suspended') {
                        $results['suspended']++;
                    } elseif ($result['current_status'] === 'active') {
                        $results['activated']++;
                    }
                }
            } else {
                $results['errors']++;
            }
        }

        Log::info('Bulk tenant access update completed', $results);

        return $results;
    }
}
