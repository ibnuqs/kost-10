<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\IoTDevice;
use App\Models\Tenant;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DoorController extends Controller
{
    protected $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    /**
     * Open tenant's room door
     */
    public function openMyRoomDoor(Request $request)
    {
        try {
            $user = Auth::user();

            // Step 1: Verifikasi tenant record
            $tenant = Tenant::where('user_id', $user->id)
                ->with('room')
                ->first();

            if (! $tenant || ! $tenant->room) {
                Log::warning('Tenant access denied - no active tenant record', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'has_tenant' => $tenant ? true : false,
                    'has_room' => $tenant && $tenant->room ? true : false,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Tidak ditemukan record tenant aktif',
                ], 403);
            }

            // Step 2: Verifikasi device untuk room ini
            $device = IoTDevice::where('room_id', $tenant->room->id)
                ->whereIn('device_type', ['door_controller', 'rfid_reader'])
                ->first();

            if (! $device) {
                Log::warning('Door controller not found for tenant room', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'room_id' => $tenant->room->id,
                    'room_number' => $tenant->room->room_number,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada door controller yang terdaftar untuk kamar Anda',
                ], 404);
            }

            // Step 3: Verifikasi status device
            if ($device->status !== 'online') {
                Log::warning('Door controller offline for tenant request', [
                    'user_id' => $user->id,
                    'device_id' => $device->device_id,
                    'device_status' => $device->status,
                    'room_number' => $tenant->room->room_number,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Door controller sedang offline (status: {$device->status})",
                ], 503);
            }

            // Step 4: Verifikasi hak akses tenant ke device ini
            $this->verifyTenantDeviceAccess($user, $tenant, $device);

            // Step 5: Verifikasi pembayaran yang tertunggak
            $overduePayments = \App\Models\Payment::where('tenant_id', $tenant->id)
                ->where('status', 'overdue')
                ->count();

            if ($overduePayments > 0) {
                Log::warning('Tenant access denied due to overdue payments', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'overdue_payments' => $overduePayments,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Anda memiliki pembayaran yang tertunggak.',
                ], 403);
            }

            $reason = $request->input('reason', 'Kontrol manual tenant');

            // Step 5: Kirim command MQTT dengan format yang diminta
            $command = [
                'command' => 'open_door',
                'device_id' => $device->device_id,
                'timestamp' => now()->timestamp * 1000, // milliseconds
                'reason' => $reason,
                'from' => 'tenant_dashboard',
                'user_id' => $user->id,
            ];

            Log::info('Sending door open command for tenant', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'tenant_id' => $tenant->id,
                'room_id' => $tenant->room->id,
                'room_number' => $tenant->room->room_number,
                'device_id' => $device->device_id,
                'command' => $command,
            ]);

            // Use the exact topic requested: rfid/command
            $topic = 'rfid/command';
            $published = $this->mqttService->publish($topic, json_encode($command));

            if ($published) {
                // Log successful door access
                Log::info('Tenant door open command sent successfully', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'tenant_id' => $tenant->id,
                    'room_id' => $tenant->room->id,
                    'room_number' => $tenant->room->room_number,
                    'device_id' => $device->device_id,
                    'reason' => $reason,
                    'mqtt_topic' => $topic,
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
                    ],
                ]);
            } else {
                Log::error('Failed to publish MQTT command for tenant door', [
                    'user_id' => $user->id,
                    'device_id' => $device->device_id,
                    'mqtt_topic' => $topic,
                    'command' => $command,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim perintah - koneksi MQTT bermasalah',
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error in tenant door control', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuka pintu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verifikasi hak akses tenant ke device tertentu
     */
    private function verifyTenantDeviceAccess($user, $tenant, $device)
    {
        // Verifikasi bahwa device benar-benar milik room tenant ini
        if ($device->room_id != $tenant->room->id) {
            Log::warning('Tenant attempting to access device from different room', [
                'user_id' => $user->id,
                'tenant_room_id' => $tenant->room->id,
                'device_room_id' => $device->room_id,
                'device_id' => $device->device_id,
            ]);

            throw new \Exception('Akses ditolak: Device bukan milik kamar Anda');
        }

        // Verifikasi tenant status masih aktif
        if ($tenant->status !== Tenant::STATUS_ACTIVE) {
            Log::warning('Inactive tenant attempting door access', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'tenant_status' => $tenant->status,
            ]);

            throw new \Exception('Akses ditolak: Status tenant tidak aktif');
        }

        // Bisa ditambah verifikasi lain seperti:
        // - Cek apakah ada pembayaran yang tertunggak
        // - Cek jam akses (misalnya jam malam)
        // - Cek apakah tenant sedang di-suspend

        Log::info('Tenant device access verified', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'device_id' => $device->device_id,
            'room_id' => $tenant->room->id,
        ]);
    }

    /**
     * Get tenant's room door status
     */
    public function getMyRoomDoorStatus(Request $request)
    {
        try {
            $user = Auth::user();

            // Get tenant record
            $tenant = Tenant::where('user_id', $user->id)
                ->where('status', Tenant::STATUS_ACTIVE)
                ->with('room')
                ->first();

            if (! $tenant || ! $tenant->room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant record or room not found',
                ], 404);
            }

            // Find IoT device for this room
            $device = IoTDevice::where('room_id', $tenant->room->id)
                ->whereIn('device_type', ['door_controller', 'rfid_reader'])
                ->first();

            if (! $device) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'room_number' => $tenant->room->room_number,
                        'device_found' => false,
                        'door_status' => 'unknown',
                        'device_status' => 'offline',
                        'last_seen' => null,
                        'message' => 'No door controller found for this room',
                    ],
                ]);
            }

            // Parse device info if available
            $deviceInfo = [];
            if ($device->device_info) {
                $deviceInfo = is_string($device->device_info)
                    ? json_decode($device->device_info, true)
                    : $device->device_info;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'room_number' => $tenant->room->room_number,
                    'device_found' => true,
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'door_status' => $deviceInfo['door_status'] ?? 'unknown',
                    'device_status' => $device->status,
                    'last_seen' => $device->last_seen,
                    'device_info' => $deviceInfo,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting tenant door status', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get door status: '.$e->getMessage(),
            ], 500);
        }
    }
}
