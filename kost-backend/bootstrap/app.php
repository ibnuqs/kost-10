<?php

// bootstrap/app.php - ENHANCED VERSION dengan Expired Payment Support

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ FIXED: Custom CORS middleware untuk production
        $middleware->api(prepend: [
            \App\Http\Middleware\CustomCors::class,
        ]);

        // Register custom middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'admin.only' => \App\Http\Middleware\AdminOnly::class,
            'tenant.only' => \App\Http\Middleware\TenantOnly::class,
            'check.user.status' => \App\Http\Middleware\CheckUserStatus::class,
            'validate.token.abilities' => \App\Http\Middleware\ValidateTokenAbilities::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withCommands([
        // Existing MQTT commands
        \App\Console\Commands\MqttListener::class,
        \App\Console\Commands\MqttTest::class,

        // ✅ NEW: Expired Payment Management Commands
        \App\Console\Commands\CheckExpiredPayments::class,
        \App\Console\Commands\CleanupOldExpiredPayments::class,

        // ✅ NEW: Monthly Payment & Tenant Management Commands
        \App\Console\Commands\GenerateMonthlyPayments::class,
        \App\Console\Commands\ProcessPaymentStatus::class,
        \App\Console\Commands\CleanupExpiredReservations::class,

        // ✅ NEW: Receipt Management Commands
        \App\Console\Commands\CleanupOldReceipts::class,
    ])
    ->withSchedule(function ($schedule) {
        // ===================================================================
        // SCHEDULED TASKS UNTUK EXPIRED PAYMENT HANDLING
        // ===================================================================

        // Check for expired payments every hour
        $schedule->command('payments:check-expired')
            ->hourly()
            ->withoutOverlapping(10) // Prevent overlap for 10 minutes
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Expired payments check failed');

                // Send email notification on failure
                if ($adminEmail = env('ADMIN_EMAIL')) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw(
                            'The scheduled task for checking expired payments has failed. Please check the logs.',
                            function ($message) use ($adminEmail) {
                                $message->to($adminEmail)
                                    ->subject('Expired Payments Check Failed - '.config('app.name'));
                            }
                        );
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send failure email', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            })
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Expired payments check completed successfully');
            })
            ->appendOutputTo(storage_path('logs/expired-payments.log'))
            ->description('Check and mark expired payments hourly');

        // Send expiration notifications every 4 hours
        $schedule->command('payments:check-expired --notify')
            ->everyFourHours()
            ->withoutOverlapping(15)
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Expired payments notification check failed');
            })
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Expired payments notification check completed');
            })
            ->appendOutputTo(storage_path('logs/expired-payments-notifications.log'))
            ->description('Check expired payments and send notifications every 4 hours');

        // Daily cleanup of very old expired payments
        $schedule->command('payments:cleanup-old-expired --days=90 --force')
            ->dailyAt('02:00')
            ->withoutOverlapping(30)
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Old expired payments cleanup failed');
            })
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Old expired payments cleanup completed');
            })
            ->appendOutputTo(storage_path('logs/expired-payments-cleanup.log'))
            ->description('Daily cleanup of very old expired payments at 2 AM');

        // Weekly payment system health check
        $schedule->call(function () {
            try {
                $stats = [
                    'total_payments' => \App\Models\Payment::count(),
                    'pending_payments' => \App\Models\Payment::where('status', 'pending')->count(),
                    'expired_payments' => \App\Models\Payment::where('status', 'expired')->count(),
                    'paid_payments' => \App\Models\Payment::where('status', 'paid')->count(),
                    'old_pending_payments' => \App\Models\Payment::where('status', 'pending')
                        ->where('created_at', '<', now()->subDays(5))
                        ->count(),
                    'near_expiry_payments' => \App\Models\Payment::where('status', 'pending')
                        ->where('created_at', '<', now()->subDays(6))
                        ->count(),
                    'check_time' => now()->toDateTimeString(),
                ];

                \Illuminate\Support\Facades\Log::info('Weekly payment system health check', $stats);

                // Alert if there are issues
                $alerts = [];

                if ($stats['old_pending_payments'] > 10) {
                    $alerts[] = "High number of old pending payments: {$stats['old_pending_payments']}";
                }

                if ($stats['near_expiry_payments'] > 20) {
                    $alerts[] = "High number of near-expiry payments: {$stats['near_expiry_payments']}";
                }

                $expiredRate = $stats['total_payments'] > 0
                    ? ($stats['expired_payments'] / $stats['total_payments']) * 100
                    : 0;

                if ($expiredRate > 20) {
                    $alerts[] = 'High expiration rate: '.round($expiredRate, 1).'%';
                }

                if (! empty($alerts)) {
                    $alertMessage = "Payment System Alerts:\n\n".implode("\n", $alerts)."\n\n".
                        "System Statistics:\n".
                        "Total Payments: {$stats['total_payments']}\n".
                        "Pending: {$stats['pending_payments']}\n".
                        "Paid: {$stats['paid_payments']}\n".
                        "Expired: {$stats['expired_payments']}\n".
                        "Old Pending: {$stats['old_pending_payments']}\n".
                        "Near Expiry: {$stats['near_expiry_payments']}\n\n".
                        "Check Time: {$stats['check_time']}";

                    \Illuminate\Support\Facades\Log::warning('Payment system alerts detected', [
                        'alerts' => $alerts,
                        'stats' => $stats,
                    ]);

                    // Send alert email if configured
                    if ($adminEmail = env('ADMIN_EMAIL')) {
                        try {
                            \Illuminate\Support\Facades\Mail::raw(
                                $alertMessage,
                                function ($message) use ($adminEmail) {
                                    $message->to($adminEmail)
                                        ->subject('Payment System Alert - '.config('app.name'));
                                }
                            );
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to send alert email', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Payment system health check failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        })
            ->weekly()
            ->sundays()
            ->at('08:00')
            ->name('payment-system-health-check')
            ->description('Weekly payment system health check every Sunday at 8 AM');

        // Monthly payment statistics report
        $schedule->call(function () {
            try {
                $currentMonth = now()->format('Y-m');
                $previousMonth = now()->subMonth()->format('Y-m');

                $currentStats = [
                    'month' => $currentMonth,
                    'total_payments' => \App\Models\Payment::where('payment_month', $currentMonth)->count(),
                    'paid' => \App\Models\Payment::where('payment_month', $currentMonth)
                        ->where('status', 'paid')->count(),
                    'pending' => \App\Models\Payment::where('payment_month', $currentMonth)
                        ->where('status', 'pending')->count(),
                    'expired' => \App\Models\Payment::where('payment_month', $currentMonth)
                        ->where('status', 'expired')->count(),
                    'revenue' => \App\Models\Payment::where('payment_month', $currentMonth)
                        ->where('status', 'paid')
                        ->sum('amount'),
                ];

                $previousStats = [
                    'month' => $previousMonth,
                    'total_payments' => \App\Models\Payment::where('payment_month', $previousMonth)->count(),
                    'paid' => \App\Models\Payment::where('payment_month', $previousMonth)
                        ->where('status', 'paid')->count(),
                    'revenue' => \App\Models\Payment::where('payment_month', $previousMonth)
                        ->where('status', 'paid')
                        ->sum('amount'),
                ];

                // Calculate rates and changes
                $currentSuccessRate = $currentStats['total_payments'] > 0
                    ? round(($currentStats['paid'] / $currentStats['total_payments']) * 100, 2)
                    : 0;

                $previousSuccessRate = $previousStats['total_payments'] > 0
                    ? round(($previousStats['paid'] / $previousStats['total_payments']) * 100, 2)
                    : 0;

                $revenueChange = $previousStats['revenue'] > 0
                    ? round((($currentStats['revenue'] - $previousStats['revenue']) / $previousStats['revenue']) * 100, 2)
                    : 0;

                $stats = array_merge($currentStats, [
                    'success_rate' => $currentSuccessRate.'%',
                    'previous_success_rate' => $previousSuccessRate.'%',
                    'revenue_change' => $revenueChange.'%',
                    'report_generated_at' => now()->toDateTimeString(),
                ]);

                \Illuminate\Support\Facades\Log::info('Monthly payment statistics report', $stats);

                // Send monthly report email if configured
                if ($adminEmail = env('ADMIN_EMAIL')) {
                    try {
                        $reportContent = "Monthly Payment Report for {$currentMonth}\n\n".
                            "=== CURRENT MONTH ({$currentMonth}) ===\n".
                            "Total Payments: {$currentStats['total_payments']}\n".
                            "Paid: {$currentStats['paid']}\n".
                            "Pending: {$currentStats['pending']}\n".
                            "Expired: {$currentStats['expired']}\n".
                            "Success Rate: {$currentSuccessRate}%\n".
                            'Revenue: Rp '.number_format((float) $currentStats['revenue'], 0, ',', '.')."\n\n".
                            "=== COMPARISON WITH PREVIOUS MONTH ({$previousMonth}) ===\n".
                            "Previous Success Rate: {$previousSuccessRate}%\n".
                            'Previous Revenue: Rp '.number_format((float) $previousStats['revenue'], 0, ',', '.')."\n".
                            "Revenue Change: {$revenueChange}%\n\n".
                            "Report Generated: {$stats['report_generated_at']}";

                        \Illuminate\Support\Facades\Mail::raw(
                            $reportContent,
                            function ($message) use ($adminEmail, $currentMonth) {
                                $message->to($adminEmail)
                                    ->subject("Monthly Payment Report - {$currentMonth} - ".config('app.name'));
                            }
                        );
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send monthly report email', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Monthly payment report generation failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        })
            ->monthlyOn(1, '09:00') // First day of month at 9 AM
            ->name('monthly-payment-report')
            ->description('Generate monthly payment statistics report on 1st of each month at 9 AM');

        // ===================================================================
        // NEW: MONTHLY PAYMENT GENERATION & TENANT STATUS MANAGEMENT
        // ===================================================================

        // Generate monthly payments on 25th of each month at 9 AM
        $schedule->command('payments:generate-monthly')
            ->monthlyOn(25, '09:00')
            ->withoutOverlapping(60) // Prevent overlap for 1 hour
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Monthly payment generation failed');

                if ($adminEmail = env('ADMIN_EMAIL')) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw(
                            'The scheduled task for generating monthly payments has failed. Please check the logs and run manually if needed.',
                            function ($message) use ($adminEmail) {
                                $message->to($adminEmail)
                                    ->subject('Monthly Payment Generation Failed - '.config('app.name'));
                            }
                        );
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send payment generation failure email', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            })
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Monthly payment generation completed successfully');
            })
            ->appendOutputTo(storage_path('logs/monthly-payments.log'))
            ->description('Generate monthly payments for all active tenants on 25th of each month');

        // Process payment statuses and tenant suspensions daily at 6 AM
        $schedule->command('payments:process-status --grace-days=7')
            ->dailyAt('06:00')
            ->withoutOverlapping(30)
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Payment status processing failed');

                if ($adminEmail = env('ADMIN_EMAIL')) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw(
                            'The scheduled task for processing payment statuses and tenant suspensions has failed. Please check the logs.',
                            function ($message) use ($adminEmail) {
                                $message->to($adminEmail)
                                    ->subject('Payment Status Processing Failed - '.config('app.name'));
                            }
                        );
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send status processing failure email', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            })
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Payment status processing completed successfully');
            })
            ->appendOutputTo(storage_path('logs/payment-status.log'))
            ->description('Process payment statuses and suspend overdue tenants daily at 6 AM');

        // Quick payment status check every 2 hours during business hours
        $schedule->command('payments:process-status --grace-days=7')
            ->cron('0 8-18/2 * * *') // Every 2 hours from 8 AM to 6 PM
            ->withoutOverlapping(15)
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::warning('Business hours payment status check failed');
            })
            ->appendOutputTo(storage_path('logs/payment-status-quick.log'))
            ->description('Quick payment status check every 2 hours during business hours');

        // Clean up expired room reservations every hour
        $schedule->command('rooms:cleanup-expired-reservations')
            ->hourly()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::warning('Expired reservation cleanup failed');
            })
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Expired reservation cleanup completed');
            })
            ->appendOutputTo(storage_path('logs/reservation-cleanup.log'))
            ->description('Clean up expired room reservations every hour');

        // Clean up old receipt files weekly
        $schedule->command('receipts:cleanup --days=90')
            ->weekly()
            ->sundays()
            ->at('01:00')
            ->withoutOverlapping(30)
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::warning('Receipt cleanup failed');
            })
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Receipt cleanup completed');
            })
            ->appendOutputTo(storage_path('logs/receipt-cleanup.log'))
            ->description('Clean up old receipt files weekly on Sunday at 1 AM');

        // ===================================================================
        // EXISTING MQTT/RFID SCHEDULED TASKS (if any)
        // ===================================================================

        // You can add any existing MQTT or other scheduled tasks here

    })
    ->create();
