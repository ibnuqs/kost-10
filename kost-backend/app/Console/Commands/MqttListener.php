<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Admin\RfidController;
use App\Services\MqttService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MqttListener extends Command
{
    protected $signature = 'mqtt:listen {--timeout=300 : Timeout in seconds (0 = run until stopped manually)}';

    protected $description = 'Listen to MQTT messages from ESP32 devices and process RFID scans';

    protected $mqttService;

    protected $rfidController;

    public function __construct(MqttService $mqttService)
    {
        parent::__construct();
        $this->mqttService = $mqttService;
        $this->rfidController = app(RfidController::class);
    }

    public function handle()
    {
        $this->info('🚀 MQTT listener starting...');

        try {
            // Connect to MQTT broker
            $this->mqttService->connect();
            $this->info('✅ Connected to HiveMQ Cloud');

            // Subscribe to ESP32 RFID scan topic
            $this->mqttService->subscribe('rfid/tags', function ($topic, $message) {
                $this->info("📱 Received RFID scan from ESP32: {$message}");

                try {
                    $data = json_decode($message, true);

                    if (json_last_error() === JSON_ERROR_NONE && isset($data['uid'])) {
                        // Skip frontend client messages
                        if (isset($data['device_id']) && str_contains($data['device_id'], 'frontend')) {
                            $this->info("📱 Frontend client scan received: {$data['device_id']}");

                            return;
                        }

                        // Process RFID scan with real database
                        $this->info("🔍 Processing UID: {$data['uid']} from device: {$data['device_id']}");
                        $processResult = $this->processRfidScanWithDatabase($data);

                        // Send response back to ESP32
                        $this->sendResponseToESP32($data['uid'], $processResult, $data['device_id'] ?? 'ESP32-RFID-01');

                        $this->info("✅ Processed RFID for card: {$data['uid']} - {$processResult['status']}");

                    } else {
                        $this->error("❌ Invalid JSON or missing UID: {$message}");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error processing RFID: {$e->getMessage()}");
                    Log::error('MQTT RFID processing error', [
                        'message' => $message,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            // Subscribe to ESP32 status updates
            $this->mqttService->subscribe('rfid/status', function ($topic, $message) {
                try {
                    $data = json_decode($message, true);
                    if ($data && isset($data['device_id'])) {
                        // Skip frontend client status messages (too noisy)
                        if (str_contains($data['device_id'], 'frontend')) {
                            return;
                        }

                        // Only show ESP32 device status
                        if (str_contains($data['device_id'], 'ESP32')) {
                            $status = $data['wifi_connected'] ? '🟢 Online' : '🔴 Offline';
                            $this->info("📊 {$data['device_id']}: {$status}");
                        }

                        // Update device status in database
                        $this->updateDeviceStatus($data);
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error processing status: {$e->getMessage()}");
                }
            });

            // Subscribe to door status topic
            $this->mqttService->subscribe('kost_system/door/status', function ($topic, $message) {
                $this->info("🚪 Door status update: {$message}");

                try {
                    $data = json_decode($message, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        Log::info('Door status update received', $data);

                        $status = $data['door_status'] ?? 'unknown';
                        $cardUid = $data['card_uid'] ?? 'N/A';

                        $this->info("   └─ Door {$status} by card: {$cardUid}");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error processing door status: {$e->getMessage()}");
                }
            });

            // Subscribe to system health topic
            $this->mqttService->subscribe('kost_system/health', function ($topic, $message) {
                $this->info("💓 System health: {$message}");
            });

            $this->displayStatus();

            // Run with timeout
            $timeout = (int) $this->option('timeout');
            $this->runWithTimeout($timeout);

        } catch (\Exception $e) {
            $this->error("❌ MQTT listener error: {$e->getMessage()}");
            Log::error('MQTT listener failed', [
                'error' => $e->getMessage(),
            ]);

            return 1; // Return failure
        } finally {
            $this->mqttService->disconnect();
            $this->info('📡 MQTT listener stopped');
        }

        return 0; // Return success
    }

    /**
     * Display listener status information
     */
    private function displayStatus()
    {
        $this->info('📡 MQTT listener ready - monitoring RFID devices');

        $timeout = (int) $this->option('timeout');
        if ($timeout > 0) {
            $minutes = floor($timeout / 60);
            $seconds = $timeout % 60;
            $this->info("⏱️  Running for {$minutes}m {$seconds}s");
        } else {
            $this->info('⏱️  Running indefinitely (use Ctrl+C to stop)');
        }
        $this->info('');
    }

    /**
     * Process RFID scan from ESP32 with strict validation
     * Requirements: device_id + uid + room + user must all match
     */
    private function processRfidScanWithDatabase($data)
    {
        try {
            $scannedDeviceId = $data['device_id'] ?? null;
            $scannedUid = $data['uid'] ?? null;

            if (! $scannedDeviceId || ! $scannedUid) {
                return [
                    'status' => 'denied',
                    'user' => 'System',
                    'message' => 'Invalid scan data',
                    'access_granted' => false,
                ];
            }

            // STEP 1: Find IoT Device and get room
            $iotDevice = \App\Models\IoTDevice::where('device_id', $scannedDeviceId)->first();
            if (! $iotDevice || ! $iotDevice->room_id) {
                return [
                    'status' => 'denied',
                    'user' => 'System',
                    'message' => 'Device not registered or no room assigned',
                    'access_granted' => false,
                ];
            }

            // STEP 2: Find RFID card with tenant-based validation
            $card = \App\Models\RfidCard::where('uid', $scannedUid)
                ->where('status', 'active')
                ->with(['user', 'tenant.room'])
                ->first();

            if (! $card) {
                // Log failed attempt for security
                \App\Models\AccessLog::create([
                    'user_id' => null,
                    'room_id' => $iotDevice->room_id,
                    'rfid_uid' => $scannedUid,
                    'device_id' => $scannedDeviceId,
                    'access_granted' => false,
                    'reason' => 'Card not found or inactive',
                    'accessed_at' => now('Asia/Jakarta'),
                ]);

                return [
                    'status' => 'denied',
                    'user' => 'Unknown',
                    'message' => 'Card not found or inactive',
                    'access_granted' => false,
                ];
            }

            // STEP 3: Check if card has valid tenant and room matches device room
            if (! $card->tenant || ! $card->tenant->room_id) {
                return [
                    'status' => 'denied',
                    'user' => $card->user ? $card->user->name : 'Unknown',
                    'message' => 'Card not assigned to any tenant',
                    'access_granted' => false,
                ];
            }

            if ($card->tenant->room_id !== $iotDevice->room_id) {
                // Log failed attempt for wrong room
                \App\Models\AccessLog::create([
                    'user_id' => $card->user_id,
                    'room_id' => $iotDevice->room_id,
                    'rfid_uid' => $scannedUid,
                    'device_id' => $scannedDeviceId,
                    'access_granted' => false,
                    'reason' => 'Card not authorized for this room',
                    'accessed_at' => now('Asia/Jakarta'),
                ]);

                return [
                    'status' => 'denied',
                    'user' => $card->user ? $card->user->name : 'Unknown',
                    'message' => 'Card not authorized for this room',
                    'access_granted' => false,
                ];
            }

            // STEP 4: Additional validations
            $user = $card->user;
            if (! $user) {
                return [
                    'status' => 'denied',
                    'user' => 'Unknown',
                    'message' => 'Card has no user assigned',
                    'access_granted' => false,
                ];
            }

            // STEP 5: Check if tenant is still active
            if ($card->tenant->status !== \App\Models\Tenant::STATUS_ACTIVE) {
                return [
                    'status' => 'denied',
                    'user' => $user->name,
                    'message' => 'Tenant is no longer active',
                    'access_granted' => false,
                ];
            }

            // STEP 6: Check for overdue payments only (allow pending)
            $overduePayments = \App\Models\Payment::where('tenant_id', $card->tenant->id)
                ->where('status', 'overdue')
                ->get();

            // Console debug output
            $this->info("💰 PAYMENT CHECK for {$user->name} (Tenant {$card->tenant->id}):");
            $this->info("   Overdue payments found: " . $overduePayments->count());
            foreach ($overduePayments as $payment) {
                $this->info("   - Payment {$payment->id}: {$payment->status} ({$payment->payment_month})");
            }

            \Illuminate\Support\Facades\Log::info('RFID Payment Check', [
                'card_uid' => $scannedUid,
                'tenant_id' => $card->tenant->id,
                'user_name' => $user->name,
                'overdue_count' => $overduePayments->count(),
                'overdue_payments' => $overduePayments->pluck('id', 'status')->toArray(),
            ]);

            if ($overduePayments->count() > 0) {
                // Log access denied due to unpaid payments
                \App\Models\AccessLog::create([
                    'user_id' => $user->id,
                    'room_id' => $iotDevice->room_id,
                    'rfid_uid' => $scannedUid,
                    'device_id' => $scannedDeviceId,
                    'access_granted' => false,
                    'reason' => 'Access denied: overdue payments',
                    'accessed_at' => now('Asia/Jakarta'),
                ]);

                return [
                    'status' => 'denied',
                    'user' => $user->name,
                    'message' => 'Access denied: You have overdue payments',
                    'access_granted' => false,
                ];
            }

            // STEP 7: SUCCESS - All validations passed
            $response = [
                'status' => 'granted',
                'user' => $user->name,
                'message' => "Welcome, {$user->name}! Room {$card->tenant->room->room_number}",
                'access_granted' => true,
            ];

            // Log successful access
            \App\Models\AccessLog::create([
                'user_id' => $user->id,
                'room_id' => $iotDevice->room_id,
                'rfid_uid' => $scannedUid,
                'device_id' => $scannedDeviceId,
                'access_granted' => true,
                'reason' => $response['message'],
                'accessed_at' => now('Asia/Jakarta'),
            ]);

            $this->info("✅ ACCESS GRANTED: {$user->name} → Room {$card->tenant->room->room_number} via {$scannedDeviceId}");

            return $response;

        } catch (\Exception $e) {
            Log::error('RFID validation error', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'user' => 'System',
                'message' => 'System validation error',
                'access_granted' => false,
            ];
        }
    }

    /**
     * Send response back to ESP32 via MQTT
     */
    private function sendResponseToESP32($uid, $result, $deviceId = 'ESP32-RFID-01')
    {
        try {
            $responseData = [
                'uid' => $uid,
                'status' => $result['status'],
                'user' => $result['user'],
                'message' => $result['message'],
                'access_granted' => $result['access_granted'],
                'device_id' => $deviceId,
                'timestamp' => time(),
            ];

            $message = json_encode($responseData);

            // Send to command topic that ESP32 listens to
            $this->mqttService->publish('rfid/command', $message);

            $this->info("📤 Response sent to ESP32: {$result['status']} for {$result['user']}");

        } catch (\Exception $e) {
            $this->error("❌ Failed to send response to ESP32: {$e->getMessage()}");
        }
    }

    /**
     * Update device status in database
     */
    private function updateDeviceStatus($data)
    {
        try {
            // Skip frontend clients
            if (isset($data['device_id']) && str_contains($data['device_id'], 'frontend')) {
                return;
            }

            $deviceId = $data['device_id'] ?? 'ESP32-RFID-01';

            // Find or create IoT device
            $device = \App\Models\IoTDevice::firstOrCreate(
                ['device_id' => $deviceId],
                [
                    'device_name' => $data['device_name'] ?? 'ESP32 RFID Reader',
                    'device_type' => 'rfid_reader',
                    'status' => 'online',
                    'room_id' => null,
                ]
            );

            // Update device status with WIB timezone
            $device->update([
                'status' => ($data['wifi_connected'] && $data['mqtt_connected']) ? 'online' : 'offline',
                'last_seen' => now('Asia/Jakarta'),
                'device_info' => json_encode([
                    'wifi_connected' => $data['wifi_connected'] ?? false,
                    'mqtt_connected' => $data['mqtt_connected'] ?? false,
                    'rfid_ready' => $data['rfid_ready'] ?? false,
                    'device_ip' => $data['device_ip'] ?? null,
                    'uptime' => $data['uptime'] ?? null,
                    'firmware_version' => $data['firmware_version'] ?? null,
                    'door_status' => $data['door_status'] ?? 'unknown',
                    'wifi_ssid' => $data['wifi_ssid'] ?? null,
                    'rssi' => $data['rssi'] ?? null,
                    'free_heap' => $data['free_heap'] ?? null,
                    'last_updated' => now('Asia/Jakarta'),
                ]),
            ]);

            $this->info("💾 Updated device status for {$deviceId}");

        } catch (\Exception $e) {
            $this->error("❌ Failed to update device status: {$e->getMessage()}");
            Log::error('Device status update error', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Run the listener with timeout
     */
    private function runWithTimeout($timeout)
    {
        $startTime = time();
        $lastProgressUpdate = 0;

        while (true) {
            // Process MQTT messages
            $this->mqttService->loop(1);

            // Check timeout
            $elapsed = time() - $startTime;
            if ($timeout > 0 && $elapsed >= $timeout) {
                $this->info('⏰ Timeout reached, stopping listener.');
                break;
            }

            // Show progress every 30 seconds
            if ($timeout > 0 && ($elapsed - $lastProgressUpdate) >= 30) {
                $remaining = $timeout - $elapsed;
                $minutes = floor($remaining / 60);
                $seconds = $remaining % 60;
                $this->info("⏱️  Time remaining: {$minutes}m {$seconds}s");
                $lastProgressUpdate = $elapsed;
            }

            // Small delay to prevent high CPU usage
            usleep(100000); // 100ms
        }
    }
}

// Alternative simple version for quick testing
/*
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;
use App\Http\Controllers\Api\Admin\RfidController;
use Illuminate\Http\Request;

class MqttListener extends Command
{
    protected $signature = 'mqtt:listen {--timeout=60}';
    protected $description = 'Listen to MQTT messages from ESP32';

    protected $mqttService;
    protected $rfidController;

    public function __construct(MqttService $mqttService)
    {
        parent::__construct();
        $this->mqttService = $mqttService;
        $this->rfidController = app(RfidController::class);
    }

    public function handle()
    {
        $this->info('🚀 Starting MQTT listener...');

        try {
            $this->mqttService->connect();
            $this->info('✅ Connected to MQTT broker');

            $this->mqttService->subscribe('kost_system/rfid/read', function ($topic, $message) {
                $this->info("📱 Received: {$message}");

                $data = json_decode($message, true);
                if ($data) {
                    $request = new Request($data);
                    $this->rfidController->processRfidFromESP32($request);
                    $this->info("✅ Processed card: {$data['card_uid']}");
                }
            });

            $timeout = (int) $this->option('timeout');
            $this->info("📡 Listening for {$timeout} seconds...");

            $end = time() + $timeout;
            while (time() < $end) {
                $this->mqttService->loop(1);
                usleep(100000);
            }

        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        } finally {
            $this->mqttService->disconnect();
        }

        return 0;
    }
}
*/
