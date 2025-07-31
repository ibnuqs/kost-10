<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DoorController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Handle CORS preflight requests explicitly
Route::options('/{any}', function () {
    return response('', 200);
})->where('any', '.*');

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()->format('c'),
        'version' => '1.0.0',
    ]);
});

// Quick test endpoints (without auth)
Route::get('/test-rooms', function () {
    try {
        $rooms = \App\Models\Room::take(5)->get();

        return response()->json([
            'success' => true,
            'rooms_count' => $rooms->count(),
            'sample_rooms' => $rooms->map(function ($room) {
                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'room_name' => $room->room_name,
                    'status' => $room->status,
                ];
            }),
            'message' => 'Rooms API test successful',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Rooms API test failed',
        ], 500);
    }
});

// Test room deletion endpoint (without auth for testing)
Route::get('/test-room-deletion/{id}', function ($id) {
    try {
        $room = \App\Models\Room::findOrFail($id);

        // Check for related data that might prevent deletion
        $iotDevices = \DB::table('iot_devices')->where('room_id', $room->id)->get();
        $rfidCards = 0;
        $accessLogs = 0;

        try {
            if (\Schema::hasTable('rfid_cards')) {
                $rfidCards = \DB::table('rfid_cards')->where('room_id', $room->id)->count();
            }
        } catch (\Exception $e) {
        }

        try {
            if (\Schema::hasTable('access_logs')) {
                $accessLogs = \DB::table('access_logs')->where('room_id', $room->id)->count();
            }
        } catch (\Exception $e) {
        }

        $canDelete = $room->tenants()->where('status', \App\Models\Tenant::STATUS_ACTIVE)->count() === 0 && $iotDevices->count() === 0;

        return response()->json([
            'success' => true,
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'room_name' => $room->room_name,
            'status' => $room->status,
            'has_tenants' => $room->tenants()->count(),
            'active_tenants' => $room->tenants()->where('status', \App\Models\Tenant::STATUS_ACTIVE)->count(),
            'iot_devices' => $iotDevices->map(function ($device) {
                return [
                    'id' => $device->id,
                    'device_id' => $device->device_id ?? 'N/A',
                    'device_name' => $device->device_name ?? 'N/A',
                    'device_type' => $device->device_type ?? 'N/A',
                    'status' => $device->status ?? 'N/A',
                ];
            }),
            'iot_devices_count' => $iotDevices->count(),
            'rfid_cards_count' => $rfidCards,
            'access_logs_count' => $accessLogs,
            'can_delete' => $canDelete,
            'blocking_factors' => [
                'active_tenants' => $room->tenants()->where('status', \App\Models\Tenant::STATUS_ACTIVE)->count() > 0,
                'iot_devices' => $iotDevices->count() > 0,
                'rfid_cards' => $rfidCards > 0,
                'access_logs' => $accessLogs > 0,
            ],
            'message' => 'Room deletion test completed (no auth required)',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Error during room test',
        ], 500);
    }
});

// Test actual room deletion (without auth for testing)
Route::delete('/test-room-deletion/{id}', function ($id) {
    try {
        $room = \App\Models\Room::findOrFail($id);

        // Check if room has active tenants using the same logic as our fixed controller
        $activeTenantCount = $room->tenants()->where('status', \App\Models\Tenant::STATUS_ACTIVE)->count();
        if ($activeTenantCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete room with active tenants. Please remove tenants first.',
                'error_type' => 'active_tenants',
            ], 422);
        }

        // Check for IoT devices linked to this room
        $iotDevicesCount = \DB::table('iot_devices')->where('room_id', $room->id)->count();
        if ($iotDevicesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete room with {$iotDevicesCount} IoT device(s) assigned. Please unassign or delete the IoT devices first.",
                'error_type' => 'has_iot_devices',
                'iot_devices_count' => $iotDevicesCount,
                'suggestions' => [
                    'Go to IoT Device Management',
                    'Unassign devices from this room',
                    'Or delete the IoT devices first',
                ],
            ], 422);
        }

        // Check for other related data
        $relatedData = [
            'inactive_tenants' => $room->tenants()->where('status', '!=', \App\Models\Tenant::STATUS_ACTIVE)->count(),
            'rfid_cards' => 0,
            'access_logs' => 0,
        ];

        // Check for RFID cards (if table exists)
        try {
            if (\Schema::hasTable('rfid_cards')) {
                $relatedData['rfid_cards'] = \DB::table('rfid_cards')->where('room_id', $room->id)->count();
            }
        } catch (\Exception $e) {
            // Table doesn't exist, skip
        }

        // Check for access logs (if table exists)
        try {
            if (\Schema::hasTable('access_logs')) {
                $relatedData['access_logs'] = \DB::table('access_logs')->where('room_id', $room->id)->count();
            }
        } catch (\Exception $e) {
            // Table doesn't exist, skip
        }

        // If there are other related records, inform the user
        $hasOtherData = ($relatedData['inactive_tenants'] + $relatedData['rfid_cards'] + $relatedData['access_logs']) > 0;
        if ($hasOtherData) {
            return response()->json([
                'success' => false,
                'message' => 'Room has historical data. This may cause issues during deletion.',
                'error_type' => 'has_history',
                'related_data' => $relatedData,
                'warning' => 'You can still try to delete, but it may fail due to database constraints.',
                'action' => 'Continue with deletion anyway?',
            ], 422);
        }

        // Start transaction for safe deletion
        \DB::beginTransaction();

        try {
            $roomNumber = $room->room_number;
            $roomId = $room->id;

            // Delete the room
            $room->delete();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Room {$roomNumber} deleted successfully",
                'deleted_room' => [
                    'id' => $roomId,
                    'room_number' => $roomNumber,
                ],
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Room not found',
        ], 404);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete room: '.$e->getMessage(),
            'error_type' => 'general_error',
        ], 500);
    }
});

// Test archive room (alternative to deletion)
Route::post('/test-room-archive/{id}', function ($id) {
    try {
        $room = \App\Models\Room::findOrFail($id);

        // Check if room can be archived (no active tenants)
        $activeTenantCount = $room->tenants()->where('status', \App\Models\Tenant::STATUS_ACTIVE)->count();
        if ($activeTenantCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot archive room with active tenants. Please remove tenants first.',
                'error_type' => 'active_tenants',
            ], 422);
        }

        $roomNumber = $room->room_number;
        // Temporary: Use 'maintenance' instead of 'archived' due to enum limitation
        $room->update(['status' => \App\Models\Room::STATUS_MAINTENANCE]);

        return response()->json([
            'success' => true,
            'message' => "Room {$roomNumber} archived successfully (test mode)",
            'archived_room' => [
                'id' => $room->id,
                'room_number' => $roomNumber,
                'status' => $room->status,
            ],
            'note' => 'Room archived instead of deleted - data preserved!',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Error during room archiving test',
        ], 500);
    }
});

// Receipt verification routes (public)
Route::prefix('receipt')->group(function () {
    Route::get('/verify/{receiptNumber}', [App\Http\Controllers\Api\ReceiptVerificationController::class, 'verify']);
});

// Webhook routes (public - no auth required for Midtrans callbacks)
Route::prefix('webhook')->group(function () {
    Route::post('/midtrans', [App\Http\Controllers\Api\Webhook\WebhookController::class, 'midtransWebhook']);
    Route::post('/payment', [App\Http\Controllers\Api\Webhook\WebhookController::class, 'paymentWebhook']);
    Route::post('/test', [App\Http\Controllers\Api\Webhook\WebhookController::class, 'testWebhook']);
});

// Legacy webhook endpoints for backward compatibility
Route::post('/midtrans-webhook', [App\Http\Controllers\Api\Webhook\WebhookController::class, 'midtransWebhook']);
Route::post('/payment-webhook', [App\Http\Controllers\Api\Webhook\WebhookController::class, 'paymentWebhook']);

// Authentication routes
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        // Stats endpoint
        Route::get('/stats', [DashboardController::class, 'getStats']);

        // 🚀 FIXED: Updated routes to match frontend calls
        Route::get('/activities', [DashboardController::class, 'recentActivities']);
        Route::get('/health', [DashboardController::class, 'systemHealth']);

        // Analytics endpoints
        Route::get('/analytics', [DashboardController::class, 'revenueAnalytics']);
        Route::get('/revenue-analytics', [DashboardController::class, 'revenueAnalytics']);

        // 🆕 NEW: Missing endpoints that frontend needs
        Route::get('/payment-trends', [DashboardController::class, 'paymentTrends']);
        Route::get('/access-history', [DashboardController::class, 'accessHistory']);

        // Additional endpoints
        Route::get('/iot-device-status', [DashboardController::class, 'getIoTDeviceStatus']);
        Route::get('/occupancy-trends', [DashboardController::class, 'getOccupancyTrends']);
        Route::get('/payment-status-summary', [DashboardController::class, 'getPaymentStatusSummary']);

        // Legacy routes (keep for backward compatibility)
        Route::get('/recent-activities', [DashboardController::class, 'recentActivities']);
        Route::get('/system-health', [DashboardController::class, 'systemHealth']);
    });

    // 🆕 NEW: Admin resource management routes
    // Rooms management
    Route::prefix('rooms')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Admin\RoomController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\Admin\RoomController::class, 'store']);
        Route::get('/stats', [App\Http\Controllers\Api\Admin\RoomController::class, 'stats']);
        Route::get('/{id}', [App\Http\Controllers\Api\Admin\RoomController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\Admin\RoomController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\Admin\RoomController::class, 'destroy']);
        Route::post('/{id}/archive', [App\Http\Controllers\Api\Admin\RoomController::class, 'archive']);
        Route::get('/{id}/test-deletion', [App\Http\Controllers\Api\Admin\RoomController::class, 'testDeletion']);
        Route::post('/{id}/assign-tenant', [App\Http\Controllers\Api\Admin\RoomController::class, 'assignTenant']);
        // ✅ FIXED: Add missing remove-tenant route
        Route::delete('/{id}/remove-tenant', [App\Http\Controllers\Api\Admin\RoomController::class, 'removeTenant']);
    });

    // Tenants management
    Route::prefix('tenants')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Admin\TenantController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\Admin\TenantController::class, 'store']);
        Route::get('/stats', [App\Http\Controllers\Api\Admin\TenantController::class, 'stats']);
        Route::get('/available', [App\Http\Controllers\Api\Admin\RoomController::class, 'getAvailableTenants']);
        Route::get('/{id}', [App\Http\Controllers\Api\Admin\TenantController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\Admin\TenantController::class, 'update']);
        Route::post('/{id}/move-out', [App\Http\Controllers\Api\Admin\TenantController::class, 'moveOut']);
    });

    // Payments management
    Route::prefix('payments')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Admin\PaymentController::class, 'index']);
        Route::get('/stats', [App\Http\Controllers\Api\Admin\PaymentController::class, 'stats']);
        Route::get('/stuck', [App\Http\Controllers\Api\Admin\PaymentController::class, 'getStuckPayments']);
        Route::get('/pre-check-generate', [App\Http\Controllers\Api\Admin\PaymentController::class, 'preCheckGenerate']);
        Route::get('/export', [App\Http\Controllers\Api\Admin\PaymentController::class, 'exportPayments']);
        Route::post('/generate-monthly', [App\Http\Controllers\Api\Admin\PaymentController::class, 'generateMonthlyPayments']);
        Route::post('/generate-individual', [App\Http\Controllers\Api\Admin\PaymentController::class, 'generateIndividualPayment']);
        Route::post('/mark-overdue', [App\Http\Controllers\Api\Admin\PaymentController::class, 'markOverdue']);
        Route::post('/bulk-sync', [App\Http\Controllers\Api\Admin\PaymentController::class, 'bulkSyncPayments']);

        // Expired payment handling
        Route::get('/expired', [App\Http\Controllers\Api\Admin\PaymentController::class, 'getExpiredPayments']);
        Route::post('/{id}/regenerate', [App\Http\Controllers\Api\Admin\PaymentController::class, 'regenerateExpiredPayment']);

        Route::get('/{id}', [App\Http\Controllers\Api\Admin\PaymentController::class, 'show']);
        Route::put('/{id}/status', [App\Http\Controllers\Api\Admin\PaymentController::class, 'updateStatus']);
        Route::post('/{id}/sync-status', [App\Http\Controllers\Api\Admin\PaymentController::class, 'syncPaymentStatus']);
        Route::post('/{id}/manual-override', [App\Http\Controllers\Api\Admin\PaymentController::class, 'manualOverride']);
    });

    // RFID management
    Route::prefix('rfid')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Admin\RfidController::class, 'index']);
        Route::get('/cards', [App\Http\Controllers\Api\Admin\RfidController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\Admin\RfidController::class, 'store']);
        Route::post('/cards', [App\Http\Controllers\Api\Admin\RfidController::class, 'store']); // Add missing /cards POST endpoint
        Route::post('/register-card', [App\Http\Controllers\Api\Admin\RfidController::class, 'store']);
        Route::put('/cards/{id}', [App\Http\Controllers\Api\Admin\RfidController::class, 'update']);
        Route::delete('/cards/{id}', [App\Http\Controllers\Api\Admin\RfidController::class, 'destroy']);
        Route::get('/stats', [App\Http\Controllers\Api\Admin\RfidController::class, 'stats']);
        Route::post('/scan', [App\Http\Controllers\Api\Admin\RfidController::class, 'scan']);
        Route::get('/check-card/{uid}', [App\Http\Controllers\Api\Admin\RfidController::class, 'checkCardExists']);

        // RFID Scanner Control endpoints
        Route::post('/scanner/start', [App\Http\Controllers\Api\Admin\RfidController::class, 'startScannerMode']);
        Route::post('/scanner/stop', [App\Http\Controllers\Api\Admin\RfidController::class, 'stopScannerMode']);

        // Get data for form dropdowns
        Route::get('/available-users', [App\Http\Controllers\Api\Admin\RfidController::class, 'getAvailableUsers']);
        Route::get('/available-rooms', [App\Http\Controllers\Api\Admin\RfidController::class, 'getAvailableRooms']);

        Route::get('/{id}', [App\Http\Controllers\Api\Admin\RfidController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\Admin\RfidController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\Admin\RfidController::class, 'destroy']);
        Route::post('/{id}/assign-user', [App\Http\Controllers\Api\Admin\RfidController::class, 'assignToUser']);
        Route::delete('/{id}/unassign-user', [App\Http\Controllers\Api\Admin\RfidController::class, 'unassignFromUser']);
        Route::put('/{id}/toggle-status', [App\Http\Controllers\Api\Admin\RfidController::class, 'toggleStatus']);
    });

    // RFID Cards (for ESP32 service compatibility)
    Route::prefix('rfid-cards')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Admin\RfidController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\Admin\RfidController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\Admin\RfidController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\Admin\RfidController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\Admin\RfidController::class, 'destroy']);
    });

    // IoT devices management
    Route::prefix('iot-devices')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'store']);
        Route::get('/stats', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'stats']);
        Route::get('/offline', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'getOfflineDevices']);
        Route::put('/bulk-status', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'bulkUpdateStatus']);

        // NEW: Room assignment endpoints
        Route::post('/auto-assign-rooms', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'autoAssignRooms']);
        Route::post('/{id}/assign-room', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'assignToRoom']);

        Route::get('/{id}', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'destroy']);
        Route::post('/{id}/command', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'sendCommand']);
        Route::post('/{deviceId}/heartbeat', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'heartbeat']);
    });

    // Access logs management
    Route::prefix('access-logs')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Admin\AccessLogController::class, 'index']);
        Route::get('/stats', [App\Http\Controllers\Api\Admin\AccessLogController::class, 'stats']);
        Route::get('/statistics', [App\Http\Controllers\Api\Admin\AccessLogController::class, 'statistics']);
        Route::get('/export', [App\Http\Controllers\Api\Admin\AccessLogController::class, 'export']);
        Route::get('/analytics', [App\Http\Controllers\Api\Admin\AccessLogController::class, 'accessAnalytics']);
        Route::get('/suspicious', [App\Http\Controllers\Api\Admin\AccessLogController::class, 'suspiciousActivity']);
        Route::get('/real-time', [App\Http\Controllers\Api\Admin\AccessLogController::class, 'realTimeMonitoring']);
        Route::get('/{id}', [App\Http\Controllers\Api\Admin\AccessLogController::class, 'show']);
    });

    // Door control management (ESP32 integration)
    Route::prefix('door-control')->group(function () {
        Route::get('/', [DoorController::class, 'index']);
        Route::post('/command', [DoorController::class, 'sendCommand']);
        Route::get('/device/{deviceId}/status', [DoorController::class, 'getStatus']);
        Route::get('/access-logs', [DoorController::class, 'getAccessLogs']);
        Route::post('/test-connection', [DoorController::class, 'testConnection']);
    });

    // NEW: Room Reservation Management
    Route::prefix('rooms')->group(function () {
        Route::post('/{id}/reserve', [App\Http\Controllers\Api\Admin\RoomController::class, 'reserve']);
        Route::delete('/{id}/cancel-reservation', [App\Http\Controllers\Api\Admin\RoomController::class, 'cancelReservation']);
        Route::post('/{id}/confirm-reservation', [App\Http\Controllers\Api\Admin\RoomController::class, 'confirmReservation']);
        Route::post('/{id}/assign-tenant-enhanced', [App\Http\Controllers\Api\Admin\RoomController::class, 'assignTenantEnhanced']);
        Route::post('/{id}/unarchive', [App\Http\Controllers\Api\Admin\RoomController::class, 'unarchive']);
        Route::get('/archived', [App\Http\Controllers\Api\Admin\RoomController::class, 'archived']);
    });

    // NEW: Payment & System Management
    Route::prefix('system')->group(function () {
        Route::post('/generate-monthly-payments', [App\Http\Controllers\Api\Admin\PaymentManagementController::class, 'generateMonthlyPayments']);
        Route::post('/process-payment-status', [App\Http\Controllers\Api\Admin\PaymentManagementController::class, 'processPaymentStatus']);
        Route::post('/update-tenant-access/{tenantId}', [App\Http\Controllers\Api\Admin\PaymentManagementController::class, 'updateTenantAccess']);
        Route::post('/update-all-tenants-access', [App\Http\Controllers\Api\Admin\PaymentManagementController::class, 'updateAllTenantsAccess']);
        Route::post('/cleanup-expired-reservations', [App\Http\Controllers\Api\Admin\PaymentManagementController::class, 'cleanupExpiredReservations']);
        Route::get('/health', [App\Http\Controllers\Api\Admin\PaymentManagementController::class, 'getSystemHealth']);
    });
});

// Tenant routes
Route::middleware(['auth:sanctum', 'role:tenant'])->prefix('tenant')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\Api\Tenant\DashboardController::class, 'tenantDashboard']);
    Route::get('/debug-dashboard', [App\Http\Controllers\Api\Tenant\DashboardController::class, 'debugTenantDashboard']);

    // Debug access logs
    Route::get('/debug-access-logs', function () {
        $user = Auth::user();
        $tenant = \App\Models\Tenant::where('user_id', $user->id)->first();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'user_id' => $tenant->user_id,
                'room_id' => $tenant->room_id,
                'status' => $tenant->status,
                'room' => $tenant->room ? [
                    'id' => $tenant->room->id,
                    'room_number' => $tenant->room->room_number,
                ] : null,
            ] : null,
            'access_logs_by_user' => \App\Models\AccessLog::where('user_id', $user->id)->count(),
            'access_logs_by_room' => $tenant ? \App\Models\AccessLog::where('room_id', $tenant->room_id)->count() : 0,
            'sample_user_logs' => \App\Models\AccessLog::where('user_id', $user->id)
                ->with('room')
                ->take(5)
                ->get(['id', 'user_id', 'room_id', 'access_granted', 'accessed_at', 'reason'])
                ->toArray(),
            'sample_room_logs' => $tenant ? \App\Models\AccessLog::where('room_id', $tenant->room_id)
                ->with('room')
                ->take(5)
                ->get(['id', 'user_id', 'room_id', 'access_granted', 'accessed_at', 'reason'])
                ->toArray() : [],
            'all_access_logs_sample' => \App\Models\AccessLog::take(10)->get(['id', 'user_id', 'room_id', 'access_granted', 'accessed_at'])->toArray(),
        ]);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Tenant\PaymentController::class, 'index']);
        Route::get('/history', [App\Http\Controllers\Api\Tenant\PaymentController::class, 'history']);
        Route::get('/summary', [App\Http\Controllers\Api\Tenant\PaymentController::class, 'summary']);
        Route::get('/{id}/payment-url', [App\Http\Controllers\Api\Tenant\PaymentController::class, 'getPaymentUrl']);
        Route::get('/{id}/status', [App\Http\Controllers\Api\Tenant\PaymentController::class, 'checkStatus']);
        Route::post('/{id}/sync-status', [App\Http\Controllers\Api\Tenant\PaymentController::class, 'syncPaymentStatus']);
        Route::get('/{id}/expiration-info', [App\Http\Controllers\Api\Tenant\PaymentController::class, 'getExpirationInfo']);
        Route::get('/expired/list', [App\Http\Controllers\Api\Tenant\PaymentController::class, 'getExpiredPayments']);
        Route::post('/{id}/regenerate', [App\Http\Controllers\Api\Tenant\PaymentController::class, 'regenerateExpiredPayment']);

        // Receipt routes
        Route::get('/{id}/receipt/download', [App\Http\Controllers\Api\Tenant\ReceiptController::class, 'download']);
        Route::get('/{id}/receipt/url', [App\Http\Controllers\Api\Tenant\ReceiptController::class, 'getUrl']);
        Route::get('/{id}/receipt/check', [App\Http\Controllers\Api\Tenant\ReceiptController::class, 'checkAvailability']);
    });

    // RFID Cards
    Route::prefix('rfid')->group(function () {
        Route::get('/cards', [App\Http\Controllers\Api\Tenant\RfidController::class, 'cards']);
        Route::post('/request-card', [App\Http\Controllers\Api\Tenant\RfidController::class, 'requestCard']);
        Route::post('/report-lost', [App\Http\Controllers\Api\Tenant\RfidController::class, 'reportLost']);
        Route::put('/cards/{id}/status', [App\Http\Controllers\Api\Tenant\RfidController::class, 'toggleStatus']);
    });

    // Access History
    Route::prefix('access')->group(function () {
        Route::get('/history', [App\Http\Controllers\Api\Tenant\AccessLogController::class, 'history']);
        Route::get('/stats', [App\Http\Controllers\Api\Tenant\AccessLogController::class, 'stats']);
    });

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/settings', [App\Http\Controllers\Api\Tenant\TenantController::class, 'profileSettings']);
        Route::put('/update', [App\Http\Controllers\Api\Tenant\TenantController::class, 'updateProfile']);
        Route::post('/emergency-contact', [App\Http\Controllers\Api\Tenant\TenantController::class, 'updateEmergencyContact']);
    });

    // IoT Devices
    Route::prefix('iot-devices')->group(function () {
        Route::get('/room', [App\Http\Controllers\Api\Tenant\IoTDeviceController::class, 'roomDevices']);
        Route::get('/{id}/status', [App\Http\Controllers\Api\Tenant\IoTDeviceController::class, 'deviceStatus']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Tenant\NotificationController::class, 'index']);
        Route::get('/unread-count', [App\Http\Controllers\Api\Tenant\NotificationController::class, 'unreadCount']);
        Route::put('/{id}/read', [App\Http\Controllers\Api\Tenant\NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [App\Http\Controllers\Api\Tenant\NotificationController::class, 'markAllAsRead']);

        // Debug endpoint
        Route::get('/debug-info', function () {
            $user = auth()->user();
            $tenant = \App\Models\Tenant::where('user_id', $user->id)->first();

            $info = [
                'user' => $user ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email] : null,
                'tenant' => $tenant ? ['id' => $tenant->id, 'user_id' => $tenant->user_id, 'status' => $tenant->status] : null,
                'notifications_count' => $user ? \App\Models\Notification::where('user_id', $user->id)->count() : 0,
                'all_notifications_count' => \App\Models\Notification::count(),
                'sample_notifications' => $user ? \App\Models\Notification::where('user_id', $user->id)->limit(3)->get() : [],
            ];

            return response()->json($info);
        });

    });

    // Door Control
    Route::prefix('door')->group(function () {
        // Test endpoint first
        Route::get('/test', function () {
            return response()->json([
                'success' => true,
                'message' => 'Door control routes are working',
                'user' => auth()->user() ? auth()->user()->only(['id', 'name', 'role']) : null,
            ]);
        });

        Route::post('/open', [App\Http\Controllers\Api\Tenant\DoorController::class, 'openMyRoomDoor']);
        Route::get('/status', [App\Http\Controllers\Api\Tenant\DoorController::class, 'getMyRoomDoorStatus']);
    });
});

// Test door control route (no auth for debugging)
Route::get('/test-tenant-door', function () {
    return response()->json([
        'success' => true,
        'message' => 'Tenant door control test endpoint working',
        'timestamp' => now()->toISOString(),
    ]);
});

Route::post('/test-tenant-door-open', function () {
    return response()->json([
        'success' => true,
        'message' => 'Tenant door open test endpoint working',
        'data' => [
            'command' => 'open_door',
            'timestamp' => now()->toISOString(),
            'note' => 'This is a test endpoint without authentication',
        ],
    ]);
});

// Debug route to check user role and tenant access
Route::middleware(['auth:sanctum'])->get('/debug-tenant-access', function () {
    $user = auth()->user();
    if (! $user) {
        return response()->json([
            'success' => false,
            'message' => 'Not authenticated',
        ], 401);
    }

    $tenant = \App\Models\Tenant::where('user_id', $user->id)->first();

    return response()->json([
        'success' => true,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
        ],
        'tenant' => $tenant ? [
            'id' => $tenant->id,
            'status' => $tenant->status,
            'room_id' => $tenant->room_id,
            'room' => $tenant->room ? [
                'id' => $tenant->room->id,
                'room_number' => $tenant->room->room_number,
                'status' => $tenant->room->status,
            ] : null,
        ] : null,
        'iot_devices' => $tenant && $tenant->room ?
            \App\Models\IoTDevice::where('room_id', $tenant->room->id)->get(['id', 'device_id', 'device_name', 'device_type', 'status']) : [],
        'can_access_door' => $user->role === 'tenant' && $user->status === 'active' && $tenant && $tenant->status === 'active',
    ]);
});

// Working door control route - using the same pattern as debug-tenant-access
Route::middleware(['auth:sanctum'])->post('/tenant-door-open', function () {
    try {
        $user = auth()->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        // Get tenant record - same as debug logic
        $tenant = \App\Models\Tenant::where('user_id', $user->id)
            ->where('status', \App\Models\Tenant::STATUS_ACTIVE)
            ->with('room')
            ->first();

        if (! $tenant || ! $tenant->room) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Tidak ditemukan record tenant aktif',
            ], 403);
        }

        // Get device - same as debug logic but look for door control
        $device = \App\Models\IoTDevice::where('room_id', $tenant->room->id)
            ->whereIn('device_type', ['door_controller', 'rfid_reader'])
            ->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada door controller yang terdaftar untuk kamar Anda',
            ], 404);
        }

        if ($device->status !== 'online') {
            return response()->json([
                'success' => false,
                'message' => "Door controller sedang offline (status: {$device->status})",
            ], 503);
        }

        $reason = request()->input('reason', 'Kontrol manual tenant');

        // Send MQTT command with exact format requested
        $command = [
            'command' => 'open_door',
            'device_id' => $device->device_id,
            'timestamp' => now()->timestamp * 1000,
            'reason' => $reason,
            'from' => 'tenant_dashboard',
            'user_id' => $user->id,
        ];

        // Use the exact topic requested: rfid/command
        $mqttService = app(\App\Services\MqttService::class);
        $topic = 'rfid/command';
        $published = $mqttService->publish($topic, json_encode($command));

        if ($published) {
            \Illuminate\Support\Facades\Log::info('Tenant door open command sent successfully', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'tenant_id' => $tenant->id,
                'room_id' => $tenant->room->id,
                'room_number' => $tenant->room->room_number,
                'device_id' => $device->device_id,
                'reason' => $reason,
                'mqtt_topic' => $topic,
                'command' => $command,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Perintah buka pintu berhasil dikirim',
                'data' => [
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'room_number' => $tenant->room->room_number,
                    'timestamp' => now()->toISOString(),
                    'reason' => $reason,
                    'user' => $user->name,
                    'mqtt_topic' => $topic,
                    'command' => $command,
                ],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim perintah - koneksi MQTT bermasalah',
            ], 500);
        }

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Tenant door control error', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error: '.$e->getMessage(),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role ?? 'unknown',
        ], 500);
    }
});

// Shared routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthController::class, 'profile']);

    // Notifications (shared between admin and tenant)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Tenant\NotificationController::class, 'index']);
        Route::get('/unread-count', [App\Http\Controllers\Api\Tenant\NotificationController::class, 'unreadCount']);
        Route::put('/{id}/read', [App\Http\Controllers\Api\Tenant\NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [App\Http\Controllers\Api\Tenant\NotificationController::class, 'markAllAsRead']);
    });

    // Payments (shared endpoints)
    Route::prefix('payments')->group(function () {
        Route::get('/{id}', [App\Http\Controllers\Api\PaymentController::class, 'show']);
        Route::get('/{id}/status', [App\Http\Controllers\Api\PaymentController::class, 'checkStatus']);
    });

    // RFID shared endpoints (for scanner component)
    Route::prefix('rfid')->group(function () {
        Route::get('/check-card/{uid}', [App\Http\Controllers\Api\Admin\RfidController::class, 'checkCardExists']);
        Route::post('/register-scan', [App\Http\Controllers\Api\Admin\RfidController::class, 'store']);
        Route::post('/register-card', [App\Http\Controllers\Api\Admin\RfidController::class, 'store']);
    });

    // ESP32 IoT Device endpoints (no auth required)
    Route::prefix('esp32')->group(function () {
        Route::post('/rfid/process', [App\Http\Controllers\Api\Admin\RfidController::class, 'processRfidFromESP32']);
        Route::post('/heartbeat', [App\Http\Controllers\Api\Admin\IoTDeviceController::class, 'heartbeat']);
    });
});

// ESP32 endpoints without auth (outside the auth middleware)
Route::prefix('esp32')->group(function () {
    Route::post('/rfid/access', [App\Http\Controllers\Api\Admin\RfidController::class, 'processRfidFromESP32']);
    Route::post('/door/access', [App\Http\Controllers\Api\Admin\RfidController::class, 'processRfidFromESP32']);
    Route::post('/rfid/scan', [App\Http\Controllers\Api\Admin\RfidController::class, 'processRfidFromESP32']);
});

// Additional endpoints for ESP32 (multiple possible paths)
Route::post('/rfid/access', [App\Http\Controllers\Api\Admin\RfidController::class, 'processRfidFromESP32']);
Route::post('/door/access', [App\Http\Controllers\Api\Admin\RfidController::class, 'processRfidFromESP32']);

// Test login endpoint (lightweight version)
Route::post('/test-login', function (\Illuminate\Http\Request $request) {
    try {
        $email = $request->input('email');
        $password = $request->input('password');

        if (! $email || ! $password) {
            return response()->json([
                'success' => false,
                'message' => 'Email and password required',
            ], 422);
        }

        $user = \App\Models\User::where('email', $email)->first(['id', 'name', 'email', 'role', 'status', 'password']);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if (! \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account inactive',
            ], 403);
        }

        // Simple token generation without Sanctum complexity
        $token = 'test_token_'.\Illuminate\Support\Str::random(40);

        return response()->json([
            'success' => true,
            'message' => 'Test login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'note' => 'This is a test endpoint with mock token',
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Test login failed',
        ], 500);
    }
});

// Test IoT devices endpoint (lightweight version)
Route::get('/test-iot-devices', function () {
    try {
        $devices = \App\Models\IoTDevice::limit(10)->get(['id', 'device_id', 'device_name', 'device_type', 'status', 'room_id']);

        return response()->json([
            'success' => true,
            'message' => 'Test IoT devices endpoint working',
            'count' => $devices->count(),
            'data' => $devices->map(function ($device) {
                return [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'device_type' => $device->device_type,
                    'status' => $device->status,
                    'room_id' => $device->room_id,
                ];
            }),
            'timestamp' => now()->toISOString(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Test IoT devices endpoint failed',
        ], 500);
    }
});

// Test auto-assign devices to rooms (without auth for testing)
Route::post('/test-auto-assign-devices', function () {
    try {
        // Get RFID devices that don't have room assignments
        $unassignedDevices = \App\Models\IoTDevice::where('device_type', 'rfid_reader')
            ->whereNull('room_id')
            ->get();

        if ($unassignedDevices->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'All RFID devices are already assigned to rooms',
                'assignments' => [],
                'current_assignments' => \App\Models\IoTDevice::where('device_type', 'rfid_reader')->with('room')->get(),
            ]);
        }

        $assignments = [];

        foreach ($unassignedDevices as $device) {
            // Strategy 1: Match device number to room number
            $roomId = null;
            if (preg_match('/(\d+)/', $device->device_id, $matches)) {
                $deviceNumber = (int) $matches[1];

                // Try to find room with matching number
                $room = \App\Models\Room::where('room_number', 'LIKE', '%'.str_pad($deviceNumber, 2, '0', STR_PAD_LEFT).'%')
                    ->orWhere('room_number', 'LIKE', '%'.$deviceNumber.'%')
                    ->first();

                if ($room) {
                    $roomId = $room->id;
                }
            }

            // Strategy 2: Find rooms with RFID cards but no devices
            if (! $roomId) {
                $roomsWithCards = \DB::table('rfid_cards')
                    ->whereNotNull('room_id')
                    ->where('status', 'active')
                    ->pluck('room_id')
                    ->unique();

                $roomsWithDevices = \App\Models\IoTDevice::whereNotNull('room_id')
                    ->where('device_type', 'rfid_reader')
                    ->pluck('room_id')
                    ->unique();

                $roomsNeedingDevices = $roomsWithCards->diff($roomsWithDevices);

                if ($roomsNeedingDevices->isNotEmpty()) {
                    $roomId = $roomsNeedingDevices->first();
                }
            }

            if ($roomId) {
                $device->update(['room_id' => $roomId]);

                $assignments[] = [
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'assigned_room_id' => $roomId,
                    'room' => \App\Models\Room::find($roomId),
                ];

                \Log::info('Test auto-assigned device to room', [
                    'device_id' => $device->device_id,
                    'room_id' => $roomId,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($assignments) > 0
                ? 'Successfully auto-assigned '.count($assignments).' devices to rooms'
                : 'No suitable room assignments found for unassigned devices',
            'assignments' => $assignments,
            'unassigned_before' => $unassignedDevices->count(),
            'assigned_now' => count($assignments),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Test auto-assignment failed',
        ], 500);
    }
});

// Test endpoint untuk ESP32
Route::post('/test-rfid', function (\Illuminate\Http\Request $request) {
    \Log::info('ESP32 Test Request', $request->all());

    $cardUID = $request->input('card_uid') ?: $request->input('uid');
    $deviceId = $request->input('device_id');

    return response()->json([
        'success' => true,
        'message' => 'Test endpoint working',
        'received_data' => $request->all(),
        'card_uid' => $cardUID,
        'device_id' => $deviceId,
        'timestamp' => now()->toISOString(),
    ]);
});

// Test real payment sync
Route::get('/test-payment-sync/{id}', function ($id) {
    try {
        $payment = \App\Models\Payment::findOrFail($id);

        if (! $payment->order_id) {
            return response()->json([
                'success' => false,
                'message' => 'Payment does not have order_id',
                'payment' => $payment->only(['id', 'status', 'amount', 'order_id']),
            ], 422);
        }

        // Test real Midtrans sync
        $midtransService = app(\App\Services\MidtransService::class);
        $result = $midtransService->checkPaymentStatus($payment->order_id);

        return response()->json([
            'success' => true,
            'message' => 'Real payment sync test',
            'payment_before' => [
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'created_at' => $payment->created_at,
                'snap_token_created_at' => $payment->snap_token_created_at,
            ],
            'midtrans_result' => $result,
            'midtrans_reachable' => $result['success'] ? true : false,
            'error_details' => ! $result['success'] ? $result : null,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Failed to test real payment sync',
        ], 500);
    }
});

// Test Midtrans connectivity
Route::get('/test-midtrans', function () {
    try {
        // Test Midtrans configuration
        $serverKey = env('MIDTRANS_SERVER_KEY');
        $clientKey = env('MIDTRANS_CLIENT_KEY');
        $isProduction = env('MIDTRANS_IS_PRODUCTION', false);

        if (! $serverKey || ! $clientKey) {
            return response()->json([
                'success' => false,
                'message' => 'Midtrans configuration missing',
                'config_check' => [
                    'server_key' => $serverKey ? 'SET' : 'MISSING',
                    'client_key' => $clientKey ? 'SET' : 'MISSING',
                    'is_production' => $isProduction,
                ],
            ], 500);
        }

        // Test API connection to Midtrans
        $midtransService = app(\App\Services\MidtransService::class);

        // Create a test order_id to check if we can query Midtrans
        $testOrderId = 'TEST-'.time();

        // Try to check status of a non-existent order (should return 404, which means connection works)
        $result = $midtransService->checkPaymentStatus($testOrderId);

        return response()->json([
            'success' => true,
            'message' => 'Midtrans connectivity test completed',
            'config' => [
                'server_key' => substr($serverKey, 0, 10).'...',
                'client_key' => substr($clientKey, 0, 10).'...',
                'is_production' => $isProduction,
                'environment' => $isProduction ? 'production' : 'sandbox',
            ],
            'connection_test' => [
                'test_order_id' => $testOrderId,
                'midtrans_response' => $result['success'] ? 'Connected' : 'Error: '.($result['error'] ?? 'Unknown'),
                'note' => 'Expected: 404 error for non-existent order means connection works',
            ],
            'timestamp' => now()->toISOString(),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Midtrans connectivity test failed',
            'error' => $e->getMessage(),
            'config_status' => [
                'server_key' => env('MIDTRANS_SERVER_KEY') ? 'SET' : 'MISSING',
                'client_key' => env('MIDTRANS_CLIENT_KEY') ? 'SET' : 'MISSING',
            ],
        ], 500);
    }
});

// Debug endpoint untuk cek RFID card
Route::get('/debug/rfid/{uid}', function ($uid) {
    $card = \App\Models\RfidCard::where('uid', strtoupper($uid))
        ->with(['user', 'room'])
        ->first();

    if (! $card) {
        return response()->json(['error' => 'Card not found', 'uid' => $uid]);
    }

    return response()->json([
        'card' => $card,
        'status' => $card->status,
        'user_assigned' => (bool) $card->user_id,
        'room_assigned' => (bool) $card->room_id,
        'can_access' => $card->status === 'active' && $card->user_id,
    ]);
});

// Debug endpoint untuk cek IoT Device
Route::get('/debug/device/{device_id}', function ($device_id) {
    $device = \App\Models\IoTDevice::where('device_id', $device_id)
        ->with('room')
        ->first();

    if (! $device) {
        return response()->json(['error' => 'Device not found', 'device_id' => $device_id]);
    }

    return response()->json([
        'device' => $device,
        'has_room' => (bool) $device->room_id,
        'room_info' => $device->room,
        'all_devices' => \App\Models\IoTDevice::all(['id', 'device_id', 'device_name', 'room_id', 'status']),
    ]);
});

// Test MQTT door control endpoint
Route::post('/test-mqtt-door-control', function (\Illuminate\Http\Request $request) {
    try {
        $deviceId = $request->input('device_id', 'ESP32-RFID-01');
        $action = $request->input('action', 'open');

        // Check MQTT service configuration
        $mqttHost = env('HIVEMQ_HOST');
        $mqttPort = env('HIVEMQ_PORT');
        $mqttUsername = env('HIVEMQ_USERNAME');
        $mqttPassword = env('HIVEMQ_PASSWORD');

        if (! $mqttHost || ! $mqttUsername || ! $mqttPassword) {
            return response()->json([
                'success' => false,
                'message' => 'MQTT configuration missing',
                'missing_config' => [
                    'HIVEMQ_HOST' => ! $mqttHost,
                    'HIVEMQ_USERNAME' => ! $mqttUsername,
                    'HIVEMQ_PASSWORD' => ! $mqttPassword,
                ],
                'suggestion' => 'Check .env file for MQTT settings',
            ]);
        }

        // Test MQTT connection and publishing
        $mqttService = app(\App\Services\MqttService::class);

        // Prepare command using ESP32 expected format
        $command = [
            'command' => $action === 'open' ? 'open_door' : 'close_door',
            'device_id' => $deviceId,
            'timestamp' => time() * 1000, // ESP32 expects milliseconds
            'reason' => 'Test door control from debug endpoint',
            'from' => 'test_endpoint',
        ];

        \Log::info('Test endpoint sending command:', $command);

        $topic = 'rfid/command'; // ESP32 subscribes to this topic

        // Try to connect and publish
        $connected = $mqttService->connect();
        if (! $connected) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to MQTT broker',
                'config' => [
                    'host' => $mqttHost,
                    'port' => $mqttPort,
                    'username' => $mqttUsername ? 'SET' : 'NOT_SET',
                ],
            ]);
        }

        $published = $mqttService->publish($topic, json_encode($command));

        if ($published) {
            return response()->json([
                'success' => true,
                'message' => 'MQTT door control command sent successfully',
                'data' => [
                    'topic' => $topic,
                    'command' => $command,
                    'mqtt_connected' => true,
                ],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish MQTT message',
                'data' => [
                    'topic' => $topic,
                    'command' => $command,
                    'mqtt_connected' => true,
                ],
            ]);
        }

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'MQTT test failed: '.$e->getMessage(),
            'error' => $e->getMessage(),
            'trace' => app()->environment('local') ? $e->getTraceAsString() : 'Hidden in production',
        ], 500);
    }
});

// Test door control endpoint without auth (for frontend debugging)
Route::post('/test-door-control-frontend', function (\Illuminate\Http\Request $request) {
    try {
        $deviceId = $request->input('device_id', 'ESP32-RFID-01');
        $command = $request->input('command', 'open_door');
        $reason = $request->input('reason', 'Test from frontend debug');

        \Log::info('Frontend test endpoint called:', [
            'device_id' => $deviceId,
            'command' => $command,
            'all_input' => $request->all(),
        ]);

        // Map room_id to correct device_id if needed
        if (is_numeric($deviceId)) {
            // If device_id is numeric (like "1"), it's probably room_id
            // Map it to correct ESP32 device_id
            $deviceId = 'ESP32-RFID-01'; // Default ESP32 device
            \Log::info('Mapped numeric device_id to ESP32 device:', ['device_id' => $deviceId]);
        }

        // Use the same DoorController logic
        $doorController = app(\App\Http\Controllers\Api\Admin\DoorController::class);

        // Create a new request with correct format
        $doorRequest = new \Illuminate\Http\Request([
            'device_id' => $deviceId,
            'command' => $command,
            'reason' => $reason,
        ]);

        $response = $doorController->sendCommand($doorRequest);

        return $response;

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Test door control failed: '.$e->getMessage(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Test RFID cards endpoint
Route::get('/test-rfid-cards', function () {
    try {
        // Raw query untuk avoid error
        $cards = \DB::table('rfid_cards')
            ->leftJoin('users', 'rfid_cards.user_id', '=', 'users.id')
            ->leftJoin('tenants', 'rfid_cards.tenant_id', '=', 'tenants.id')
            ->leftJoin('rooms', 'tenants.room_id', '=', 'rooms.id')
            ->select(
                'rfid_cards.id',
                'rfid_cards.uid',
                'rfid_cards.user_id',
                'rfid_cards.tenant_id',
                'rfid_cards.card_type',
                'rfid_cards.status',
                'users.name as user_name',
                'tenants.tenant_code',
                'rooms.room_number'
            )
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'RFID cards test endpoint',
            'total_cards' => $cards->count(),
            'data' => $cards,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Test RFID cards failed: '.$e->getMessage(),
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Debug auth endpoint
Route::get('/debug-auth', function () {
    try {
        $headers = request()->headers->all();
        $authHeader = request()->header('Authorization');
        $user = auth('sanctum')->user();

        return response()->json([
            'has_auth_header' => ! empty($authHeader),
            'auth_header' => $authHeader ? substr($authHeader, 0, 20).'...' : null,
            'user_authenticated' => ! empty($user),
            'user' => $user ? $user->only(['id', 'name', 'email', 'role']) : null,
            'bearer_token_format' => str_starts_with($authHeader ?? '', 'Bearer '),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ]);
    }
});

// Debug payment endpoint
Route::get('/debug-payment/{id}', function ($id) {
    try {
        // Check if user is authenticated
        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json(['error' => 'No authenticated user']);
        }

        // Check tenant
        $tenant = \App\Models\Tenant::where('user_id', $user->id)->where('status', 'active')->first();
        if (! $tenant) {
            return response()->json(['error' => 'No active tenant found']);
        }

        // Check payment
        $payment = \App\Models\Payment::where('tenant_id', $tenant->id)->find($id);
        if (! $payment) {
            return response()->json(['error' => 'Payment not found']);
        }

        // Check Midtrans config
        $midtransConfig = [
            'server_key' => config('services.midtrans.server_key'),
            'client_key' => config('services.midtrans.client_key'),
            'is_production' => config('services.midtrans.is_production'),
        ];

        return response()->json([
            'success' => true,
            'user' => $user->only(['id', 'name', 'email']),
            'tenant' => $tenant->only(['id', 'user_id', 'status']),
            'payment' => $payment->only(['id', 'order_id', 'amount', 'status']),
            'midtrans_config' => [
                'server_key_exists' => ! empty($midtransConfig['server_key']),
                'client_key_exists' => ! empty($midtransConfig['client_key']),
                'is_production' => $midtransConfig['is_production'],
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Test payment URL endpoint (simplified)
Route::get('/test-payment-url/{id}', function ($id) {
    try {
        // Simulate the exact same flow as PaymentController
        $paymentController = new \App\Http\Controllers\Api\Tenant\PaymentController(
            new \App\Services\MidtransService
        );

        // Create a mock request with authentication
        $request = new \Illuminate\Http\Request;
        $request->setUserResolver(function () {
            return auth('sanctum')->user();
        });

        // Call the actual method
        $response = $paymentController->getPaymentUrl($request, $id);

        return $response;

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Test endpoint error: '.$e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Test MQTT connectivity and access logs
Route::get('/test-mqtt-status', function () {
    try {
        // Get recent access logs
        $recentLogs = \App\Models\AccessLog::orderBy('accessed_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'recent_logs_count' => $recentLogs->count(),
            'recent_logs' => $recentLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'rfid_uid' => $log->rfid_uid,
                    'device_id' => $log->device_id,
                    'access_granted' => $log->access_granted,
                    'accessed_at' => $log->accessed_at,
                    'notes' => $log->notes,
                ];
            }),
            'environment' => [
                'mqtt_host' => env('HIVEMQ_HOST', 'not configured'),
                'mqtt_port' => env('HIVEMQ_PORT', 'not configured'),
                'mqtt_user' => env('HIVEMQ_USERNAME') ? 'configured' : 'not configured',
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Test create access log entry
Route::post('/test-create-access-log', function (\Illuminate\Http\Request $request) {
    try {
        $log = \App\Models\AccessLog::create([
            'user_id' => null,
            'room_id' => null,
            'rfid_uid' => $request->get('rfid_uid', 'TEST123'),
            'device_id' => $request->get('device_id', 'ESP32-TEST'),
            'access_granted' => $request->get('access_granted', true),
            'accessed_at' => now(),
            'notes' => 'Test access log from API endpoint',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test access log created',
            'data' => $log,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Debug tenant dashboard issue
Route::get('/debug-tenant-dashboard/{user_id}', function ($user_id) {
    try {
        $user = \App\Models\User::find($user_id);
        if (! $user) {
            return response()->json(['error' => 'User not found']);
        }

        $allTenants = \App\Models\Tenant::where('user_id', $user_id)->get();
        $activeTenant = \App\Models\Tenant::where('user_id', $user_id)
            ->where('status', \App\Models\Tenant::STATUS_ACTIVE)
            ->with(['room', 'user'])
            ->first();

        return response()->json([
            'user' => $user,
            'all_tenants' => $allTenants,
            'active_tenant' => $activeTenant,
            'constants' => [
                'STATUS_ACTIVE' => \App\Models\Tenant::STATUS_ACTIVE,
                'STATUS_MOVED_OUT' => \App\Models\Tenant::STATUS_MOVED_OUT,
                'STATUS_SUSPENDED' => \App\Models\Tenant::STATUS_SUSPENDED,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Test receipt generation endpoint
Route::get('/test-receipt', function () {
    try {
        // Find a paid payment or create a test one
        $payment = \App\Models\Payment::where('status', 'paid')->first();

        if (! $payment) {
            // Create a test payment
            $tenant = \App\Models\Tenant::first();
            if (! $tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tenant found. Please run seeders first.',
                ], 404);
            }

            $payment = \App\Models\Payment::create([
                'tenant_id' => $tenant->id,
                'order_id' => 'TEST-'.time(),
                'amount' => 1000000,
                'payment_month' => now()->format('Y-m'),
                'status' => 'paid',
                'payment_method' => 'bank_transfer',
                'paid_at' => now(),
            ]);
        }

        // Load necessary relationships
        $payment->load(['tenant.user', 'tenant.room']);

        // Test receipt generation
        $receiptService = new \App\Services\ReceiptService;
        $receiptPath = $receiptService->generateReceipt($payment);

        return response()->json([
            'success' => true,
            'message' => 'Receipt generated successfully',
            'data' => [
                'payment_id' => $payment->id,
                'receipt_path' => $receiptPath,
                'receipt_url' => \Storage::disk('public')->url($receiptPath),
                'file_exists' => \Storage::disk('public')->exists($receiptPath),
                'file_size' => \Storage::disk('public')->exists($receiptPath) ? \Storage::disk('public')->size($receiptPath) : 0,
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error generating receipt',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Test receipt HTML generation only (debug endpoint)
Route::get('/test-receipt-html', function () {
    try {
        // Find a paid payment or create a test one
        $payment = \App\Models\Payment::where('status', 'paid')->first();

        if (! $payment) {
            // Create a test payment
            $tenant = \App\Models\Tenant::first();
            if (! $tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tenant found. Please run seeders first.',
                ], 404);
            }

            $payment = \App\Models\Payment::create([
                'tenant_id' => $tenant->id,
                'order_id' => 'TEST-HTML-'.time(),
                'amount' => 1000000,
                'payment_month' => now()->format('Y-m'),
                'status' => 'paid',
                'payment_method' => 'bank_transfer',
                'paid_at' => now(),
            ]);
        }

        // Load necessary relationships
        $payment->load(['tenant.user', 'tenant.room']);

        // Test just HTML generation
        $receiptService = new \App\Services\ReceiptService;

        // Use reflection to access private methods for testing
        $reflection = new \ReflectionClass($receiptService);
        $prepareDataMethod = $reflection->getMethod('prepareReceiptData');
        $prepareDataMethod->setAccessible(true);
        $generateHtmlMethod = $reflection->getMethod('generateReceiptHtml');
        $generateHtmlMethod->setAccessible(true);

        $receiptData = $prepareDataMethod->invoke($receiptService, $payment);
        $html = $generateHtmlMethod->invoke($receiptService, $receiptData);

        return response($html, 200, ['Content-Type' => 'text/html']);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error generating receipt HTML',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Debug routes removed - use scripts/debug_midtrans.php if needed

// Test DomPDF availability
Route::get('/test-dompdf', function () {
    try {
        $info = [
            'dompdf_class_exists' => class_exists('\\Dompdf\\Dompdf'),
            'dompdf_options_exists' => class_exists('\\Dompdf\\Options'),
            'php_version' => phpversion(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];

        if (class_exists('\\Dompdf\\Dompdf')) {
            try {
                $options = new \Dompdf\Options;
                $dompdf = new \Dompdf\Dompdf($options);
                $info['dompdf_instantiation'] = 'success';
            } catch (\Exception $e) {
                $info['dompdf_instantiation'] = 'failed: '.$e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $info,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
