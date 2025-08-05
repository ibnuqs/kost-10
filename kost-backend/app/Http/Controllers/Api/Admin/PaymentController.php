<?php

// File: app/Http/Controllers/Api/Admin/PaymentController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments with filtering and pagination
     */
    public function index(Request $request)
    {
        try {
            $perPage = min(100, max(5, (int) $request->get('per_page', 20)));
            $status = $request->get('status', 'all');
            $month = $request->get('month', '');
            $search = $request->get('search', '');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Payment::with(['tenant.user', 'tenant.room']);

            // Filter by status
            if ($status !== 'all' && in_array($status, [Payment::STATUS_PENDING, Payment::STATUS_PAID, Payment::STATUS_OVERDUE])) {
                $query->where('status', $status);
            }

            // Filter by month
            if (! empty($month)) {
                $query->where('payment_month', $month);
            }

            // Search functionality
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                        ->orWhereHas('tenant', function ($tenantQuery) use ($search) {
                            $tenantQuery->where('tenant_code', 'like', "%{$search}%")
                                ->orWhereHas('user', function ($userQuery) use ($search) {
                                    $userQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            }

            // Sorting
            $allowedSortFields = ['order_id', 'amount', 'payment_month', 'status', 'paid_at', 'created_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
            }

            $payments = $query->paginate($perPage);

            $paymentsData = $payments->getCollection()->map(function ($payment) {
                $data = $payment->getApiData();

                // Add tenant info with proper structure for frontend
                if ($payment->tenant) {
                    $data['tenant'] = [
                        'id' => $payment->tenant->id,
                        'tenant_code' => $payment->tenant->tenant_code,
                        'user_id' => $payment->tenant->user_id,
                        'room_id' => $payment->tenant->room_id,
                        'monthly_rent' => (float) $payment->tenant->monthly_rent,
                        'start_date' => $payment->tenant->start_date,
                        'status' => $payment->tenant->status,
                        'user' => $payment->tenant->user ? [
                            'id' => $payment->tenant->user->id,
                            'name' => $payment->tenant->user->name,
                            'email' => $payment->tenant->user->email,
                            'phone' => $payment->tenant->user->phone,
                        ] : null,
                        'room' => $payment->tenant->room ? [
                            'id' => $payment->tenant->room->id,
                            'room_number' => $payment->tenant->room->room_number,
                            'room_name' => $payment->tenant->room->room_name,
                        ] : null,
                    ];
                }

                return $data;
            });

            return response()->json([
                'success' => true,
                'data' => $paymentsData,
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'last_page' => $payments->lastPage(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem(),
                ],
                'filters' => [
                    'status' => $status,
                    'month' => $month,
                    'search' => $search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
                'message' => 'Payments retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch payments', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_params' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
                'data' => [],
            ], 500);
        }
    }

    /**
     * Generate payments for all active tenants for a specific month
     */
    public function generateMonthlyPayments(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_month' => 'required|date_format:Y-m',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $paymentMonth = $request->payment_month;

            // Check if payments already exist for this month
            $existingPayments = Payment::where('payment_month', $paymentMonth)->count();
            if ($existingPayments > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payments for this month already exist',
                ], 422);
            }

            // Get all active tenants
            $activeTenants = Tenant::where('status', Tenant::STATUS_ACTIVE)
                ->with(['user', 'room'])
                ->get();

            if ($activeTenants->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active tenants found',
                ], 422);
            }

            DB::beginTransaction();

            $generatedPayments = [];
            $notificationsSent = 0;
            foreach ($activeTenants as $tenant) {
                $payment = Payment::create([
                    'order_id' => $this->generateOrderId(),
                    'tenant_id' => $tenant->id,
                    'payment_month' => $paymentMonth,
                    'amount' => $tenant->monthly_rent,
                    'status' => Payment::STATUS_PENDING,
                ]);

                // Create notification for the tenant
                \App\Models\Notification::create([
                    'user_id' => $tenant->user_id,
                    'title' => 'Tagihan Bulanan Dibuat',
                    'message' => 'Tagihan sewa untuk bulan '.date('F Y', strtotime($paymentMonth.'-01')).' telah dibuat sebesar Rp '.number_format($tenant->monthly_rent, 0, ',', '.'),
                    'type' => 'payment',
                    'data' => json_encode(['payment_id' => $payment->id, 'order_id' => $payment->order_id]),
                ]);

                $notificationsSent++;
                $generatedPayments[] = $payment;
            }

            DB::commit();

            Log::info('Monthly payments generated successfully', [
                'payment_month' => $paymentMonth,
                'payments_count' => count($generatedPayments),
                'notifications_sent' => $notificationsSent,
                'generated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_month' => $paymentMonth,
                    'payments_generated' => count($generatedPayments),
                    'notifications_sent' => $notificationsSent,
                    'total_amount' => array_sum(array_column($generatedPayments, 'amount')),
                ],
                'message' => 'Monthly payments generated successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate monthly payments', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate monthly payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified payment
     */
    public function show($id)
    {
        try {
            $payment = Payment::with(['tenant.user', 'tenant.room'])->findOrFail($id);

            $paymentData = $payment->getApiData();

            // Add detailed tenant info
            if ($payment->tenant) {
                $paymentData['tenant_details'] = $payment->tenant->getApiData();
            }

            return response()->json([
                'success' => true,
                'data' => $paymentData,
                'message' => 'Payment details retrieved successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to fetch payment details', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment details',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update payment status manually (admin action)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $payment = Payment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:'.implode(',', [Payment::STATUS_PENDING, Payment::STATUS_PAID, Payment::STATUS_OVERDUE]),
                'payment_method' => 'sometimes|string|max:50',
                'notes' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $oldStatus = $payment->status;
            $newStatus = $request->status;

            $updateData = ['status' => $newStatus];

            // If marking as paid, set paid_at timestamp
            if ($newStatus === Payment::STATUS_PAID && $oldStatus !== Payment::STATUS_PAID) {
                $updateData['paid_at'] = now();
                if ($request->has('payment_method')) {
                    $updateData['payment_method'] = $request->payment_method;
                }
            }

            $payment->update($updateData);

            Log::info('Payment status updated manually', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'data' => $payment->fresh()->getApiData(),
                'message' => 'Payment status updated successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to update payment status', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment status',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Mark overdue payments
     */
    public function markOverdue(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cutoff_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $cutoffDate = $request->cutoff_date;

            $overduePayments = Payment::where('status', Payment::STATUS_PENDING)
                ->where('created_at', '<', $cutoffDate)
                ->update(['status' => Payment::STATUS_OVERDUE]);

            Log::info('Payments marked as overdue', [
                'cutoff_date' => $cutoffDate,
                'affected_payments' => $overduePayments,
                'marked_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'cutoff_date' => $cutoffDate,
                    'payments_marked_overdue' => $overduePayments,
                ],
                'message' => 'Overdue payments marked successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to mark overdue payments', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark overdue payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function stats(Request $request)
    {
        try {
            $month = $request->get('month', ''); // Default to empty string for all months

            $query = Payment::query();
            if (! empty($month)) {
                $query->where('payment_month', $month);
            }

            $stats = [
                'current_month' => [
                    'month' => empty($month) ? 'all' : $month,
                    'total_payments' => (clone $query)->count(),
                    'paid_payments' => (clone $query)->where('status', Payment::STATUS_PAID)->count(),
                    'pending_payments' => (clone $query)->where('status', Payment::STATUS_PENDING)->count(),
                    'overdue_payments' => (clone $query)->where('status', Payment::STATUS_OVERDUE)->count(),
                    'total_amount' => (float) (clone $query)->sum('amount'),
                    'paid_amount' => (float) (clone $query)->where('status', Payment::STATUS_PAID)->sum('amount'),
                    'pending_amount' => (float) (clone $query)->where('status', Payment::STATUS_PENDING)->sum('amount'),
                    'overdue_amount' => (float) (clone $query)->where('status', Payment::STATUS_OVERDUE)->sum('amount'),
                ],
                'overall' => [
                    'total_payments' => Payment::count(),
                    'collection_rate' => $this->calculateCollectionRate($month),
                    'average_payment' => (float) Payment::where('status', Payment::STATUS_PAID)->avg('amount'),
                    'payment_methods' => $this->getPaymentMethodStats(),
                ],
                'recent_payments' => $this->getRecentPayments(10),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Payment statistics retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch payment statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment statistics',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate unique order ID
     */
    private function generateOrderId(): string
    {
        do {
            $orderId = 'PAY'.now()->format('ymd').strtoupper(Str::random(6));
        } while (Payment::where('order_id', $orderId)->exists());

        return $orderId;
    }

    /**
     * Calculate collection rate for a specific month
     */
    private function calculateCollectionRate(string $month): float
    {
        $totalPayments = Payment::where('payment_month', $month)->count();
        $paidPayments = Payment::where('payment_month', $month)
            ->where('status', Payment::STATUS_PAID)
            ->count();

        return $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100, 2) : 0.0;
    }

    /**
     * Get payment method statistics
     */
    private function getPaymentMethodStats(): array
    {
        return Payment::where('status', Payment::STATUS_PAID)
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->payment_method,
                    'count' => $item->count,
                    'total_amount' => (float) $item->total_amount,
                ];
            })
            ->toArray();
    }

    /**
     * Get stuck payments (payments pending for too long)
     */
    public function getStuckPayments(Request $request)
    {
        try {
            $hoursThreshold = $request->get('hours_threshold', 6);
            $cutoffTime = now()->subHours($hoursThreshold);

            $stuckPayments = Payment::with(['tenant.user', 'tenant.room'])
                ->where('status', Payment::STATUS_PENDING)
                ->where('created_at', '<', $cutoffTime)
                ->get()
                ->map(function ($payment) {
                    $data = $payment->getApiData();
                    $data['stuck_duration'] = now()->diffInHours($payment->created_at);
                    $data['last_sync_attempt'] = $payment->updated_at->format('c');
                    $data['auto_sync_failed'] = now()->diffInHours($payment->updated_at) > 1;

                    if ($payment->tenant) {
                        $data['tenant'] = [
                            'id' => $payment->tenant->id,
                            'user_id' => $payment->tenant->user_id,
                            'room_id' => $payment->tenant->room_id,
                            'monthly_rent' => $payment->tenant->monthly_rent,
                            'start_date' => $payment->tenant->start_date,
                            'status' => $payment->tenant->status,
                            'user' => [
                                'id' => $payment->tenant->user->id,
                                'name' => $payment->tenant->user->name,
                                'email' => $payment->tenant->user->email,
                            ],
                        ];
                    }

                    return $data;
                });

            return response()->json([
                'success' => true,
                'data' => $stuckPayments,
                'message' => 'Stuck payments retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch stuck payments', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stuck payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Sync payment status with payment gateway
     */
    public function syncPaymentStatus(Request $request, $id)
    {
        try {
            $payment = Payment::findOrFail($id);

            // Here you would implement actual sync with Midtrans
            // For now, we'll just update the updated_at timestamp
            $payment->touch();

            Log::info('Payment status synced', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'synced_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $payment->fresh()->getApiData(),
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
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync payment status',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Manual override payment status with reason
     */
    public function manualOverride(Request $request, $id)
    {
        try {
            $payment = Payment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:'.implode(',', [Payment::STATUS_PENDING, Payment::STATUS_PAID, Payment::STATUS_OVERDUE]),
                'reason' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $oldStatus = $payment->status;
            $newStatus = $request->status;
            $reason = $request->reason;

            $updateData = ['status' => $newStatus];

            if ($newStatus === Payment::STATUS_PAID && $oldStatus !== Payment::STATUS_PAID) {
                $updateData['paid_at'] = now();
                $updateData['payment_method'] = 'manual_override';
            }

            $payment->update($updateData);

            Log::info('Payment status manually overridden', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'overridden_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $payment->fresh()->getApiData(),
                'message' => 'Payment status overridden successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to override payment status', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to override payment status',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Bulk sync payment statuses
     */
    public function bulkSyncPayments(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_ids' => 'sometimes|array',
                'payment_ids.*' => 'integer|exists:payments,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $query = Payment::query();

            if ($request->has('payment_ids')) {
                $query->whereIn('id', $request->payment_ids);
            } else {
                // Sync all pending payments if no specific IDs provided
                $query->where('status', Payment::STATUS_PENDING);
            }

            $payments = $query->get();
            $syncedCount = 0;

            foreach ($payments as $payment) {
                // Here you would implement actual sync with Midtrans
                // For now, we'll just update the updated_at timestamp
                $payment->touch();
                $syncedCount++;
            }

            Log::info('Bulk payment sync completed', [
                'synced_count' => $syncedCount,
                'payment_ids' => $request->payment_ids ?? 'all_pending',
                'synced_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'synced_count' => $syncedCount,
                    'total_payments' => $payments->count(),
                ],
                'message' => 'Bulk sync completed successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to bulk sync payments', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk sync payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Pre-check before generating monthly payments
     */
    public function preCheckGenerate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'month' => 'required|date_format:Y-m',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $month = $request->month;

            // Check active tenants
            $activeTenantsCount = Tenant::where('status', Tenant::STATUS_ACTIVE)->count();

            // Check for duplicate payments
            $duplicatePayments = Payment::where('payment_month', $month)->count();

            // Check for invalid tenant data
            $invalidTenants = Tenant::where('status', Tenant::STATUS_ACTIVE)
                ->whereNull('monthly_rent')
                ->orWhereNull('user_id')
                ->count();

            $warnings = [];
            $errors = [];

            if ($duplicatePayments > 0) {
                $warnings[] = "$duplicatePayments pembayaran sudah ada untuk bulan $month";
            }

            if ($invalidTenants > 0) {
                $errors[] = "$invalidTenants tenant memiliki data tidak valid (monthly_rent atau user_id kosong)";
            }

            if ($activeTenantsCount === 0) {
                $errors[] = 'Tidak ada tenant aktif untuk generate pembayaran';
            }

            $valid = empty($errors) && $duplicatePayments === 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'valid' => $valid,
                    'activeTenantsCount' => $activeTenantsCount,
                    'duplicatePayments' => $duplicatePayments,
                    'invalidData' => $invalidTenants,
                    'warnings' => $warnings,
                    'errors' => $errors,
                ],
                'message' => 'Pre-check completed successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to perform pre-check', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform pre-check',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export payments to CSV
     */
    public function exportPayments(Request $request)
    {
        try {
            $filters = $request->all();
            $query = Payment::with(['tenant.user', 'tenant.room']);

            // Apply same filters as index method
            if (isset($filters['status']) && $filters['status'] !== 'all') {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['month']) && ! empty($filters['month'])) {
                $query->where('payment_month', $filters['month']);
            }

            if (isset($filters['search']) && ! empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                        ->orWhereHas('tenant', function ($tenantQuery) use ($search) {
                            $tenantQuery->where('tenant_code', 'like', "%{$search}%")
                                ->orWhereHas('user', function ($userQuery) use ($search) {
                                    $userQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            }

            $payments = $query->orderBy('created_at', 'desc')->get();

            // Create CSV content
            $csvContent = "ID,Order ID,Tenant,Amount,Status,Payment Month,Paid At\n";

            foreach ($payments as $payment) {
                $tenantName = $payment->tenant && $payment->tenant->user ? $payment->tenant->user->name : 'N/A';
                $paidAt = $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : '';

                $csvContent .= implode(',', [
                    $payment->id,
                    $payment->order_id,
                    '"'.str_replace('"', '""', $tenantName).'"',
                    $payment->amount,
                    $payment->status,
                    $payment->payment_month,
                    $paidAt,
                ])."\n";
            }

            Log::info('Payments exported', [
                'total_exported' => $payments->count(),
                'filters' => $filters,
                'exported_by' => Auth::id(),
            ]);

            return response($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="payments-export-'.now()->format('Y-m-d').'.csv"',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to export payments', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate individual payment for specific tenant
     */
    public function generateIndividualPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tenant_id' => 'required|integer|exists:tenants,id',
                'payment_month' => 'required|date_format:Y-m',
                'prorate_from_date' => 'sometimes|date',
                'send_notification' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tenant = Tenant::with(['user', 'room'])->findOrFail($request->tenant_id);

            // Check if tenant is active
            if ($tenant->status !== Tenant::STATUS_ACTIVE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant is not active',
                ], 422);
            }

            // Check if payment already exists for this month
            $existingPayment = Payment::where('tenant_id', $tenant->id)
                ->where('payment_month', $request->payment_month)
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already exists for this tenant and month',
                    'data' => $existingPayment->getApiData(),
                ], 422);
            }

            // Calculate payment amount (with prorating if specified)
            $amount = $tenant->monthly_rent;
            $description = 'Sewa Kamar Bulan '.date('F Y', strtotime($request->payment_month.'-01'));

            if ($request->has('prorate_from_date')) {
                $prorateDate = \Carbon\Carbon::parse($request->prorate_from_date);
                $monthStart = \Carbon\Carbon::parse($request->payment_month.'-01');
                $monthEnd = $monthStart->copy()->endOfMonth();

                $daysInMonth = $monthStart->daysInMonth;
                $remainingDays = $daysInMonth - $prorateDate->day + 1;

                $dailyRate = $tenant->monthly_rent / $daysInMonth;
                $amount = round($dailyRate * $remainingDays, 2);

                $description .= " (Prorata {$remainingDays} hari)";
            }

            // Generate unique order ID
            $orderId = $this->generateOrderId();

            // Create payment
            $payment = Payment::create([
                'order_id' => $orderId,
                'tenant_id' => $tenant->id,
                'payment_month' => $request->payment_month,
                'amount' => $amount,
                'status' => Payment::STATUS_PENDING,
                'description' => $description,
                'generation_type' => 'manual',
                'generated_by_user_id' => Auth::id(),
            ]);

            // Send notification if requested
            if ($request->get('send_notification', true)) {
                // Create in-app notification
                \App\Models\Notification::create([
                    'user_id' => $tenant->user_id,
                    'title' => 'Tagihan Baru',
                    'message' => 'Tagihan pembayaran sewa untuk bulan '.date('F Y', strtotime($request->payment_month.'-01')).' telah dibuat. Jumlah: Rp '.number_format($amount, 0, ',', '.'),
                    'type' => 'payment',
                    'data' => json_encode([
                        'payment_id' => $payment->id,
                        'order_id' => $orderId,
                        'amount' => $amount,
                    ]),
                ]);

                // TODO: Add email notification here if needed
                // Mail::to($tenant->user->email)->send(new PaymentGeneratedMail($payment));
            }

            Log::info('Individual payment generated', [
                'payment_id' => $payment->id,
                'tenant_id' => $tenant->id,
                'order_id' => $orderId,
                'amount' => $amount,
                'payment_month' => $request->payment_month,
                'generated_by' => Auth::id(),
                'prorated' => $request->has('prorate_from_date'),
            ]);

            return response()->json([
                'success' => true,
                'data' => $payment->fresh()->getApiData(),
                'message' => 'Payment generated successfully',
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to generate individual payment', [
                'tenant_id' => $request->tenant_id ?? null,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payment',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Regenerate expired payment
     */
    public function regenerateExpiredPayment(Request $request, $id)
    {
        try {
            $payment = Payment::findOrFail($id);

            // Check if payment can be regenerated
            if (! $payment->canRegenerate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be regenerated',
                    'data' => [
                        'current_status' => $payment->status,
                        'is_expired' => $payment->isExpired(),
                    ],
                ], 422);
            }

            DB::beginTransaction();

            // Mark old payment as cancelled
            $payment->update([
                'status' => Payment::STATUS_CANCELLED,
                'notes' => 'Cancelled due to regeneration by admin on '.now()->format('Y-m-d H:i:s'),
            ]);

            // Create new payment
            $newPayment = Payment::create([
                'order_id' => $this->generateOrderId(),
                'tenant_id' => $payment->tenant_id,
                'payment_month' => $payment->payment_month,
                'amount' => $payment->amount,
                'status' => Payment::STATUS_PENDING,
                'notes' => 'Regenerated from expired payment #'.$payment->id,
                'regenerated_from' => $payment->id, // <-- This line is added
            ]);

            DB::commit();

            Log::info('Payment regenerated successfully', [
                'old_payment_id' => $payment->id,
                'new_payment_id' => $newPayment->id,
                'regenerated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'old_payment' => $payment->fresh()->getApiData(),
                    'new_payment' => $newPayment->fresh()->getApiData(),
                ],
                'message' => 'Payment regenerated successfully',
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to regenerate expired payment', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate payment',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get expired payments list
     */
    public function getExpiredPayments(Request $request)
    {
        try {
            $perPage = min(50, max(5, (int) $request->get('per_page', 15)));

            $query = Payment::with(['tenant.user', 'tenant.room'])
                ->where(function ($q) {
                    $q->where('status', Payment::STATUS_EXPIRED)
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('status', Payment::STATUS_PENDING)
                                ->where(function ($expiredQuery) {
                                    // Snap token expired (24 hours)
                                    $expiredQuery->where('snap_token_created_at', '<', now()->subHours(24))
                                                // OR payment is old (7 days)
                                        ->orWhere('created_at', '<', now()->subDays(7));
                                });
                        });
                });

            $expiredPayments = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $paymentsData = $expiredPayments->getCollection()->map(function ($payment) {
                $data = $payment->getApiData();

                // Add tenant info with proper structure for frontend
                if ($payment->tenant) {
                    $data['tenant'] = [
                        'id' => $payment->tenant->id,
                        'tenant_code' => $payment->tenant->tenant_code,
                        'user_id' => $payment->tenant->user_id,
                        'room_id' => $payment->tenant->room_id,
                        'monthly_rent' => (float) $payment->tenant->monthly_rent,
                        'start_date' => $payment->tenant->start_date,
                        'status' => $payment->tenant->status,
                        'user' => $payment->tenant->user ? [
                            'id' => $payment->tenant->user->id,
                            'name' => $payment->tenant->user->name,
                            'email' => $payment->tenant->user->email,
                            'phone' => $payment->tenant->user->phone,
                        ] : null,
                        'room' => $payment->tenant->room ? [
                            'id' => $payment->tenant->room->id,
                            'room_number' => $payment->tenant->room->room_number,
                            'room_name' => $payment->tenant->room->room_name,
                        ] : null,
                    ];
                }

                return $data;
            });

            return response()->json([
                'success' => true,
                'data' => $paymentsData,
                'pagination' => [
                    'current_page' => $expiredPayments->currentPage(),
                    'per_page' => $expiredPayments->perPage(),
                    'total' => $expiredPayments->total(),
                    'last_page' => $expiredPayments->lastPage(),
                    'from' => $expiredPayments->firstItem(),
                    'to' => $expiredPayments->lastItem(),
                ],
                'message' => 'Expired payments retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch expired payments', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expired payments',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get recent successful payments
     */
    private function getRecentPayments(int $limit): array
    {
        return Payment::with(['tenant.user'])
            ->where('status', Payment::STATUS_PAID)
            ->latest('paid_at')
            ->limit($limit)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => (float) $payment->amount,
                    'payment_month' => $payment->payment_month,
                    'paid_at' => $payment->paid_at ? $payment->paid_at->format('c') : null,
                    'tenant_name' => $payment->tenant && $payment->tenant->user ? $payment->tenant->user->name : 'N/A',
                ];
            })
            ->toArray();
    }
}
