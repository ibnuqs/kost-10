<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Notification;
use Exception;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        // Set your Merchant Server Key
        Config::$serverKey = config('services.midtrans.server_key');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        Config::$isProduction = config('services.midtrans.is_production', false);
        // Set sanitization on (default)
        Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        Config::$is3ds = true;
    }

    /**
     * Create Snap payment token
     */
    public function createSnapToken(Payment $payment): array
    {
        try {
            // Debug Midtrans configuration
            Log::info('Midtrans Configuration Check', [
                'server_key_set' => ! empty(Config::$serverKey),
                'server_key_length' => strlen(Config::$serverKey ?? ''),
                'is_production' => Config::$isProduction,
                'is_sanitized' => Config::$isSanitized,
                'is_3ds' => Config::$is3ds,
                'config_server_key' => config('services.midtrans.server_key') ? 'SET' : 'NOT_SET',
                'env_server_key' => env('MIDTRANS_SERVER_KEY') ? 'SET' : 'NOT_SET',
            ]);

            // ✅ FIXED: Load tenant relationship properly
            if (! $payment->tenant) {
                $payment->load('tenant.user');
            }

            if (! $payment->tenant) {
                Log::error('Payment tenant relationship not found', [
                    'payment_id' => $payment->id,
                    'tenant_id' => $payment->tenant_id ?? 'NULL',
                ]);
                throw new Exception('Payment tenant relationship not found. Payment ID: '.$payment->id);
            }

            if (! $payment->tenant->user) {
                Log::error('Payment tenant user relationship not found', [
                    'payment_id' => $payment->id,
                    'tenant_id' => $payment->tenant_id,
                    'user_id' => $payment->tenant->user_id ?? 'NULL',
                ]);
                throw new Exception('Payment tenant user relationship not found. Payment ID: '.$payment->id);
            }

            // ✅ FIXED: Ensure proper data types and clean values
            $orderId = (string) $payment->order_id;
            $amount = (int) round((float) $payment->amount); // Ensure integer, no decimals
            $tenantName = (string) ($payment->tenant->user->name ?? $payment->tenant->name ?? 'Unknown');
            $tenantEmail = (string) ($payment->tenant->user->email ?? $payment->tenant->email ?? 'noemail@example.com');
            $tenantPhone = (string) ($payment->tenant->user->phone ?? $payment->tenant->phone ?? '');

            // ✅ FIXED: Clean payment month format
            $paymentMonth = $payment->payment_month;
            $formattedMonth = date('F Y', strtotime($paymentMonth.'-01'));

            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'customer_details' => [
                    'first_name' => $tenantName,
                    'email' => $tenantEmail,
                    'phone' => $tenantPhone,
                ],
                'item_details' => [
                    [
                        'id' => 'rent_'.$paymentMonth,
                        'price' => $amount,
                        'quantity' => 1,
                        'name' => 'Sewa Kamar Bulan '.$formattedMonth,
                        'category' => 'rent',
                    ],
                ],
                // ✅ FIXED: Dynamic callback URLs (ngrok-ready)
                'callbacks' => [
                    'finish' => env('FRONTEND_URL', 'http://localhost:3000').'/tenant/payments?status=success&order_id='.$orderId,
                    'unfinish' => env('FRONTEND_URL', 'http://localhost:3000').'/tenant/payments?status=pending&order_id='.$orderId,
                    'error' => env('FRONTEND_URL', 'http://localhost:3000').'/tenant/payments?status=failed&order_id='.$orderId,
                ],
                // ✅ NEW: Webhook notification URL for real-time updates
                'custom_field1' => env('WEBHOOK_URL', env('NGROK_URL', 'http://localhost:8000').'/api/webhook/midtrans'),
                // ✅ FIXED: Simplified expiry without complex date formatting
                'expiry' => [
                    'unit' => 'day',
                    'duration' => 1,
                ],
            ];

            // ✅ ADDED: Debug logging
            Log::info('Midtrans Snap token request parameters', [
                'order_id' => $orderId,
                'amount' => $amount,
                'tenant_name' => $tenantName,
                'params' => $params,
            ]);

            // Check if required configuration is set
            if (empty(Config::$serverKey)) {
                throw new Exception('Midtrans server key is not configured');
            }

            Log::info('Attempting to create Snap token with Midtrans...', [
                'request_url' => Config::$isProduction ? 'production' : 'sandbox',
                'server_key_prefix' => substr(Config::$serverKey, 0, 10).'...',
            ]);

            try {
                $snapToken = Snap::getSnapToken($params);
            } catch (\Exception $e) {
                // Handle duplicate order_id error
                if (strpos($e->getMessage(), 'order_id has already been taken') !== false) {
                    Log::warning('Order ID already exists, generating new one', [
                        'old_order_id' => $orderId,
                        'payment_id' => $payment->id,
                    ]);

                    // Generate new order_id and update payment
                    $newOrderId = self::generateOrderId($payment->tenant_id, $payment->payment_month);
                    $payment->update(['order_id' => $newOrderId]);

                    // Update params with new order_id
                    $params['transaction_details']['order_id'] = $newOrderId;
                    $params['callbacks']['finish'] = str_replace($orderId, $newOrderId, $params['callbacks']['finish']);
                    $params['callbacks']['unfinish'] = str_replace($orderId, $newOrderId, $params['callbacks']['unfinish']);
                    $params['callbacks']['error'] = str_replace($orderId, $newOrderId, $params['callbacks']['error']);

                    Log::info('Retrying with new order_id', ['new_order_id' => $newOrderId]);
                    $snapToken = Snap::getSnapToken($params);
                } else {
                    throw $e;
                }
            }

            Log::info('Midtrans Snap token request successful', [
                'token_length' => strlen($snapToken),
                'token_prefix' => substr($snapToken, 0, 20).'...',
            ]);

            // Update payment with snap token and timestamp
            $payment->update([
                'snap_token' => $snapToken,
                'snap_token_created_at' => now(),
            ]);

            Log::info('Midtrans Snap token created successfully', [
                'order_id' => $orderId,
                'amount' => $amount,
                'tenant_id' => $payment->tenant_id,
                'snap_token' => substr($snapToken, 0, 10).'...', // Log partial token for security
            ]);

            return [
                'success' => true,
                'snap_token' => $snapToken,
                'payment' => $payment,
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Midtrans Snap token', [
                'error' => $e->getMessage(),
                'order_id' => $payment->order_id ?? 'unknown',
                'payment_id' => $payment->id ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Midtrans notification webhook
     */
    public function handleNotification(array $notificationData): array
    {
        try {
            $notification = new Notification;

            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $type = $notification->payment_type;
            $fraudStatus = $notification->fraud_status ?? null;

            Log::info('Midtrans notification received', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'payment_type' => $type,
                'fraud_status' => $fraudStatus,
            ]);

            // Find payment by order_id
            $payment = Payment::where('order_id', $orderId)->first();

            if (! $payment) {
                Log::warning('Payment not found for order_id', ['order_id' => $orderId]);

                return [
                    'success' => false,
                    'message' => 'Payment not found',
                ];
            }

            // Update payment status based on transaction status
            $this->updatePaymentStatus($payment, $transactionStatus, $fraudStatus, $notification);

            return [
                'success' => true,
                'message' => 'Notification handled successfully',
                'payment' => $payment,
            ];

        } catch (Exception $e) {
            Log::error('Failed to handle Midtrans notification', [
                'error' => $e->getMessage(),
                'notification_data' => $notificationData,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update payment status based on Midtrans response
     */
    private function updatePaymentStatus(Payment $payment, string $transactionStatus, ?string $fraudStatus, Notification $notification): void
    {
        $paymentMethod = $notification->payment_type ?? null;
        $paidAt = null;

        switch ($transactionStatus) {
            case 'capture':
                if ($fraudStatus == 'challenge') {
                    $status = 'pending';
                } elseif ($fraudStatus == 'accept') {
                    $status = 'paid';
                    $paidAt = now();
                } else {
                    $status = 'pending';
                }
                break;

            case 'settlement':
                $status = 'paid';
                $paidAt = now();
                break;

            case 'pending':
                $status = 'pending';
                break;

            case 'deny':
            case 'expire':
            case 'cancel':
                $status = 'failed';
                break;

            default:
                $status = 'pending';
                break;
        }

        // Update payment
        $payment->update([
            'status' => $status,
            'payment_method' => $paymentMethod,
            'paid_at' => $paidAt,
        ]);

        Log::info('Payment status updated', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'old_status' => $payment->getOriginal('status'),
            'new_status' => $status,
            'transaction_status' => $transactionStatus,
        ]);

        // Send notification to tenant if payment successful
        if ($status === 'paid') {
            $this->sendPaymentSuccessNotification($payment);
        }
    }

    /**
     * Check payment status from Midtrans
     */
    public function checkPaymentStatus(string $orderId): array
    {
        try {
            Log::info('🔍 Checking payment status from Midtrans', [
                'order_id' => $orderId,
                'environment' => Config::$isProduction ? 'production' : 'sandbox',
                'timestamp' => now()->toISOString(),
            ]);

            $status = Transaction::status($orderId);

            // Ensure $status is an object, even if Midtrans returns an array
            if (is_array($status)) {
                $status = (object) $status;
            }

            // Log detailed response
            Log::info('✅ Midtrans status response received', [
                'order_id' => $orderId,
                'transaction_status' => $status->transaction_status ?? 'unknown',
                'payment_type' => $status->payment_type ?? 'unknown',
                'fraud_status' => $status->fraud_status ?? null,
                'gross_amount' => $status->gross_amount ?? null,
                'transaction_time' => $status->transaction_time ?? null,
                'status_message' => $status->status_message ?? null,
                'full_response' => json_encode($status, JSON_UNESCAPED_SLASHES),
            ]);

            return [
                'success' => true,
                'status' => $status,
            ];

        } catch (Exception $e) {
            Log::error('❌ Failed to check payment status from Midtrans', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'error_class' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'environment' => Config::$isProduction ? 'production' : 'sandbox',
            ]);

            

            

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel payment transaction
     */
    public function cancelPayment(string $orderId): array
    {
        try {
            $cancel = Transaction::cancel($orderId);

            Log::info('Payment cancelled', [
                'order_id' => $orderId,
                'response' => $cancel,
            ]);

            return [
                'success' => true,
                'data' => $cancel,
            ];

        } catch (Exception $e) {
            Log::error('Failed to cancel payment', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send payment success notification
     */
    private function sendPaymentSuccessNotification(Payment $payment): void
    {
        try {
            if (! $payment->tenant || ! $payment->tenant->user) {
                Log::warning('Cannot send payment success notification: Tenant or user data missing.', [
                    'payment_id' => $payment->id,
                ]);

                return;
            }

            \App\Models\Notification::create([
                'user_id' => $payment->tenant->user_id,
                'title' => 'Pembayaran Berhasil',
                'message' => 'Pembayaran Anda sebesar Rp '.number_format((float) $payment->amount, 0, ',', '.').' untuk tagihan bulan '.date('F Y', strtotime($payment->payment_month.'-01')).' telah berhasil.',
                'type' => 'payment',
                'data' => json_encode([
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => $payment->amount,
                ]),
            ]);

            Log::info('Payment success notification created', [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
                'user_id' => $payment->tenant->user_id,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send payment success notification', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);
        }
    }

    /**
     * Generate unique order ID
     */
    public static function generateOrderId(int $tenantId, string $paymentMonth): string
    {
        // Add microseconds and random string for better uniqueness
        $timestamp = time();
        $microseconds = substr(microtime(), 2, 6);
        $random = strtoupper(substr(uniqid(), -4));

        return 'RENT-'.$tenantId.'-'.$paymentMonth.'-'.$timestamp.$microseconds.$random;
    }

    /**
     * Format amount for Midtrans (remove decimal)
     */
    public static function formatAmount(float $amount): int
    {
        return (int) round($amount);
    }
}
