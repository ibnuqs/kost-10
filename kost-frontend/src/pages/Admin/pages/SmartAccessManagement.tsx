// File: src/pages/Admin/pages/SmartAccessManagement.tsx
import React, { useState, useEffect, useMemo } from 'react';
import { Button } from '../components/ui/Forms/Button';
import {
  RfidTable,
  RfidRealTimeMonitor,
  AdminDoorControl,
} from '../components/feature/rfid';
import { RfidFormNew } from '../components/feature/rfid/RfidFormNew';
import { WorkingAnalytics } from '../components/feature/rfid/WorkingAnalytics';
import { Shield, CreditCard, Activity, DoorOpen, BarChart3, Plus } from 'lucide-react';
import { useRfidEvents } from '../../../hooks';
import { useHttpRfidPolling } from '../../../hooks/useHttpRfidPolling';
import { esp32Service } from '../services/esp32Service';
import { roomService } from '../services/roomService';
import type { RfidCard, RfidFormData, AdminDoorControlRequest } from '../types/rfid';
import type { Room } from '../types/room';

type TabType = 'cards' | 'monitor' | 'door-control' | 'logs';

export const SmartAccessManagement: React.FC = () => {
  const [activeTab, setActiveTab] = useState<TabType>('monitor');
  const [showCardForm, setShowCardForm] = useState(false);
  const [selectedCard, setSelectedCard] = useState<RfidCard | null>(null);
  const [cards, setCards] = useState<RfidCard[]>([]);
  const [rooms, setRooms] = useState<Room[]>([]);
  const [devices, setDevices] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [cardsLoading, setCardsLoading] = useState(true);
  const [roomsLoading, setRoomsLoading] = useState(true);
  const [devicesLoading, setDevicesLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  
  // Add caching
  const [lastLoadTime, setLastLoadTime] = useState<number>(0);
  const CACHE_DURATION = 30000; // 30 seconds
  const [stats, setStats] = useState({
    totalCards: 0,
    activeCards: 0,
    inactiveCards: 0,
    accessGrantedToday: 0,
    accessDeniedToday: 0,
    totalAccessToday: 0,
    successRate: 0
  });

  // Real-time MQTT data (fallback to HTTP polling if MQTT disabled)
  const mqttData = useRfidEvents();
  const httpData = useHttpRfidPolling();
  
  // Use MQTT data if available, otherwise fallback to HTTP polling
  const { recentScans, deviceStatuses, isConnected } = import.meta.env.VITE_MQTT_ENABLED === 'false' 
    ? httpData 
    : mqttData;


  useEffect(() => {
    loadData();
  }, []);

  // ULTRA-OPTIMIZED: Pre-computed room-device mapping with caching & memoization
  const availableRoomsForDoorControl = useMemo(() => {
    // Early return if no data
    if (!rooms.length || !deviceStatuses?.size) {
      return [];
    }

    const now = Date.now();
    const twoMinutesAgo = now - (2 * 60 * 1000); // Increase tolerance for better ESP32 detection

    // BLAZING FAST: Pre-build optimized lookup structures
    const onlineDeviceSet = new Set();
    const roomToDeviceMap = new Map();
    const deviceMetadata = new Map(); // Cache device info for reuse
    
    // Single optimized pass through devices
    for (const [deviceId, status] of deviceStatuses.entries()) {
      // Optimized online check with better tolerance
      const isOnline = status?.last_seen && 
        new Date(status.last_seen).getTime() > twoMinutesAgo;
      
      if (isOnline) {
        onlineDeviceSet.add(deviceId);
        
        // Enhanced regex for better ESP32 device matching
        const roomMatch = deviceId.match(/(?:ROOM[_-]?(\d+)|R(\d+)|ESP32[_-]?(\d+)|KOST[_-]?(\d+))/i);
        if (roomMatch) {
          const roomNum = parseInt(roomMatch[1] || roomMatch[2] || roomMatch[3] || roomMatch[4]);
          if (roomNum && !isNaN(roomNum)) {
            roomToDeviceMap.set(roomNum, deviceId);
            deviceMetadata.set(deviceId, {
              last_seen: status.last_seen,
              wifi_connected: status?.wifi_connected,
              mqtt_connected: status?.mqtt_connected
            });
          }
        }
      }
    }

    // OPTIMIZED: Multi-strategy room filtering with priority
    const filteredRooms = rooms.filter(room => {
      // Strategy 1: Direct MQTT device mapping (fastest)
      if (roomToDeviceMap.has(room.room_number)) {
        return true;
      }
      
      // Strategy 2: Backend device mapping (fallback)
      const hasBackendDevice = devices.some(d => {
        const deviceOnline = onlineDeviceSet.has(d.device_id);
        const roomMatches = d.room_id === room.id || 
                           d.room_number === room.room_number ||
                           (d.device_name && d.device_name.includes(room.room_number.toString()));
        return deviceOnline && roomMatches;
      });
      
      return hasBackendDevice;
    })
    .sort((a, b) => {
      // Sort by room number for consistent UI
      return a.room_number - b.room_number;
    });

    
    return filteredRooms;
  }, [rooms, deviceStatuses, devices]);

  // WorkingAnalytics is always mounted (hidden) to maintain MQTT connection

  // Optimized tab rendering - avoid re-renders when switching tabs
  const renderActiveTab = () => {
    switch (activeTab) {
      case 'monitor':
        return (
          <div className="animate-fadeIn">
            <RfidRealTimeMonitor />
          </div>
        );
      case 'cards':
        return (
          <div className="animate-fadeIn">
            <div className="bg-white rounded-xl shadow-sm border border-gray-100">
              <div className="p-6 border-b border-gray-100">
                <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                  <div>
                    <h3 className="text-lg font-semibold flex items-center gap-2">
                      <CreditCard className="w-5 h-5 text-purple-600" />
                      Manajemen Kartu RFID
                    </h3>
                    <p className="text-gray-600 mt-1">Tambah, edit, dan kelola kartu akses</p>
                  </div>
                  <Button
                    onClick={() => setShowCardForm(true)}
                    className="bg-purple-600 hover:bg-purple-700 text-white"
                  >
                    <Plus className="w-4 h-4 mr-2" />
                    Tambah Kartu Baru
                  </Button>
                </div>
              </div>
              <div className="p-6">
                <RfidTable 
                  cards={cards.filter(card => 
                    !searchTerm || 
                    card.uid.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    card.user?.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    card.user?.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    card.room?.room_number?.toString().includes(searchTerm)
                  )}
                  loading={cardsLoading}
                  searchTerm={searchTerm}
                  onSearchChange={setSearchTerm}
                  onEdit={handleEditCard}
                  onDelete={handleDeleteCard}
                  onToggleStatus={async (cardId) => {
                    const card = cards.find(c => c.id === cardId);
                    if (card) {
                      await esp32Service.updateRfidCard(card.id, {
                        ...card,
                        status: card.status === 'active' ? 'inactive' : 'active'
                      });
                      loadData(true);
                    }
                  }}
                />
              </div>
            </div>
          </div>
        );
      case 'door-control':
        return (
          <div className="animate-fadeIn">
            <AdminDoorControl 
              rooms={availableRoomsForDoorControl}
              onDoorControl={handleDoorControl}
            />
          </div>
        );
      case 'logs':
        return (
          <div className="animate-fadeIn">
            <WorkingAnalytics />
          </div>
        );
      default:
        return null;
    }
  };

  // Update stats when MQTT data changes  
  useEffect(() => {
    // Load from backend if no MQTT data available
    const loadAccessStats = async () => {
      try {
        const { accessLogService } = await import('../services/accessLogService');
        const response = await accessLogService.getLogs({ per_page: 100 }); // Balanced performance and data coverage
        
        if (response?.logs && Array.isArray(response.logs)) {
          const today = new Date().toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' });
          const todayScans = response.logs.filter(log => 
            new Date(log.accessed_at).toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' }) === today
          );
          const grantedToday = todayScans.filter(log => {
            return log.access_granted === true || log.access_granted === 'true' || log.access_granted === 1 || log.access_granted === '1';
          }).length;
          const totalAccessToday = todayScans.length;
          const successRate = totalAccessToday > 0 ? Math.round((grantedToday / totalAccessToday) * 100) : 0;

          setStats(prev => ({
            ...prev,
            accessGrantedToday: grantedToday,
            totalAccessToday,
            successRate
          }));
        }
      } catch (error: unknown) {
        // Fallback to default stats
      }
    };
    
    if (recentScans && Array.isArray(recentScans) && recentScans.length > 0) {
      // Use MQTT data if available
      const today = new Date().toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' });
      const todayScans = recentScans.filter(s => 
        new Date(s.timestamp).toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' }) === today
      );
      const grantedToday = todayScans.filter(s => {
        return s.access_granted === true || s.access_granted === 'true' || s.access_granted === 1 || s.access_granted === '1';
      }).length;
      const totalAccessToday = todayScans.length;
      const successRate = totalAccessToday > 0 ? Math.round((grantedToday / totalAccessToday) * 100) : 0;

      setStats(prev => ({
        ...prev,
        accessGrantedToday: grantedToday,
        totalAccessToday,
        successRate
      }));
    } else {
      // Fallback to backend data
      loadAccessStats();
    }
  }, [recentScans]);

  const loadData = async (forceRefresh = false) => {
    const now = Date.now();
    
    // Skip loading if recently loaded (unless forced)
    if (!forceRefresh && now - lastLoadTime < CACHE_DURATION && (cards.length > 0 || rooms.length > 0)) {
      return;
    }

    setLoading(true);

    // INSTANT: Load MQTT device data (no API call needed)
    const mqttDevices = Array.from(deviceStatuses?.entries() || []).map(([deviceId, status]) => ({
      device_id: deviceId,
      id: deviceId,
      device_name: deviceId,
      status: 'online',
      wifi_connected: true,
      mqtt_connected: true,
      last_seen: status.last_seen
    }));
    
    if (mqttDevices.length > 0) {
      setDevices(mqttDevices);
      setDevicesLoading(false);
    }

    // PARALLEL LOADING: Load all data simultaneously (non-blocking)
    const loadPromises = [];

    // Cards loading
    setCardsLoading(true);
    const cardsPromise = esp32Service.getRfidCards()
      .then(cardsData => {
        const cards = Array.isArray(cardsData) ? cardsData : [];
        setCards(cards);
        setCardsLoading(false);
        
        // Update stats immediately
        const totalCards = cards.length;
        const activeCount = cards.filter(c => c.status === 'active').length;
        setStats(prev => ({
          ...prev,
          totalCards,
          activeCards: activeCount,
          inactiveCards: totalCards - activeCount
        }));
        
        return cards;
      })
      .catch(error => {
        setCards([]);
        setCardsLoading(false);
        return [];
      });
    loadPromises.push(cardsPromise);

    // Rooms loading (PRIORITY: needed for door control)
    setRoomsLoading(true);
    const roomsPromise = roomService.getRooms()
      .then(roomsData => {
        const roomsArray = Array.isArray(roomsData) ? roomsData : (roomsData?.rooms || []);
        console.log('[DEBUG] Rooms data from API:', roomsArray); // <-- DEBUG
        setRooms(roomsArray);
        setRoomsLoading(false);
        return roomsArray;
      })
      .catch(error => {
        setRooms([]);
        setRoomsLoading(false);
        return [];
      });
    loadPromises.push(roomsPromise);

    // Backend devices (if MQTT not available)
    if (mqttDevices.length === 0) {
      setDevicesLoading(true);
      const devicesPromise = esp32Service.getDevices()
        .then(devicesData => {
          const devices = Array.isArray(devicesData) ? devicesData : [];
          setDevices(devices);
          setDevicesLoading(false);
          return devices;
        })
        .catch(error => {
          setDevicesLoading(false);
          return [];
        });
      loadPromises.push(devicesPromise);
    }

    // FAST: Hide loading as soon as rooms are ready (most critical for door control)
    roomsPromise.finally(() => {
      setLoading(false);
      setLastLoadTime(now);
    });

    // Wait for all non-critical data in background
    Promise.allSettled(loadPromises);
  };

  const handleCardSubmit = async (data: RfidFormData) => {
    try {
      let result;
      
      if (selectedCard) {
        // Update existing card - merge with existing card data to preserve status
        const updateData = {
          ...selectedCard,
          ...data,
          status: data.status || selectedCard.status || 'active'
        };
        result = await esp32Service.updateRfidCard(selectedCard.id, updateData);
      } else {
        // Create new card
        const createData = {
          ...data,
          status: data.status || 'active'
        };
        result = await esp32Service.createRfidCard(createData);
      }
      
      if (result !== null) {
        setShowCardForm(false);
        setSelectedCard(null);
        loadData();
      } else {
        alert('Gagal menyimpan kartu RFID.');
      }
    } catch (error: unknown) {
      alert(`Gagal menyimpan kartu: ${error instanceof Error ? error.message : 'Kesalahan tidak dikenal'}`);
    }
  };

  const handleEditCard = (card: RfidCard) => {
    setSelectedCard(card);
    setShowCardForm(true);
  };

  const handleDeleteCard = async (cardId: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus kartu ini?')) {
      try {
        await esp32Service.deleteRfidCard(cardId);
        loadData();
      } catch (error: unknown) {
        alert('Gagal menghapus kartu RFID.');
      }
    }
  };

  const handleDoorControl = async (request: AdminDoorControlRequest): Promise<boolean> => {
    try {
      // Find the room to get associated IoT device
      const room = rooms.find(r => r.id === request.room_id);
      if (!room) {
        return false;
      }
      
      
      // Find device that's actually online via MQTT for this room
      let associatedDevice = null;
      let mqttDeviceId = null;
      
      // Look for MQTT device first
      for (const [deviceId, deviceStatus] of deviceStatuses?.entries() || []) {
        const isAssociated = 
          deviceId.includes(`ROOM_${room.room_number}`) ||
          deviceId.includes(`R${room.room_number}`) ||
          // More precise matching - avoid partial matches
          (deviceId.includes(room.room_number) && 
           (deviceId.includes('ROOM') || deviceId.includes('ESP32'))) ||
          devices.some(backendDevice => 
            (backendDevice.device_id === deviceId || backendDevice.id === deviceId) &&
            (backendDevice.room_id === room.id || 
             backendDevice.room_number === room.room_number ||
             (backendDevice.device_name && backendDevice.device_name.includes(room.room_number)))
          );
        
        const isRecentlySeen = (() => {
          if (!deviceStatus?.last_seen) return false;
          const lastSeenTime = new Date(deviceStatus.last_seen).getTime();
          const now = Date.now();
          const oneMinuteAgo = now - (1 * 60 * 1000);
          return lastSeenTime > oneMinuteAgo;
        })();
        
        if (isAssociated && isRecentlySeen) {
          mqttDeviceId = deviceId;
          // Find corresponding backend device for more info
          associatedDevice = devices.find(d => d.device_id === deviceId || d.id === deviceId) || {
            device_id: deviceId,
            id: deviceId,
            device_name: deviceId
          };
          break;
        }
      }
      
      if (!associatedDevice || !mqttDeviceId) {
        alert(`Tidak ada device ESP32 yang online untuk kamar ${room.room_number}`);
        return false;
      }
      
      const deviceId = mqttDeviceId;
      
      // Send MQTT command to ESP32 device
      if (request.action === 'open_door') {
        esp32Service.sendDoorOpenCommand(deviceId);
      } else if (request.action === 'close_door') {
        esp32Service.sendDoorCloseCommand(deviceId);
      }
      
      // Also try API endpoint as backup
      try {
        const apiResponse = await esp32Service.sendCommand(deviceId, request.action, {
          room_id: request.room_id,
          reason: request.reason
        });
      } catch (apiError) {
        // API backup failed, but MQTT command was sent
      }

      // Send manual door control log via MQTT (same format as ESP32)
      try {
        const mqttService = (window as { mqttService?: { publish: (topic: string, message: string) => void } }).mqttService;
        
        if (mqttService) {
          const logMessage = {
            id: `manual-${Date.now()}`,
            uid: 'MANUAL_ADMIN',
            device_id: deviceId,
            access_granted: true,
            reason: request.reason || `Manual ${request.action.replace('_', ' ')} by admin`,
            user_name: 'Admin',
            room_number: room.room_number,
            timestamp: Date.now(),
            accessed_at: new Date().toISOString(),
            type: 'manual_door_control',
            message: request.reason || `Manual ${request.action.replace('_', ' ')} by admin`,
            user: {
              name: 'Admin',
              email: 'admin@system.local'
            }
          };
          
          // Publish to a dedicated topic for manual control logs
          mqttService.publish('rfid/manual_control', JSON.stringify(logMessage));
          
          // Force refresh WorkingAnalytics by triggering a window event
          window.dispatchEvent(new CustomEvent('manual-door-log', { detail: logMessage }));
        }
      } catch (mqttLogError) {
        // MQTT logging failed - continue without logging
      }

      return true;
    } catch (error) {
      return false;
    }
  };

  const tabs = [
    { id: 'monitor' as TabType, label: 'Monitor Langsung', description: 'Pemindaian RFID real-time', icon: Activity },
    { id: 'cards' as TabType, label: 'Kartu RFID', description: 'Kelola kartu akses', icon: CreditCard },
    { id: 'door-control' as TabType, label: 'Kontrol Pintu', description: 'Akses pintu manual', icon: DoorOpen },
    { id: 'logs' as TabType, label: 'Log Akses', description: 'Monitoring aktivitas', icon: BarChart3 }
  ];

  return (
    <div className="min-h-screen bg-gray-50 p-4 lg:p-6">
      <div className="max-w-7xl mx-auto space-y-6">
        {/* Page Header */}
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h1 className="text-2xl lg:text-3xl font-bold text-gray-900 flex items-center gap-3">
              <div className="p-2 bg-blue-100 rounded-lg">
                <Shield className="w-8 h-8 text-blue-600" />
              </div>
              Manajemen Akses Pintar
            </h1>
            <p className="text-gray-600 mt-1 text-sm lg:text-base">
              Sistem kontrol akses RFID, manajemen pintu, dan monitoring lengkap
            </p>
          </div>
          
          <Button 
            variant="outline" 
            onClick={() => loadData(true)}
            disabled={loading}
            className="text-xs lg:text-sm"
          >
            Segarkan
          </Button>
        </div>

        {/* Enhanced Quick Stats */}
        <div className="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
          <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Total Kartu</p>
                {cardsLoading ? (
                  <div className="flex items-center gap-2">
                    <div className="h-8 w-12 bg-gray-200 rounded animate-pulse"></div>
                    <div className="h-3 w-16 bg-gray-200 rounded animate-pulse"></div>
                  </div>
                ) : (
                  <p className="text-2xl font-bold text-gray-900">{stats.totalCards}</p>
                )}
              </div>
              <div className="p-3 bg-blue-100 rounded-full">
                <CreditCard className="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Kartu Aktif</p>
                <p className="text-2xl font-bold text-green-600">{stats.activeCards}</p>
              </div>
              <div className="p-3 bg-green-100 rounded-full">
                <Shield className="w-6 h-6 text-green-600" />
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Kartu Nonaktif</p>
                <p className="text-2xl font-bold text-gray-500">{stats.inactiveCards}</p>
              </div>
              <div className="p-3 bg-gray-100 rounded-full">
                <CreditCard className="w-6 h-6 text-gray-500" />
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Akses Hari Ini</p>
                <p className="text-2xl font-bold text-blue-600">{stats.totalAccessToday}</p>
              </div>
              <div className="p-3 bg-blue-100 rounded-full">
                <Activity className="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Koneksi</p>
                <p className={`text-lg font-bold ${isConnected ? 'text-green-600' : 'text-red-600'}`}>
                  {isConnected ? 'Terhubung' : 'Terputus'}
                </p>
              </div>
              <div className={`p-3 rounded-full ${isConnected ? 'bg-green-100' : 'bg-red-100'}`}>
                <div className={`w-6 h-6 rounded-full ${isConnected ? 'bg-green-500' : 'bg-red-500'}`}></div>
              </div>
            </div>
          </div>
        </div>

        {/* Enhanced Navigation Tabs */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          {/* Desktop Tabs */}
          <div className="hidden md:flex">
            {tabs.map(tab => {
              const Icon = tab.icon;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`flex-1 p-4 text-left transition-all duration-200 ${
                    activeTab === tab.id
                      ? 'bg-blue-50 border-b-2 border-blue-500'
                      : 'hover:bg-gray-50 border-b-2 border-transparent'
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <div className={`p-2 rounded-lg ${
                      activeTab === tab.id ? 'bg-blue-100' : 'bg-gray-100'
                    }`}>
                      <Icon className={`w-5 h-5 ${
                        activeTab === tab.id ? 'text-blue-600' : 'text-gray-500'
                      }`} />
                    </div>
                    <div>
                      <div className={`font-medium ${
                        activeTab === tab.id ? 'text-blue-900' : 'text-gray-900'
                      }`}>
                        {tab.label}
                      </div>
                      <div className="text-xs text-gray-500 mt-1">
                        {tab.description}
                      </div>
                    </div>
                  </div>
                </button>
              );
            })}
          </div>
          
          {/* Mobile Tabs */}
          <div className="md:hidden p-4">
            <div className="relative">
              <select 
                value={activeTab} 
                onChange={(e) => setActiveTab(e.target.value as TabType)}
                className="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
              >
                {tabs.map(tab => (
                  <option key={tab.id} value={tab.id}>
                    {tab.label} - {tab.description}
                  </option>
                ))}
              </select>
              <div className="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        {/* Tab Content */}
        <div className="min-h-[600px]">
          {renderActiveTab()}
        </div>

        {/* Hidden WorkingAnalytics - Always mounted to listen to MQTT */}
        <div style={{ display: 'none', position: 'absolute', top: '-9999px' }}>
          <WorkingAnalytics />
        </div>

        {/* RFID Card Form Modal */}
        <RfidFormNew
          isOpen={showCardForm}
          card={selectedCard}
          onClose={() => {
            setShowCardForm(false);
            setSelectedCard(null);
          }}
          onSubmit={handleCardSubmit}
        />

      </div>
    </div>
  );
};