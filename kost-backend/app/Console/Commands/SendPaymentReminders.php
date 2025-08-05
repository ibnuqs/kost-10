<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders extends Command
{
    protected $signature = 'payments:send-reminders 
                          {--dry-run : Show what would be sent without actually sending}
                          {--days=3,2,1,0 : Comma-separated list of days before due date to send reminders}';

    protected $description = 'Send payment reminders to tenants based on days before due date';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $reminderDays = array_map('intval', explode(',', $this->option('days')));

        $this->info('🔔 Sending payment reminders...');

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No reminders will be sent');
        }

        $totalSent = 0;
        $errors = 0;

        foreach ($reminderDays as $daysBeforeDue) {
            $sent = $this->sendRemindersForDay($daysBeforeDue, $isDryRun);
            $totalSent += $sent['sent'];
            $errors += $sent['errors'];
        }

        $this->info("✅ Payment reminders completed!");
        $this->table(['Result', 'Count'], [
            ['Total Reminders Sent', $totalSent],
            ['Errors', $errors],
        ]);

        Log::info('Payment reminders completed', [
            'total_sent' => $totalSent,
            'errors' => $errors,
            'dry_run' => $isDryRun,
        ]);

        return $errors > 0 ? 1 : 0;
    }

    private function sendRemindersForDay(int $daysBeforeDue, bool $isDryRun = false): array
    {
        $today = now();
        $targetDate = $today->copy()->addDays($daysBeforeDue);
        
        $this->info("📅 Processing reminders for {$daysBeforeDue} days before due date...");

        // Find pending payments where due date (end of payment month) matches target date
        $pendingPayments = Payment::where('status', 'pending')
            ->get()
            ->filter(function ($payment) use ($targetDate) {
                $dueDate = Carbon::createFromFormat('Y-m', $payment->payment_month)->endOfMonth();
                return $dueDate->isSameDay($targetDate);
            });

        if ($pendingPayments->isEmpty()) {
            $this->line("  ℹ️  No payments due in {$daysBeforeDue} days");
            return ['sent' => 0, 'errors' => 0];
        }

        $sent = 0;
        $errors = 0;

        foreach ($pendingPayments as $payment) {
            try {
                $tenant = $payment->tenant()->with('user')->first();
                
                if (!$tenant || !$tenant->user) {
                    $this->error("  ❌ No tenant/user found for payment {$payment->id}");
                    $errors++;
                    continue;
                }

                $dueDate = Carbon::createFromFormat('Y-m', $payment->payment_month)->endOfMonth();
                $message = $this->generateReminderMessage($payment, $daysBeforeDue, $dueDate);

                if (!$isDryRun) {
                    Notification::create([
                        'user_id' => $tenant->user_id,
                        'title' => $this->generateReminderTitle($daysBeforeDue),
                        'message' => $message,
                        'type' => 'payment',
                        'data' => json_encode([
                            'payment_id' => $payment->id,
                            'order_id' => $payment->order_id,
                            'amount' => $payment->amount,
                            'due_date' => $dueDate->format('Y-m-d'),
                            'days_left' => $daysBeforeDue,
                        ]),
                    ]);
                }

                $this->line("  📧 {$tenant->user->name} (Payment #{$payment->id}) - {$message}");
                $sent++;

            } catch (\Exception $e) {
                $this->error("  ❌ Error sending reminder for payment {$payment->id}: " . $e->getMessage());
                $errors++;
                
                Log::error('Failed to send payment reminder', [
                    'payment_id' => $payment->id,
                    'days_before_due' => $daysBeforeDue,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'errors' => $errors];
    }

    private function generateReminderTitle(int $daysBeforeDue): string
    {
        switch ($daysBeforeDue) {
            case 3:
                return 'Pengingat Pembayaran - 3 Hari Lagi';
            case 2:
                return 'Pengingat Pembayaran - 2 Hari Lagi';
            case 1:
                return 'Pengingat Pembayaran - Besok';
            case 0:
                return 'Pembayaran Jatuh Tempo Hari Ini';
            default:
                return 'Pengingat Pembayaran';
        }
    }

    private function generateReminderMessage(Payment $payment, int $daysBeforeDue, Carbon $dueDate): string
    {
        $monthName = $dueDate->format('F Y');
        $dueDateFormatted = $dueDate->format('d F Y');
        $amountFormatted = 'Rp ' . number_format($payment->amount, 0, ',', '.');

        switch ($daysBeforeDue) {
            case 3:
                return "Tagihan kos bulan {$monthName} sebesar {$amountFormatted} jatuh tempo dalam 3 hari ({$dueDateFormatted}). Segera lakukan pembayaran.";
            
            case 2:
                return "Tagihan kos bulan {$monthName} sebesar {$amountFormatted} jatuh tempo dalam 2 hari ({$dueDateFormatted}). Jangan sampai terlambat!";
            
            case 1:
                return "Tagihan kos bulan {$monthName} sebesar {$amountFormatted} jatuh tempo besok ({$dueDateFormatted}). Harap segera bayar!";
            
            case 0:
                return "Hari terakhir pembayaran tagihan kos bulan {$monthName} sebesar {$amountFormatted}! Segera lakukan pembayaran sebelum tengah malam.";
            
            default:
                return "Pengingat pembayaran tagihan kos bulan {$monthName} sebesar {$amountFormatted} jatuh tempo pada {$dueDateFormatted}.";
        }
    }
}