<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\MidtransService; // Pastikan service ini tersedia
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Untuk operasi tanggal

class PaymentController extends Controller
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Get tenant's payments with expired status check (alias for index)
     */
    public function index(Request $request)
    {
        return $this->tenantPayments($request);
    }

    /**
     * Get tenant's payments with expired status check
     */
    public function tenantPayments(Request $request)
    {
        try {
            $user = $request->user();

            $tenant = Tenant::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (! $tenant) {
                Log::warning('No active tenant found for user', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Tenant information not found or not active.',
                ], 200);
            }

            $payments = Payment::where('tenant_id', $tenant->id)
                ->orderBy('payment_month', 'desc')
                ->get()
                ->map(function ($payment) {
                    // Add computed fields
                    $payment->is_expired = $this->isPaymentExpired($payment);
                    $payment->can_regenerate = $this->canRegeneratePayment($payment);
                    $payment->expires_at = $this->getPaymentExpirationDate($payment);

                    return $payment;
                });

            Log::info('Tenant payments retrieved', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'payment_count' => $payments->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $payments->toArray(),
                'message' => 'Payments retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve tenant payments', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get specific payment for tenant (with authorization check).
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            $payment = Payment::with(['tenant.user'])->findOrFail($id);

            // Authorization: Ensure the payment belongs to the logged-in tenant
            $tenant = Tenant::where('user_id', $user->id)->first();
            if (! $tenant || $payment->tenant_id !== $tenant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Payment retrieved successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve payment', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment URL with expired handling for tenant.
     */
    public function getPaymentUrl(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (! $user) {
                Log::error('No authenticated user for payment URL request', ['payment_id' => $id]);

                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $tenant = Tenant::where('user_id', $user->id)->where('status', 'active')->first();

            if (! $tenant) {
                Log::error('No active tenant found for user', [
                    'user_id' => $user->id,
                    'payment_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Tenant information not found',
                ], 404);
            }

            // Load payment with proper error handling
            $payment = Payment::where('tenant_id', $tenant->id)->find($id);

            if (! $payment) {
                Log::error('Payment not found for tenant', [
                    'payment_id' => $id,
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            // Ensure relationships are loaded
            $payment->load('tenant.user');

            // Check if payment is already paid
            if ($payment->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has already been paid',
                ], 422);
            }

            // Check if payment is expired and handle it
            if ($this->isPaymentExpired($payment)) {
                Log::info('Payment expired, handling regeneration', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'created_at' => $payment->created_at,
                    'snap_token_created' => $payment->snap_token_created_at,
                ]);

                // Check if we can regenerate
                if (! $this->canRegeneratePayment($payment)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment has expired and cannot be regenerated. Please contact support.',
                        'error_code' => 'PAYMENT_EXPIRED_NO_REGEN',
                        'data' => [
                            'expired_at' => $this->getPaymentExpirationDate($payment),
                            'can_regenerate' => false,
                            'contact_support' => true,
                        ],
                    ], 410); // 410 Gone
                }

                // Regenerate payment
                $regeneratedPayment = $this->regeneratePayment($payment);
                if (! $regeneratedPayment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to regenerate expired payment',
                        'error_code' => 'PAYMENT_REGEN_FAILED',
                    ], 500);
                }

                $payment = $regeneratedPayment;

                Log::info('Payment regenerated successfully', [
                    'old_payment_id' => $id,
                    'new_payment_id' => $payment->id,
                    'new_order_id' => $payment->order_id,
                ]);
            }

            try {
                // Verify Midtrans configuration before attempting to create token
                $serverKey = config('services.midtrans.server_key');
                $clientKey = config('services.midtrans.client_key');

                if (empty($serverKey) || empty($clientKey)) {
                    Log::error('Midtrans configuration missing', [
                        'payment_id' => $payment->id,
                        'server_key_exists' => ! empty($serverKey),
                        'client_key_exists' => ! empty($clientKey),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Payment gateway configuration error',
                        'error' => 'Midtrans configuration is not properly set',
                    ], 500);
                }

                // Create or get Snap token
                if (! $payment->snap_token || $this->isSnapTokenExpired($payment)) {
                    Log::info('Creating new Snap token for payment', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'tenant_id' => $payment->tenant_id,
                    ]);

                    $result = $this->midtransService->createSnapToken($payment);

                    if (! $result['success']) {
                        Log::error('Failed to create Midtrans Snap token', [
                            'payment_id' => $payment->id,
                            'order_id' => $payment->order_id,
                            'error' => $result['error'] ?? 'Unknown error',
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to create payment URL',
                            'error' => $result['error'] ?? 'Unknown payment gateway error',
                        ], 500);
                    }

                    // Update snap token created timestamp
                    $payment->update([
                        'snap_token' => $result['snap_token'],
                        'snap_token_created_at' => now(),
                    ]);
                }

                $payment->refresh();

                // Generate URLs
                $isProduction = env('MIDTRANS_IS_PRODUCTION', false);

                if ($isProduction) {
                    $snapUrl = "https://app.midtrans.com/snap/v1/transactions/{$payment->snap_token}";
                } else {
                    $snapUrl = "https://app.sandbox.midtrans.com/snap/v1/transactions/{$payment->snap_token}";
                }

                // Calculate expiration
                $expiresAt = $this->getPaymentExpirationDate($payment);

                $responseData = [
                    'payment_url' => $snapUrl,
                    'snap_token' => $payment->snap_token,
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'expires_at' => $expiresAt->toISOString(),
                    'is_expired' => false,
                    'can_regenerate' => true,

                    // Expiration info
                    'expiration_info' => [
                        'expires_at' => $expiresAt->toISOString(),
                        'expires_in_minutes' => now()->diffInMinutes($expiresAt),
                        'expires_in_hours' => round(now()->diffInMinutes($expiresAt) / 60, 1),
                        'is_near_expiry' => now()->diffInMinutes($expiresAt) < 60, // Warning if < 1 hour
                    ],

                    // Alternative URLs
                    'alternative_urls' => [
                        'snap_redirect' => $snapUrl,
                        'direct_checkout' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$payment->snap_token}",
                        'mobile_deep_link' => "gojek://gopay/checkout/{$payment->snap_token}",
                        'embedded_checkout' => $snapUrl.'?embed=true',
                    ],

                    // Frontend config
                    'frontend_config' => [
                        'snap_token' => $payment->snap_token,
                        'client_key' => env('MIDTRANS_CLIENT_KEY'),
                        'is_production' => $isProduction,
                        'payment_type' => 'snap',
                        'transaction_details' => [
                            'order_id' => $payment->order_id,
                            'gross_amount' => (int) $payment->amount,
                        ],
                    ],
                ];

                return response()->json([
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Payment URL created successfully',
                ], 200);

            } catch (\Exception $midtransError) {
                Log::error('Midtrans specific error', [
                    'error' => $midtransError->getMessage(),
                    'payment_id' => $payment->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway error',
                    'error' => 'Failed to connect to payment gateway',
                ], 500);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found or unauthorized',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get payment URL', [
                'error' => $e->getMessage(),
                'payment_id' => $id,
                'user_id' => $request->user()->id,
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment URL',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Check payment status for tenant (with authorization check).
     */
    public function checkStatus(Request $request, $id)
    {
        try {
            $user = $request->user();
            $payment = Payment::findOrFail($id);

            // Authorization: Ensure the payment belongs to the logged-in tenant
            $tenant = Tenant::where('user_id', $user->id)->first();
            if (! $tenant || $payment->tenant_id !== $tenant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Check status from Midtrans if order_id exists
            $midtransStatus = null;
            if ($payment->order_id) {
                $result = $this->midtransService->checkPaymentStatus($payment->order_id);

                if ($result['success']) {
                    $midtransStatus = $result['status'];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $payment->status,
                    'updated_at' => $payment->updated_at->toISOString(),
                    'paid_at' => $payment->paid_at?->toISOString(),
                    'expired_at' => $payment->expired_at?->toISOString(),
                    'payment_method' => $payment->payment_method,
                    'is_expired' => $this->isPaymentExpired($payment),
                    'can_regenerate' => $this->canRegeneratePayment($payment),
                ],
                'message' => 'Payment status retrieved successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to check payment status', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync payment status with Midtrans manually.
     */
    public function syncPaymentStatus(Request $request, $id)
    {
        try {
            $user = $request->user();
            $payment = Payment::findOrFail($id);

            // Authorization: Ensure the payment belongs to the logged-in tenant
            $tenant = Tenant::where('user_id', $user->id)->first();
            if (! $tenant || $payment->tenant_id !== $tenant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            if (! $payment->order_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment does not have order_id for status checking',
                ], 422);
            }

            // Force sync with Midtrans
            $result = $this->midtransService->checkPaymentStatus($payment->order_id);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync with Midtrans: '.($result['error'] ?? 'Unknown error'),
                ], 500);
            }

            $midtransStatus = $result['status'];
            $oldStatus = $payment->status;

            // Update payment status based on Midtrans response
            $this->updatePaymentFromMidtransStatus($payment, $midtransStatus);

            $payment->refresh();

            Log::info('Payment status manually synced', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'old_status' => $oldStatus,
                'new_status' => $payment->status,
                'midtrans_status' => $midtransStatus->transaction_status ?? 'unknown',
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'old_status' => $oldStatus,
                    'new_status' => $payment->status,
                    'midtrans_status' => $midtransStatus->transaction_status ?? 'unknown',
                    'updated_at' => $payment->updated_at->toISOString(),
                    'paid_at' => $payment->paid_at?->toISOString(),
                ],
                'message' => 'Payment status synced successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to sync payment status', [
                'payment_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update payment status based on Midtrans transaction status.
     * Enhanced with proper validation to prevent premature status updates.
     */
    private function updatePaymentFromMidtransStatus(Payment $payment, $midtransStatus): void
    {
        $transactionStatus = $midtransStatus->transaction_status ?? 'unknown';
        $fraudStatus = $midtransStatus->fraud_status ?? null;
        $grossAmount = $midtransStatus->gross_amount ?? null;

        Log::info('Updating payment status from Midtrans', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'current_status' => $payment->status,
            'midtrans_transaction_status' => $transactionStatus,
            'midtrans_fraud_status' => $fraudStatus,
            'midtrans_gross_amount' => $grossAmount,
            'payment_amount' => $payment->amount,
        ]);

        // Validate gross amount matches payment amount (if available)
        if ($grossAmount && abs((float) $grossAmount - (float) $payment->amount) > 0.01) {
            Log::warning('Amount mismatch detected, skipping status update', [
                'payment_id' => $payment->id,
                'payment_amount' => $payment->amount,
                'midtrans_amount' => $grossAmount,
            ]);

            return;
        }

        // Only update if the new status represents actual progression
        switch ($transactionStatus) {
            case 'settlement':
                // Only mark as paid if transaction is truly settled
                if ($fraudStatus === 'accept' || $fraudStatus === null) {
                    if (! in_array($payment->status, ['paid', 'success'])) {
                        $payment->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'payment_method' => $midtransStatus->payment_type ?? null,
                            'transaction_id' => $midtransStatus->transaction_id ?? null,
                        ]);
                        Log::info('Payment marked as paid (settlement)', ['payment_id' => $payment->id]);
                    }
                } else {
                    Log::warning('Settlement with non-accept fraud status', [
                        'payment_id' => $payment->id,
                        'fraud_status' => $fraudStatus,
                    ]);
                }
                break;

            case 'capture':
                // For credit card capture, only mark as paid if fraud check passes
                if ($fraudStatus === 'accept') {
                    if (! in_array($payment->status, ['paid', 'success'])) {
                        $payment->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'payment_method' => $midtransStatus->payment_type ?? null,
                            'transaction_id' => $midtransStatus->transaction_id ?? null,
                        ]);
                        Log::info('Payment marked as paid (capture accepted)', ['payment_id' => $payment->id]);
                    }
                } elseif ($fraudStatus === 'challenge') {
                    // Keep as pending if fraud challenge
                    if ($payment->status !== 'pending') {
                        $payment->update(['status' => 'pending']);
                        Log::info('Payment kept as pending (fraud challenge)', ['payment_id' => $payment->id]);
                    }
                } else {
                    Log::warning('Capture with problematic fraud status', [
                        'payment_id' => $payment->id,
                        'fraud_status' => $fraudStatus,
                    ]);
                }
                break;

            case 'pending':
                // Only update to pending if not already in a final state
                if (! in_array($payment->status, ['paid', 'success', 'failed', 'expired'])) {
                    if ($payment->status !== 'pending') {
                        $payment->update(['status' => 'pending']);
                        Log::info('Payment status updated to pending', ['payment_id' => $payment->id]);
                    }
                }
                break;

            case 'deny':
            case 'cancel':
            case 'failure':
                // Only update to failed if not already paid
                if (! in_array($payment->status, ['paid', 'success'])) {
                    $payment->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'failure_reason' => $transactionStatus,
                    ]);
                    Log::info('Payment marked as failed', [
                        'payment_id' => $payment->id,
                        'reason' => $transactionStatus,
                    ]);
                }
                break;

            case 'expire':
                // Only update to expired if not already paid
                if (! in_array($payment->status, ['paid', 'success'])) {
                    $payment->update([
                        'status' => 'expired',
                        'expired_at' => now(),
                        'failure_reason' => 'Payment expired',
                    ]);
                    Log::info('Payment marked as expired', ['payment_id' => $payment->id]);
                }
                break;

            default:
                Log::warning('Unknown Midtrans transaction status', [
                    'payment_id' => $payment->id,
                    'transaction_status' => $transactionStatus,
                ]);
                break;
        }
    }

    /**
     * Get expiration info for a payment (Tenant only).
     */
    public function getExpirationInfo(Request $request, $id)
    {
        try {
            $user = $request->user();
            $tenant = Tenant::where('user_id', $user->id)->where('status', 'active')->first();

            if (! $tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant information not found',
                ], 404);
            }

            $payment = Payment::where('tenant_id', $tenant->id)->findOrFail($id);

            $expirationInfo = [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'is_expired' => $this->isPaymentExpired($payment),
                'can_regenerate' => $this->canRegeneratePayment($payment),
                'expires_at' => $this->getPaymentExpirationDate($payment)->toISOString(),
                'created_at' => $payment->created_at->toISOString(),
                'snap_token_created_at' => $payment->snap_token_created_at?->toISOString(),
            ];

            // Add time remaining if not expired
            if (! $expirationInfo['is_expired']) {
                $expiresAt = $this->getPaymentExpirationDate($payment);
                $expirationInfo['time_remaining'] = [
                    'total_minutes' => now()->diffInMinutes($expiresAt),
                    'hours' => floor(now()->diffInMinutes($expiresAt) / 60),
                    'minutes' => now()->diffInMinutes($expiresAt) % 60,
                    'is_near_expiry' => now()->diffInMinutes($expiresAt) < 60,
                    'human_readable' => now()->diffForHumans($expiresAt, true).' remaining',
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $expirationInfo,
                'message' => 'Expiration info retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get expiration info', [
                'payment_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get expiration info',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get tenant's expired payments.
     */
    public function getExpiredPayments(Request $request)
    {
        try {
            $user = $request->user();
            $tenant = Tenant::where('user_id', $user->id)->where('status', 'active')->first();

            if (! $tenant) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Tenant information not found',
                ], 200);
            }

            $expiredPayments = Payment::where('tenant_id', $tenant->id)
                ->where('status', 'expired')
                ->orderBy('expired_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    $payment->can_regenerate = $this->canRegeneratePayment($payment);

                    return $payment;
                });

            return response()->json([
                'success' => true,
                'data' => $expiredPayments,
                'message' => 'Expired payments retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get expired payments', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get expired payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get tenant's near expiry payments.
     */
    public function getNearExpiryPayments(Request $request)
    {
        try {
            $user = $request->user();
            $tenant = Tenant::where('user_id', $user->id)->where('status', 'active')->first();

            if (! $tenant) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Tenant information not found',
                ], 200);
            }

            $nearExpiryPayments = Payment::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->where(function ($query) {
                    // Payments that will expire in next 2 hours based on snap_token_created_at (24h expiry)
                    // Or payments that are 5-7 days old based on created_at (7-day expiry logic)
                    $query->where(function ($q) {
                        $q->whereNotNull('snap_token_created_at')
                            ->where('snap_token_created_at', '<', now()->subHours(22)) // Created more than 22h ago
                            ->where('snap_token_created_at', '>', now()->subHours(24)); // And less than 24h ago (to be near expiry)
                    })
                        ->orWhere(function ($q) {
                            $q->whereNull('snap_token_created_at') // If no snap token, rely on payment creation date
                                ->where('created_at', '<', now()->subDays(5)) // Created more than 5 days ago
                                ->where('created_at', '>', now()->subDays(7)); // And less than 7 days ago (to be near expiry)
                        });
                })
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($payment) {
                    $expiresAt = $this->getPaymentExpirationDate($payment);
                    $payment->expires_at = $expiresAt->toISOString();
                    $payment->expires_in_minutes = now()->diffInMinutes($expiresAt);
                    $payment->is_urgent = now()->diffInMinutes($expiresAt) < 60;

                    return $payment;
                });

            return response()->json([
                'success' => true,
                'data' => $nearExpiryPayments,
                'message' => 'Near expiry payments retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get near expiry payments', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get near expiry payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Manually regenerate expired payment endpoint for tenant.
     */
    public function regenerateExpiredPayment(Request $request, $id)
    {
        try {
            $user = $request->user();

            $tenant = Tenant::where('user_id', $user->id)->where('status', 'active')->first();

            if (! $tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant information not found',
                ], 404);
            }

            $payment = Payment::where('tenant_id', $tenant->id)->findOrFail($id);

            // Check if payment can be regenerated
            if (! $this->canRegeneratePayment($payment)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be regenerated',
                    'error_code' => 'PAYMENT_CANNOT_REGENERATE',
                    'data' => [
                        'status' => $payment->status,
                        'created_at' => $payment->created_at,
                        'can_regenerate' => false,
                    ],
                ], 422);
            }

            $newPayment = $this->regeneratePayment($payment);

            if (! $newPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to regenerate payment',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment regenerated successfully',
                'data' => [
                    'old_payment_id' => $payment->id,
                    'new_payment_id' => $newPayment->id,
                    'new_order_id' => $newPayment->order_id,
                    'amount' => $newPayment->amount,
                    'status' => $newPayment->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to regenerate expired payment', [
                'payment_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate payment',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get payment history (paginated)
     */
    public function history(Request $request)
    {
        try {
            $user = $request->user();

            $tenant = Tenant::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (! $tenant) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Tenant information not found',
                ]);
            }

            $query = Payment::where('tenant_id', $tenant->id);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('year') && $request->year) {
                $query->whereYear('created_at', $request->year);
            }

            if ($request->has('month') && $request->month) {
                $query->whereMonth('created_at', $request->month);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $payments = $query->orderBy('payment_month', 'desc')
                ->paginate($perPage);

            $data = [
                'data' => $payments->items(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Payment history retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment history: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment summary
     */
    public function summary(Request $request)
    {
        try {
            $user = $request->user();

            $tenant = Tenant::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (! $tenant) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'current_month' => null,
                        'next_payment' => null,
                        'total_paid' => 0,
                        'total_pending' => 0,
                        'overdue_count' => 0,
                        'payment_streak' => 0,
                        'average_payment_time' => 0,
                    ],
                    'message' => 'Tenant information not found',
                ]);
            }

            $currentMonth = Carbon::now()->format('Y-m');
            $nextMonth = Carbon::now()->addMonth()->format('Y-m');

            // Current month payment
            $currentPayment = Payment::where('tenant_id', $tenant->id)
                ->where('payment_month', $currentMonth)
                ->first();

            // Next month payment
            $nextPayment = Payment::where('tenant_id', $tenant->id)
                ->where('payment_month', $nextMonth)
                ->first();

            // Statistics
            $totalPaid = Payment::where('tenant_id', $tenant->id)
                ->where('status', 'paid')
                ->sum('amount');

            $totalPending = Payment::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->sum('amount');

            $overdueCount = Payment::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays(7))
                ->count();

            // Payment streak (consecutive months paid on time)
            $paymentStreak = $this->calculatePaymentStreak($tenant->id);

            // Average payment time (days from creation to payment)
            $avgPaymentTime = Payment::where('tenant_id', $tenant->id)
                ->where('status', 'paid')
                ->whereNotNull('paid_at')
                ->selectRaw('AVG(DATEDIFF(paid_at, created_at)) as avg_days')
                ->value('avg_days') ?? 0;

            $summary = [
                'current_month' => $currentPayment,
                'next_payment' => $nextPayment,
                'total_paid' => (float) $totalPaid,
                'total_pending' => (float) $totalPending,
                'overdue_count' => $overdueCount,
                'payment_streak' => $paymentStreak,
                'average_payment_time' => round($avgPaymentTime, 1),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Payment summary retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment summary: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate payment streak
     */
    private function calculatePaymentStreak($tenantId)
    {
        $payments = Payment::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->orderBy('payment_month', 'desc')
            ->pluck('payment_month')
            ->toArray();

        if (empty($payments)) {
            return 0;
        }

        $streak = 1;
        $currentMonth = Carbon::parse($payments[0].'-01');

        for ($i = 1; $i < count($payments); $i++) {
            $prevMonth = Carbon::parse($payments[$i].'-01');
            $expectedMonth = $currentMonth->copy()->subMonth();

            if ($prevMonth->format('Y-m') === $expectedMonth->format('Y-m')) {
                $streak++;
                $currentMonth = $prevMonth;
            } else {
                break;
            }
        }

        return $streak;
    }

    // ===================================================================
    // EXPIRED PAYMENT HANDLING METHODS (DUPLICATED FOR TENANT)
    // ===================================================================
    // Catatan: Metode-metode ini diduplikasi untuk menjaga self-containment
    // pada controller ini. Untuk aplikasi yang lebih besar, pertimbangkan
    // memindahkannya ke Service Class atau Trait.

    /**
     * Check if payment is expired.
     */
    private function isPaymentExpired(Payment $payment): bool
    {
        if (in_array($payment->status, ['paid', 'failed', 'cancelled'])) { // Added 'cancelled' to skip
            return false;
        }

        if ($payment->snap_token_created_at) {
            $tokenExpiresAt = Carbon::parse($payment->snap_token_created_at)->addHours(24);
            if (now()->gt($tokenExpiresAt)) {
                return true;
            }
        }

        $paymentExpiresAt = Carbon::parse($payment->created_at)->addDays(7);

        return now()->gt($paymentExpiresAt);
    }

    /**
     * Check if snap token specifically is expired.
     */
    private function isSnapTokenExpired(Payment $payment): bool
    {
        if (! $payment->snap_token_created_at) {
            return true; // No timestamp means we should regenerate
        }

        $tokenExpiresAt = Carbon::parse($payment->snap_token_created_at)->addHours(24);

        return now()->gt($tokenExpiresAt);
    }

    /**
     * Check if payment can be regenerated.
     */
    private function canRegeneratePayment(Payment $payment): bool
    {
        $maxAge = Carbon::parse($payment->created_at)->addDays(30); // Payments older than 30 days cannot be regenerated

        return now()->lt($maxAge) && in_array($payment->status, ['pending', 'expired']);
    }

    /**
     * Get payment expiration date.
     */
    private function getPaymentExpirationDate(Payment $payment): Carbon
    {
        if ($payment->snap_token_created_at) {
            return Carbon::parse($payment->snap_token_created_at)->addHours(24);
        }

        return Carbon::parse($payment->created_at)->addDays(7);
    }

    /**
     * Regenerate expired payment.
     */
    private function regeneratePayment(Payment $payment)
    {
        try {
            DB::beginTransaction();

            $payment->update([
                'status' => 'expired',
                'expired_at' => now(),
                'notes' => 'Payment expired and regenerated by tenant',
            ]);

            $newOrderId = MidtransService::generateOrderId($payment->tenant_id, $payment->payment_month);

            $newPayment = Payment::create([
                'order_id' => $newOrderId,
                'tenant_id' => $payment->tenant_id,
                'payment_month' => $payment->payment_month,
                'amount' => $payment->amount,
                'status' => 'pending',
                'regenerated_from' => $payment->id,
                'notes' => 'Regenerated from expired payment #'.$payment->id,
            ]);

            DB::commit();

            return $newPayment;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to regenerate payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check and update expired payments for a tenant.
     */
    private function checkAndUpdateExpiredPayments($tenantId)
    {
        try {
            $pendingPayments = Payment::where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->get();

            foreach ($pendingPayments as $payment) {
                if ($this->isPaymentExpired($payment) && ! $this->canRegeneratePayment($payment)) {
                    $payment->update([
                        'status' => 'expired',
                        'expired_at' => now(),
                        'notes' => 'Automatically marked as expired by tenant system',
                    ]);
                    Log::info('Payment automatically marked as expired for tenant', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to check expired payments for tenant', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
