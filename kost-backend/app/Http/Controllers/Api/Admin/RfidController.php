<?php

// File: app/Http/Controllers/Api/Admin/RfidController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\IoTDevice;
use App\Models\RfidCard;
use App\Models\Room;
use App\Models\User;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RfidController extends Controller
{
    private $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    /**
     * Helper untuk mendapatkan respons error yang konsisten.
     */
    protected function getErrorResponse(
        \Exception $e,
        $message = 'Terjadi kesalahan server internal.',
        $statusCode = 500
    ) {
        Log::error($message, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => app()->environment('local') ? $e->getMessage() : 'Terjadi Kesalahan Server Internal',
        ], $statusCode);
    }

    // ===== OPERASI CRUD DASAR =====

    /**
     * Mendapatkan semua kartu RFID untuk panel admin.
     * Endpoint: GET /admin/rfid/cards
     */
    public function index(Request $request)
    {
        try {
            $query = RfidCard::select('id', 'uid', 'user_id', 'tenant_id', 'card_type', 'status', 'created_at', 'updated_at')
                ->with([
                    'user:id,name,email',
                    'tenant.room:id,room_number,room_name',
                    'tenant.room.iotDevices:id,device_id,device_name,room_id',
                ]);

            // Filter berdasarkan status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter berdasarkan pengguna
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter berdasarkan tenant
            if ($request->filled('tenant_id')) {
                $query->where('tenant_id', $request->tenant_id);
            }

            // Pencarian berdasarkan UID atau nama pengguna
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('uid', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            // Urutkan berdasarkan tanggal dibuat (terbaru dulu)
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            if (in_array($sortBy, ['created_at', 'uid', 'status'])) {
                $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
            $cards = $query->paginate($perPage);

            // Manual add device_id untuk debugging
            $cards->getCollection()->transform(function ($card) {
                $cardArray = $card->toArray();

                // Manual device_id calculation
                if ($card->tenant_id) {
                    $tenant = \App\Models\Tenant::find($card->tenant_id);
                    if ($tenant && $tenant->room_id) {
                        $iotDevice = IoTDevice::where('room_id', $tenant->room_id)->first();
                        $cardArray['device_id'] = $iotDevice ? $iotDevice->device_id : null;

                        Log::info('Manual device_id mapping', [
                            'card_id' => $card->id,
                            'tenant_id' => $card->tenant_id,
                            'room_id' => $tenant->room_id,
                            'device_id' => $cardArray['device_id'],
                        ]);
                    } else {
                        $cardArray['device_id'] = null;
                        Log::warning('Tenant not found or no room_id', [
                            'card_id' => $card->id,
                            'tenant_id' => $card->tenant_id,
                        ]);
                    }
                } else {
                    $cardArray['device_id'] = null;
                }

                return $cardArray;
            });

            // Statistik ringkasan
            $stats = [
                'total' => RfidCard::count(),
                'aktif' => RfidCard::where('status', 'active')->count(),
                'tidak_aktif' => RfidCard::where('status', 'inactive')->count(),
                'terdaftar' => RfidCard::whereNotNull('user_id')->count(),
                'belum_terdaftar' => RfidCard::whereNull('user_id')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Kartu RFID berhasil diambil',
                'data' => $cards,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mengambil kartu RFID');
        }
    }

    /**
     * Mendaftarkan kartu RFID baru dari scan atau manual input.
     * Endpoint: POST /admin/rfid/register-card
     * Body: { "uid": "ABCD1234" }
     */
    public function store(Request $request)
    {
        // Handle both 'uid' and 'rfid_uid' parameter names for compatibility
        $uid = $request->input('uid') ?: $request->input('rfid_uid');

        $validator = Validator::make(array_merge($request->all(), ['uid' => $uid]), [
            'uid' => 'required|string|max:255|unique:rfid_cards,uid',
            'tenant_id' => 'nullable|exists:tenants,id',
            'card_type' => 'nullable|in:primary,backup,temporary',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $createData = [
                'uid' => strtoupper(trim($uid)),
                'status' => $request->status ?? 'inactive',
                'tenant_id' => $request->tenant_id,
                'card_type' => $request->card_type ?? 'primary',
            ];

            // If tenant_id is provided, get user_id from tenant relationship
            if ($request->tenant_id) {
                $tenant = \App\Models\Tenant::find($request->tenant_id);
                if ($tenant) {
                    $createData['user_id'] = $tenant->user_id;
                    if (! $request->filled('status')) {
                        $createData['status'] = 'active';
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tenant not found',
                    ], 404);
                }
            } else {
                $createData['user_id'] = null;
            }

            $rfidCard = RfidCard::create($createData);

            Log::info('Kartu RFID baru didaftarkan', [
                'card_id' => $rfidCard->id,
                'uid' => $rfidCard->uid,
                'user_id' => $rfidCard->user_id,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kartu RFID berhasil didaftarkan',
                'data' => $rfidCard->load(['user:id,name,email', 'tenant.room:id,room_number,room_name']),
            ], 201);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mendaftarkan kartu RFID');
        }
    }

    /**
     * Tampilkan kartu RFID tertentu
     */
    public function show($id)
    {
        try {
            $card = RfidCard::with(['user:id,name,email,phone', 'tenant.room:id,room_number,room_name'])
                ->findOrFail($id);

            // Dapatkan statistik penggunaan kartu
            $stats = [
                'total_akses' => 0,
                'akses_hari_ini' => 0,
                'akses_minggu_ini' => 0,
                'akses_terakhir' => null,
            ];

            try {
                if ($card->user_id) {
                    $stats = [
                        'total_akses' => AccessLog::where('user_id', $card->user_id)->count(),
                        'akses_hari_ini' => AccessLog::where('user_id', $card->user_id)
                            ->whereDate('accessed_at', today())->count(),
                        'akses_minggu_ini' => AccessLog::where('user_id', $card->user_id)
                            ->whereBetween('accessed_at', [now()->startOfWeek(), now()->endOfWeek()])
                            ->count(),
                        'akses_terakhir' => AccessLog::where('user_id', $card->user_id)
                            ->latest('accessed_at')->first()?->accessed_at,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Gagal mengambil statistik akses untuk kartu', [
                    'card_id' => $card->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail kartu RFID berhasil diambil',
                'data' => $card,
                'stats' => $stats,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu RFID tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mengambil detail kartu RFID');
        }
    }

    /**
     * Perbarui kartu RFID
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required|string|max:255|unique:rfid_cards,uid,'.$id,
            'tenant_id' => 'nullable|exists:tenants,id',
            'card_type' => 'nullable|in:primary,backup,temporary',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $card = RfidCard::findOrFail($id);
            $oldData = $card->toArray();

            $updateData = [
                'uid' => strtoupper(trim($request->uid)),
                'tenant_id' => $request->tenant_id,
                'card_type' => $request->card_type ?? 'primary',
                'status' => $request->status,
            ];

            // If tenant_id is provided, get user_id from tenant relationship
            if ($request->tenant_id) {
                $tenant = \App\Models\Tenant::find($request->tenant_id);
                if ($tenant) {
                    $updateData['user_id'] = $tenant->user_id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tenant not found',
                    ], 404);
                }
            } else {
                $updateData['user_id'] = null;
            }

            $card->update($updateData);

            Log::info('Kartu RFID diperbarui', [
                'card_id' => $card->id,
                'old_data' => $oldData,
                'new_data' => $card->toArray(),
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kartu RFID berhasil diperbarui',
                'data' => $card->load(['user:id,name,email', 'tenant.room:id,room_number,room_name']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu RFID tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal memperbarui kartu RFID');
        }
    }

    /**
     * Toggle status kartu RFID (active/inactive).
     * Endpoint: PUT /admin/rfid/cards/{id}/toggle
     */
    public function toggleStatus($id)
    {
        try {
            $card = RfidCard::findOrFail($id);
            $newStatus = $card->status === 'active' ? 'inactive' : 'active';
            $card->update(['status' => $newStatus]);

            Log::info('Status kartu RFID diubah', [
                'card_id' => $card->id,
                'uid' => $card->uid,
                'old_status' => $card->getOriginal('status'),
                'new_status' => $newStatus,
                'changed_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Kartu RFID berhasil di{$newStatus}kan",
                'data' => $card->load(['user:id,name,email', 'tenant.room:id,room_number,room_name']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu RFID tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mengubah status kartu RFID');
        }
    }

    /**
     * Assign kartu RFID ke user dan/atau room.
     * Endpoint: PUT /admin/rfid/cards/{id}/assign
     * Body: { "user_id": 1, "room_id": 2 }
     */
    public function assignCard(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $card = RfidCard::findOrFail($id);

            // Periksa apakah pengguna sudah memiliki kartu aktif lain
            if ($request->user_id) {
                $existingCard = RfidCard::where('user_id', $request->user_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingCard) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pengguna sudah memiliki kartu RFID aktif lain',
                    ], 400);
                }
            }

            // Update assignment kartu
            $updateData = [
                'user_id' => $request->user_id,
                'room_id' => $request->room_id,
            ];

            // Auto-aktifkan kartu ketika ditugaskan ke pengguna
            if ($request->user_id && $card->status === 'inactive') {
                $updateData['status'] = 'active';
            }

            // Nonaktifkan kartu ketika tidak ditugaskan ke pengguna
            if (! $request->user_id && $card->status === 'active') {
                $updateData['status'] = 'inactive';
            }

            $card->update($updateData);

            Log::info('Kartu RFID ditugaskan', [
                'card_id' => $card->id,
                'uid' => $card->uid,
                'user_id' => $request->user_id,
                'room_id' => $request->room_id,
                'assigned_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kartu RFID berhasil ditugaskan',
                'data' => $card->load(['user:id,name,email', 'tenant.room:id,room_number,room_name']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu RFID tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal menugaskan kartu RFID');
        }
    }

    /**
     * Delete kartu RFID.
     * Endpoint: DELETE /admin/rfid/cards/{id}
     */
    public function destroy($id)
    {
        try {
            $card = RfidCard::findOrFail($id);
            $cardUid = $card->uid;
            $cardUser = $card->user ? $card->user->name : 'Tidak Ditugaskan';

            $card->delete();

            Log::info('Kartu RFID dihapus', [
                'card_id' => $id,
                'uid' => $cardUid,
                'user' => $cardUser,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Kartu RFID {$cardUid} berhasil dihapus",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu RFID tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal menghapus kartu RFID');
        }
    }

    /**
     * Check apakah card exists di database.
     * Endpoint: GET /admin/rfid/check-card/{uid}
     */
    public function checkCardExists($uid)
    {
        try {
            $card = RfidCard::where('uid', strtoupper(trim($uid)))
                ->with(['user:id,name,email', 'tenant.room:id,room_number,room_name'])
                ->first();

            if ($card) {
                return response()->json([
                    'success' => true,
                    'exists' => true,
                    'card' => $card,
                    'message' => 'Kartu ditemukan dalam database',
                ]);
            }

            return response()->json([
                'success' => true,
                'exists' => false,
                'card' => null,
                'message' => 'Kartu belum terdaftar',
            ], 200);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal memeriksa kartu');
        }
    }

    // ===== ESP32 INTEGRATION =====

    /**
     * Process RFID scan dari ESP32 untuk access control.
     * Endpoint: POST /admin/rfid/process (from MQTT)
     * Body from ESP32: { "card_uid": "ABCD1234", "device_id": "ESP32_ROOM_101", ... }
     */
    public function processRfidFromESP32(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_uid' => 'required|string',
            'device_id' => 'required|string',
            'location' => 'nullable|string',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cardUID = strtoupper(trim($request->input('card_uid')));
        $deviceId = $request->input('device_id');

        try {
            $this->updateDeviceHeartbeat($deviceId);

            $rfidCard = RfidCard::where('uid', $cardUID)
                ->with(['user', 'tenant.room'])
                ->first();

            $accessGranted = false;
            $message = 'Akses ditolak: Kartu tidak dikenali.';
            $userName = 'Tidak Diketahui';
            $roomNumber = 'N/A';
            $userId = null;
            $roomId = null;

            if ($rfidCard) {
                $userId = $rfidCard->user_id;
                $roomId = $rfidCard->tenant && $rfidCard->tenant->room ? $rfidCard->tenant->room->id : null;
                $userName = $rfidCard->user ? $rfidCard->user->name : 'Tidak Ditugaskan';
                $roomNumber = $rfidCard->tenant && $rfidCard->tenant->room ? $rfidCard->tenant->room->room_number : 'N/A';

                if ($rfidCard->status !== 'active') {
                    $message = 'Akses ditolak: Kartu tidak aktif.';
                } elseif (! $rfidCard->user_id) {
                    $message = 'Akses ditolak: Kartu belum ditugaskan ke pengguna.';
                } elseif ($rfidCard->tenant && $rfidCard->tenant->status !== 'active') {
                    $message = 'Akses ditolak: Status tenant tidak aktif (' . $rfidCard->tenant->status . ').';
                } elseif ($this->hasPendingOverduePayments($rfidCard)) {
                    $message = 'Akses ditolak: Terdapat pembayaran yang telat.';
                } else {
                    $device = IoTDevice::where('device_id', $deviceId)->first();

                    if (! $device || ! $device->room_id) {
                        $accessGranted = true;
                        $message = "Akses diberikan: Selamat datang, {$userName}!";
                    } elseif (! $roomId || $roomId !== $device->room_id) {
                        $message = 'Akses ditolak: Kartu untuk kamar yang berbeda.';
                    } else {
                        $accessGranted = true;
                        $message = "Akses diberikan: Selamat datang, {$userName}!";
                    }
                }
            } else {
                // Auto-registration logic
                $email = 'auto-'.strtolower($cardUID).'@demo.com';
                $user = User::where('email', $email)->first();

                if (! $user) {
                    $user = User::create([
                        'name' => 'Auto User '.substr($cardUID, 0, 4),
                        'email' => $email,
                        'password' => bcrypt(str_random(10)),
                        'role' => 'tenant',
                    ]);
                }

                $rfidCard = RfidCard::create([
                    'uid' => $cardUID,
                    'user_id' => $user->id,
                    'status' => 'active',
                ]);

                $accessGranted = true;
                $message = "Akses diberikan: Kartu baru terdaftar otomatis untuk {$user->name}.";
                $userName = $user->name;
                $userId = $user->id;
            }

            $responsePayload = [
                'card_uid' => $cardUID,
                'access_granted' => $accessGranted,
                'user_name' => $userName,
                'room_number' => $roomNumber,
                'message' => $message,
                'device_id' => $deviceId,
                'timestamp' => now()->toISOString(),
            ];

            $this->logAccess($userId, $roomId, $cardUID, $deviceId, $accessGranted, $message);

            try {
                $topic = "kost_system/door/response/{$deviceId}";
                $this->mqttService->publish($topic, json_encode($responsePayload));
                Log::info('Respons MQTT dikirim', [
                    'topic' => $topic,
                    'device_id' => $deviceId,
                    'access_granted' => $accessGranted,
                ]);
            } catch (\Exception $e) {
                Log::error('Publikasi MQTT gagal untuk respons pemrosesan RFID', [
                    'device_id' => $deviceId,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Akses RFID diproses.',
                'data' => $responsePayload,
            ]);

        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Error pemrosesan RFID ESP32');
        }
    }

    /**
     * Remote door control dari admin panel.
     * Endpoint: POST /admin/door-control/remote
     * Body: { "action": "open", "device_id": "ESP32_ROOM_101", "duration": 5000 }
     */
    public function remoteDoorControl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:open,close',
            'device_id' => 'required|string',
            'duration' => 'nullable|integer|min:1000|max:30000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $device = IoTDevice::where('device_id', $request->device_id)
                ->where('device_type', 'door_lock')
                ->with('room:id,room_number,room_name')
                ->first();

            if (! $device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perangkat tidak ditemukan atau bukan kunci pintu.',
                    'error' => 'ID Perangkat tidak terdaftar atau tipe salah.',
                ], 404);
            }

            if ($device->status === 'offline') {
                return response()->json([
                    'success' => false,
                    'message' => 'Perangkat sedang offline.',
                    'error' => 'Tidak dapat mengontrol perangkat offline.',
                ], 400);
            }

            $command = [
                'action' => $request->input('action'),
                'device_id' => $request->input('device_id'),
                'duration' => $request->input('duration', 5000),
                'timestamp' => now()->toISOString(),
                'source' => 'web_admin',
                'admin_id' => Auth::id() ?? 'system',
                'request_id' => uniqid('remote_'),
            ];

            $topic = "kost_system/door/control/{$device->device_id}";

            try {
                $this->mqttService->publish($topic, json_encode($command));

                // Log aksi kontrol remote
                $this->logAccess(
                    Auth::id(),
                    $device->room_id,
                    null, // Tidak ada UID RFID untuk kontrol remote
                    $device->device_id,
                    true, // Perintah admin dianggap "diberikan"
                    "Kontrol pintu remote '{$command['action']}' oleh admin untuk kamar ".
                    ($device->room ? $device->room->room_number : 'N/A')
                );

                Log::info('Perintah kontrol pintu remote dikirim', [
                    'device_id' => $device->device_id,
                    'action' => $command['action'],
                    'admin_id' => Auth::id(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Perintah pintu berhasil dikirim',
                    'command' => $command,
                    'status' => 'dikirim',
                    'device' => [
                        'id' => $device->device_id,
                        'name' => $device->device_name,
                        'room' => $device->room ? $device->room->room_number : null,
                    ],
                ]);

            } catch (\Exception $e) {
                Log::error('Publikasi MQTT gagal untuk kontrol remote', [
                    'device_id' => $device->device_id,
                    'error' => $e->getMessage(),
                ]);

                return $this->getErrorResponse($e, 'Gagal mengirim perintah kontrol pintu.', 500);
            }

        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Error kontrol pintu remote');
        }
    }

    /**
     * Start scanner mode pada ESP32.
     * Endpoint: POST /admin/rfid/scanner/start
     * Body: { "device_id": "ESP32_ROOM_101" }
     */
    public function startScannerMode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deviceId = $request->input('device_id');

        try {
            $device = IoTDevice::where('device_id', $deviceId)->first();

            if (! $device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perangkat scanner tidak ditemukan.',
                    'error' => 'ID Perangkat tidak terdaftar.',
                ], 404);
            }

            if ($device->status === 'offline') {
                return response()->json([
                    'success' => false,
                    'message' => 'Perangkat sedang offline.',
                    'error' => 'Tidak dapat mengirim perintah ke perangkat offline.',
                ], 400);
            }

            $command = [
                'command' => 'start-scan',
                'device_id' => $deviceId,
                'timestamp' => now()->toISOString(),
                'source' => 'web_admin',
                'request_id' => uniqid('start_scan_'),
            ];

            $topic = 'rfid/command';

            try {
                $this->mqttService->publish($topic, json_encode($command));
                Log::info('Perintah mulai mode scanner dikirim', [
                    'device_id' => $deviceId,
                    'command' => 'start-scan',
                    'request_id' => $command['request_id'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Perintah mulai scanner dikirim ke perangkat {$deviceId}",
                    'status' => 'dikirim',
                    'command' => $command,
                ]);
            } catch (\Exception $e) {
                return $this->getErrorResponse($e, 'Gagal mempublikasikan perintah mulai scanner melalui MQTT.');
            }

        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Error dalam startScannerMode');
        }
    }

    /**
     * Stop scanner mode pada ESP32.
     * Endpoint: POST /admin/rfid/scanner/stop
     * Body: { "device_id": "ESP32_ROOM_101" }
     */
    public function stopScannerMode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deviceId = $request->input('device_id');

        try {
            $device = IoTDevice::where('device_id', $deviceId)->first();

            if (! $device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perangkat scanner tidak ditemukan.',
                    'error' => 'ID Perangkat tidak terdaftar.',
                ], 404);
            }

            if ($device->status === 'offline') {
                return response()->json([
                    'success' => false,
                    'message' => 'Perangkat sedang offline.',
                    'error' => 'Tidak dapat mengirim perintah ke perangkat offline.',
                ], 400);
            }

            $command = [
                'command' => 'stop-scan',
                'device_id' => $deviceId,
                'timestamp' => now()->toISOString(),
                'source' => 'web_admin',
                'request_id' => uniqid('stop_scan_'),
            ];

            $topic = 'rfid/command';

            try {
                $this->mqttService->publish($topic, json_encode($command));
                Log::info('Perintah henti mode scanner dikirim', [
                    'device_id' => $deviceId,
                    'command' => 'stop-scan',
                    'request_id' => $command['request_id'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Perintah henti scanner dikirim ke perangkat {$deviceId}",
                    'status' => 'dikirim',
                    'command' => $command,
                ]);
            } catch (\Exception $e) {
                return $this->getErrorResponse($e, 'Gagal mempublikasikan perintah henti scanner melalui MQTT.');
            }

        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Error dalam stopScannerMode');
        }
    }

    // ===== DASHBOARD & STATISTICS =====

    /**
     * Dapatkan log akses dengan filtering
     */
    public function getAccessLogs(Request $request)
    {
        try {
            $query = AccessLog::with(['user:id,name', 'room:id,room_number,room_name'])
                ->orderBy('accessed_at', 'desc');

            if ($request->filled('date_from')) {
                $query->whereDate('accessed_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('accessed_at', '<=', $request->date_to);
            }
            if ($request->filled('rfid_uid')) {
                $query->where('rfid_uid', 'like', '%'.$request->rfid_uid.'%');
            }
            if ($request->filled('device_id')) {
                $query->where('device_id', 'like', '%'.$request->device_id.'%');
            }
            if ($request->filled('access_granted')) {
                $query->where('access_granted', $request->boolean('access_granted'));
            }

            $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Log akses berhasil diambil',
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mendapatkan log akses');
        }
    }

    /**
     * Dapatkan statistik dashboard
     */
    public function getDashboardStats()
    {
        try {
            $stats = [
                'kartu' => [
                    'total_kartu' => RfidCard::count(),
                    'kartu_aktif' => RfidCard::where('status', 'active')->count(),
                    'kartu_terdaftar' => RfidCard::whereNotNull('user_id')->count(),
                ],
                'perangkat' => [
                    'total_perangkat' => IoTDevice::count(),
                    'perangkat_online' => IoTDevice::where('status', 'online')->count(),
                    'perangkat_offline' => IoTDevice::where('status', 'offline')->count(),
                ],
                'akses_hari_ini' => [
                    'total_akses' => AccessLog::where('access_granted', true)->whereDate('accessed_at', today())->count(),
                    'akses_ditolak' => AccessLog::where('access_granted', false)->whereDate('accessed_at', today())->count(),
                ],
                'akses_minggu_ini' => [
                    'total_akses' => AccessLog::where('access_granted', true)->whereBetween('accessed_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistik dashboard berhasil diambil',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mendapatkan statistik');
        }
    }

    /**
     * Bulk operations untuk kartu RFID
     */
    public function bulkOperation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete,assign_room',
            'card_ids' => 'required|array|min:1',
            'card_ids.*' => 'exists:rfid_cards,id',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $cards = RfidCard::whereIn('id', $request->card_ids)->get();
            $updated = 0;
            $errors = [];

            foreach ($cards as $card) {
                try {
                    switch ($request->action) {
                        case 'activate':
                            $card->update(['status' => 'active']);
                            $updated++;
                            break;
                        case 'deactivate':
                            $card->update(['status' => 'inactive']);
                            $updated++;
                            break;
                        case 'delete':
                            $card->delete();
                            $updated++;
                            break;
                        case 'assign_room':
                            if ($request->room_id) {
                                $card->update(['room_id' => $request->room_id]);
                                $updated++;
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Kartu {$card->uid}: ".$e->getMessage();
                }
            }

            DB::commit();

            Log::info('Operasi bulk kartu RFID', [
                'action' => $request->action,
                'updated_count' => $updated,
                'total_count' => count($request->card_ids),
                'performed_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil {$request->action} {$updated} kartu",
                'data' => [
                    'updated_count' => $updated,
                    'total_count' => count($request->card_ids),
                    'errors' => $errors,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->getErrorResponse($e, 'Gagal melakukan operasi bulk');
        }
    }

    /**
     * Ekspor data kartu RFID
     */
    public function export(Request $request)
    {
        try {
            $query = RfidCard::with(['user:id,name,email', 'tenant.room:id,room_number,room_name']);

            // Terapkan filter yang sama dengan index
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }

            $cards = $query->orderBy('created_at', 'desc')->get();

            $exportData = $cards->map(function ($card) {
                return [
                    'UID' => $card->uid,
                    'Status' => ucfirst($card->status),
                    'Pengguna' => $card->user ? $card->user->name : 'Tidak Ditugaskan',
                    'Email' => $card->user ? $card->user->email : '',
                    'Kamar' => $card->tenant && $card->tenant->room ? "Kamar {$card->tenant->room->room_number}" : 'Tidak Ditugaskan',
                    'Dibuat Pada' => $card->created_at->format('Y-m-d H:i:s'),
                    'Diperbarui Pada' => $card->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'filename' => 'kartu_rfid_'.date('Y-m-d_H-i-s').'.csv',
                'message' => 'Data ekspor berhasil dibuat',
            ]);

        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mengekspor kartu RFID');
        }
    }

    /**
     * Impor kartu RFID dari CSV
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $imported = 0;
            $errors = [];

            $data = array_map('str_getcsv', file($file->path()));
            $headers = array_shift($data);

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                try {
                    $cardData = array_combine($headers, $row);

                    // Validasi dan buat kartu
                    if (empty($cardData['uid'])) {
                        throw new \Exception('UID tidak boleh kosong');
                    }

                    // Periksa duplikasi
                    if (RfidCard::where('uid', strtoupper(trim($cardData['uid'])))->exists()) {
                        throw new \Exception('UID sudah ada: '.$cardData['uid']);
                    }

                    RfidCard::create([
                        'uid' => strtoupper(trim($cardData['uid'])),
                        'status' => isset($cardData['status']) && in_array($cardData['status'], ['active', 'inactive'])
                            ? $cardData['status'] : 'inactive',
                        'user_id' => null, // Akan ditugaskan nanti
                        'room_id' => null,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = 'Baris '.($index + 2).': '.$e->getMessage();
                }
            }

            DB::commit();

            Log::info('Impor kartu RFID selesai', [
                'imported_count' => $imported,
                'error_count' => count($errors),
                'imported_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimpor {$imported} kartu RFID",
                'data' => [
                    'imported_count' => $imported,
                    'errors' => $errors,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->getErrorResponse($e, 'Gagal mengimpor kartu RFID');
        }
    }

    // ===== HELPER METHODS =====

    /**
     * Log percobaan akses.
     */
    private function logAccess($userId, $roomId, $rfidUid, $deviceId, $accessGranted, $notes = null)
    {
        try {
            AccessLog::create([
                'user_id' => $userId,
                'room_id' => $roomId,
                'rfid_uid' => $rfidUid,
                'device_id' => $deviceId,
                'access_granted' => $accessGranted,
                'accessed_at' => now(),
                'notes' => $notes,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mencatat akses', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'device_id' => $deviceId,
            ]);
        }
    }

    /**
     * Update heartbeat perangkat.
     */
    private function updateDeviceHeartbeat($deviceId)
    {
        try {
            $device = IoTDevice::where('device_id', $deviceId)->first();
            if ($device) {
                $device->update([
                    'last_seen' => now(),
                    'status' => 'online',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Gagal memperbarui heartbeat perangkat', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dapatkan pengguna yang tersedia untuk assignment
     */
    public function getAvailableUsers()
    {
        try {
            // Pengguna tenant dengan informasi room mereka
            $availableUsers = User::where('role', 'tenant')
                ->with(['tenants' => function ($query) {
                    $query->where('status', 'active')
                        ->with('room:id,room_number,room_name');
                }])
                ->select('id', 'name', 'email', 'phone')
                ->orderBy('name')
                ->get()
                ->map(function ($user) {
                    $activeTenant = $user->tenants->first();

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'tenant' => $activeTenant ? [
                            'id' => $activeTenant->id,
                            'room_id' => $activeTenant->room_id,
                            'status' => $activeTenant->status,
                            'room' => $activeTenant->room ? [
                                'id' => $activeTenant->room->id,
                                'room_number' => $activeTenant->room->room_number,
                            ] : null,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $availableUsers,
                'message' => 'Pengguna yang tersedia berhasil diambil',
            ]);

        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mendapatkan pengguna yang tersedia');
        }
    }

    /**
     * Dapatkan kamar yang tersedia
     */
    public function getAvailableRooms()
    {
        try {
            $rooms = Room::with(['tenants' => function ($query) {
                $query->where('status', 'active')
                    ->with('user:id,name,email');
            }])
                ->select('id', 'room_number', 'room_name', 'status')
                ->orderBy('room_number')
                ->get()
                ->map(function ($room) {
                    $activeTenant = $room->tenants->first();

                    return [
                        'id' => $room->id,
                        'room_number' => $room->room_number,
                        'room_name' => $room->room_name ?? '',
                        'status' => $room->status ?? 'available',
                        'label' => "Kamar {$room->room_number}",
                        'tenant' => $activeTenant ? [
                            'id' => $activeTenant->id,
                            'user_id' => $activeTenant->user_id,
                            'user' => $activeTenant->user ? [
                                'id' => $activeTenant->user->id,
                                'name' => $activeTenant->user->name,
                                'email' => $activeTenant->user->email,
                            ] : null,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $rooms,
                'message' => 'Kamar berhasil diambil',
            ]);

        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mengambil kamar');
        }
    }

    /**
     * Laporan penggunaan kartu RFID
     */
    public function getUsageReport(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));

            // Statistik penggunaan per kartu
            $cardUsage = DB::table('access_logs')
                ->join('rfid_cards', 'access_logs.rfid_uid', '=', 'rfid_cards.uid')
                ->leftJoin('users', 'rfid_cards.user_id', '=', 'users.id')
                ->whereBetween('access_logs.accessed_at', [$startDate, $endDate])
                ->select(
                    'rfid_cards.uid',
                    'users.name as user_name',
                    DB::raw('COUNT(*) as total_usage'),
                    DB::raw('COUNT(CASE WHEN access_logs.access_granted = 1 THEN 1 END) as successful_access'),
                    DB::raw('COUNT(CASE WHEN access_logs.access_granted = 0 THEN 1 END) as failed_access'),
                    DB::raw('MAX(access_logs.accessed_at) as last_used')
                )
                ->groupBy('rfid_cards.uid', 'users.name')
                ->orderBy('total_usage', 'desc')
                ->get();

            // Kartu yang tidak digunakan
            $unusedCards = RfidCard::with('user:id,name')
                ->whereDoesntHave('accessLogs', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('accessed_at', [$startDate, $endDate]);
                })
                ->where('status', 'active')
                ->get();

            $report = [
                'periode' => [
                    'mulai' => $startDate,
                    'selesai' => $endDate,
                ],
                'ringkasan' => [
                    'total_kartu_aktif' => RfidCard::where('status', 'active')->count(),
                    'kartu_digunakan' => $cardUsage->count(),
                    'kartu_tidak_digunakan' => $unusedCards->count(),
                    'total_akses' => $cardUsage->sum('total_usage'),
                ],
                'penggunaan_kartu' => $cardUsage,
                'kartu_tidak_digunakan' => $unusedCards,
            ];

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Laporan penggunaan berhasil diambil',
            ]);

        } catch (\Exception $e) {
            return $this->getErrorResponse($e, 'Gagal mendapatkan laporan penggunaan');
        }
    }

    /**
     * Update device_id untuk kartu RFID yang belum memiliki device_id
     * Endpoint: POST /admin/rfid-cards/update-device-ids
     */
    public function updateDeviceIds(Request $request)
    {
        try {
            // Validasi input jika ada cards yang spesifik
            if ($request->has('cards')) {
                $validator = Validator::make($request->all(), [
                    'cards' => 'array',
                    'cards.*.id' => 'exists:rfid_cards,id',
                    'cards.*.uid' => 'string',
                    'cards.*.room_id' => 'nullable|exists:rooms,id',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi gagal',
                        'errors' => $validator->errors(),
                    ], 422);
                }
            }

            DB::beginTransaction();

            // Ambil kartu yang belum punya device_id
            $cardsWithoutDevice = RfidCard::whereNull('device_id')
                ->with(['room', 'user'])
                ->get();

            if ($cardsWithoutDevice->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Semua kartu RFID sudah memiliki device_id',
                    'updated_count' => 0,
                    'error_count' => 0,
                ]);
            }

            $updateCount = 0;
            $errorCount = 0;
            $details = [];

            foreach ($cardsWithoutDevice as $card) {
                try {
                    $deviceId = $this->determineDeviceIdForCard($card);

                    if ($deviceId) {
                        $card->update([
                            'device_id' => $deviceId,
                            'access_type' => 'room_only', // Default access type
                        ]);

                        $updateCount++;
                        $details[] = [
                            'card_uid' => $card->uid,
                            'status' => 'updated',
                            'device_id' => $deviceId,
                            'message' => 'Updated successfully',
                        ];

                        Log::info('RFID card device_id updated', [
                            'card_id' => $card->id,
                            'uid' => $card->uid,
                            'device_id' => $deviceId,
                            'updated_by' => Auth::id(),
                        ]);
                    } else {
                        $errorCount++;
                        $details[] = [
                            'card_uid' => $card->uid,
                            'status' => 'error',
                            'message' => 'Could not determine device_id',
                        ];
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $details[] = [
                        'card_uid' => $card->uid,
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ];

                    Log::error('Error updating RFID card device_id', [
                        'card_id' => $card->id,
                        'uid' => $card->uid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            // Log operasi keseluruhan
            Log::info('Bulk RFID device_id update completed', [
                'total_cards' => $cardsWithoutDevice->count(),
                'updated_count' => $updateCount,
                'error_count' => $errorCount,
                'performed_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengupdate {$updateCount} kartu RFID dengan device_id",
                'updated_count' => $updateCount,
                'error_count' => $errorCount,
                'details' => $details,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->getErrorResponse($e, 'Gagal mengupdate device_id kartu RFID');
        }
    }

    /**
     * Check if RFID card user has pending or overdue payments
     */
    private function hasPendingOverduePayments($rfidCard): bool
    {
        if (!$rfidCard || !$rfidCard->user_id) {
            return false;
        }

        // First try direct tenant relationship from RFID card
        if ($rfidCard->tenant_id) {
            $overduePayments = \App\Models\Payment::where('tenant_id', $rfidCard->tenant_id)
                ->where('status', 'overdue')
                ->exists();

            if ($overduePayments) {
                \Illuminate\Support\Facades\Log::info('RFID Access Denied - Direct tenant has overdue payments', [
                    'rfid_uid' => $rfidCard->uid,
                    'user_id' => $rfidCard->user_id,
                    'tenant_id' => $rfidCard->tenant_id,
                ]);
                return true;
            }
        }

        // Fallback: check via user relationship to find active tenant
        $activeTenant = \App\Models\Tenant::where('user_id', $rfidCard->user_id)
            ->where('status', 'active')
            ->first();

        if ($activeTenant) {
            $overduePayments = \App\Models\Payment::where('tenant_id', $activeTenant->id)
                ->where('status', 'overdue')
                ->exists();

            if ($overduePayments) {
                \Illuminate\Support\Facades\Log::info('RFID Access Denied - User tenant has overdue payments', [
                    'rfid_uid' => $rfidCard->uid,
                    'user_id' => $rfidCard->user_id,
                    'tenant_id' => $activeTenant->id,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Tentukan device_id yang tepat untuk kartu RFID
     */
    private function determineDeviceIdForCard(RfidCard $card): ?string
    {
        // Strategi 1: Jika kartu memiliki room_id, cari IoT device untuk room tersebut
        if ($card->room_id) {
            $iotDevice = IoTDevice::where('room_id', $card->room_id)
                ->where('device_type', 'rfid_reader')
                ->first();

            if ($iotDevice) {
                return $iotDevice->device_id;
            }
        }

        // Strategi 2: Default ke ESP32 utama
        // Ini untuk setup single-device yang ada saat ini
        return 'ESP32-RFID-01';
    }
}
