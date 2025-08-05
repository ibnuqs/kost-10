// ===================================================================
// tenantService.ts - Tenant API Service v3.2 (CLEAN)
// ===================================================================

import api, { endpoints, ApiResponse } from '../../../utils/api';
import { DashboardData } from '../types/dashboard';
import { tenantApiConfig } from '../config/apiConfig';
import { 
  Payment, 
  PaymentSummary
} from '../types/payment';
import { 
  User
} from '../types/common';
import { Notification } from '../types/notification';

// ===================================================================
// TYPES & INTERFACES
// ===================================================================

export interface TenantProfileUpdateData {
  name: string;
  phone?: string;
  emergency_contact?: string;
  emergency_phone?: string;
}

export interface PaymentHistoryParams {
  page?: number;
  per_page?: number;
  status?: string;
  month?: string;
}

export interface AccessHistoryParams {
  page?: number;
  per_page?: number;
  date_from?: string;
  date_to?: string;
}

export interface NotificationParams {
  page?: number;
  per_page?: number;
  unread_only?: boolean;
}

export interface CardRequestData {
  card_type?: string;
  reason?: string;
}

// Interface definitions for API responses
export interface ProfileUpdateResponse {
  user: User;
  message: string;
}

export interface PaymentHistoryResponse {
  data: Payment[];
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface AccessHistoryResponse {
  data: AccessLogEntry[];
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface AccessStatsResponse {
  today: number;
  week: number;
  month: number;
  total: number;
  last_access: string | null;
}

export interface RfidCardResponse {
  id: number;
  card_number: string;
  status: 'active' | 'inactive' | 'lost' | 'suspended';
  tenant_id: number;
  device_id?: string;
  created_at: string;
  updated_at: string;
}

export interface IoTDeviceResponse {
  id: number;
  device_id: string;
  device_name: string;
  device_type: string;
  room_id?: number;
  status: 'online' | 'offline' | 'maintenance';
  last_seen?: string;
  created_at: string;
  updated_at: string;
}

export interface DeviceStatusResponse {
  device_id: string;
  status: 'online' | 'offline' | 'maintenance';
  last_seen: string;
  battery_level?: number;
  signal_strength?: number;
  temperature?: number;
  humidity?: number;
}

export interface NotificationListResponse {
  notifications: Notification[];
  total: number;
}

export interface CardRequestResponse {
  request_id: number;
  status: 'pending' | 'approved' | 'rejected';
  message: string;
}

export interface CardStatusUpdateResponse {
  card: RfidCardResponse;
  message: string;
}

export interface DoorControlResponse {
  success: boolean;
  message: string;
  door_status?: 'open' | 'closed';
  opened_at?: string;
}

export interface DoorStatusResponse {
  door_status: 'open' | 'closed';
  last_access?: string;
  access_count_today: number;
}

export interface AccessLogEntry {
  id: number;
  tenant_id: number;
  device_id: string;
  access_type: 'rfid' | 'manual' | 'app';
  status: 'granted' | 'denied';
  timestamp: string;
  door_name?: string;
  room_number?: string;
}

export interface DebugTenantAccessResponse {
  tenant_id: number;
  access_granted: boolean;
  reason: string;
  active_cards: number;
  room_access: boolean;
}

// ===================================================================
// TENANT SERVICE CLASS
// ===================================================================

class TenantService {
  
  // ===================================================================
  // DASHBOARD & PROFILE
  // ===================================================================

  /**
   * ✅ FIXED: Get dashboard data - main endpoint
   */
  async getDashboardData(): Promise<DashboardData> {
    try {
      // ✅ Use correct dashboard endpoint
      const response = await api.get<ApiResponse<DashboardData>>(endpoints.tenant.dashboard);
      
      return this.handleResponse(response, 'Failed to fetch dashboard data');
    } catch (error: unknown) {
      return this.handleError(error, 'getDashboardData');
    }
  }

  /**
   * Update tenant profile
   */
  async updateProfile(data: TenantProfileUpdateData): Promise<ProfileUpdateResponse> {
    try {
      const response = await api.put<ApiResponse<ProfileUpdateResponse>>(endpoints.tenant.profile.update, data);
      return this.handleResponse<ProfileUpdateResponse>(response, 'Failed to update profile');
    } catch (error: unknown) {
      return this.handleError(error, 'updateProfile');
    }
  }

  /**
   * Refresh dashboard data (alias)
   */
  async refreshDashboard(): Promise<DashboardData> {
    return this.getDashboardData();
  }

  // ===================================================================
  // PAYMENTS
  // ===================================================================

  /**
   * Get payment history
   */
  async getPaymentHistory(params?: PaymentHistoryParams): Promise<PaymentHistoryResponse> {
    try {
      const response = await api.get<ApiResponse<PaymentHistoryResponse>>(endpoints.tenant.payments.index, { params });
      return this.handleResponse<PaymentHistoryResponse>(response, 'Failed to fetch payment history');
    } catch (error: unknown) {
      return this.handleError(error, 'getPaymentHistory');
    }
  }

  /**
   * Get payments (alias for getPaymentHistory)
   */
  async getPayments(params?: PaymentHistoryParams): Promise<PaymentHistoryResponse> {
    return this.getPaymentHistory(params);
  }

  /**
   * Get payment summary
   */
  async getPaymentSummary(): Promise<PaymentSummary> {
    try {
      const response = await api.get<ApiResponse<PaymentSummary>>(endpoints.tenant.payments.summary);
      return this.handleResponse<PaymentSummary>(response, 'Failed to fetch payment summary');
    } catch (error: unknown) {
      return this.handleError(error, 'getPaymentSummary');
    }
  }

  /**
   * Get payment URL for specific payment
   */
  async getPaymentUrl(paymentId: string): Promise<{ payment_url: string }> {
    try {
      const response = await api.get<ApiResponse<{ payment_url: string }>>(
        endpoints.tenant.payments.paymentUrl(paymentId)
      );
      return this.handleResponse(response, 'Failed to get payment URL');
    } catch (error: unknown) {
      return this.handleError(error, 'getPaymentUrl');
    }
  }

  // ===================================================================
  // ACCESS LOGS
  // ===================================================================

  /**
   * Get access history
   */
  async getAccessHistory(params?: AccessHistoryParams): Promise<AccessHistoryResponse> {
    try {
      const response = await api.get<ApiResponse<AccessHistoryResponse>>(endpoints.tenant.access.history, { params });
      return this.handleResponse<AccessHistoryResponse>(response, 'Failed to fetch access history');
    } catch (error: unknown) {
      return this.handleError(error, 'getAccessHistory');
    }
  }

  /**
   * Get access statistics
   */
  async getAccessStats(): Promise<AccessStatsResponse> {
    try {
      const response = await api.get<ApiResponse<AccessStatsResponse>>(endpoints.tenant.access.stats);
      return this.handleResponse<AccessStatsResponse>(response, 'Failed to fetch access stats');
    } catch (error: unknown) {
      return this.handleError(error, 'getAccessStats');
    }
  }

  // ===================================================================
  // RFID CARDS
  // ===================================================================

  /**
   * Get RFID cards
   */
  async getRfidCards(): Promise<RfidCardResponse[]> {
    try {
      const response = await api.get<ApiResponse<RfidCardResponse[]>>(endpoints.tenant.rfid.cards);
      return this.handleResponse<RfidCardResponse[]>(response, 'Failed to fetch RFID cards');
    } catch (error: unknown) {
      return this.handleError(error, 'getRfidCards');
    }
  }

  /**
   * Request new RFID card
   */
  async requestNewCard(data: CardRequestData): Promise<CardRequestResponse> {
    try {
      const response = await api.post<ApiResponse<CardRequestResponse>>(endpoints.tenant.rfid.requestCard, data);
      return this.handleResponse<CardRequestResponse>(response, 'Failed to request new card');
    } catch (error: unknown) {
      return this.handleError(error, 'requestNewCard');
    }
  }

  /**
   * Report lost RFID card
   */
  async reportLostCard(cardId: string, reason?: string): Promise<CardStatusUpdateResponse> {
    try {
      const response = await api.post<ApiResponse<CardStatusUpdateResponse>>(endpoints.tenant.rfid.reportLost, {
        card_id: cardId,
        reason
      });
      return this.handleResponse<CardStatusUpdateResponse>(response, 'Failed to report lost card');
    } catch (error: unknown) {
      return this.handleError(error, 'reportLostCard');
    }
  }

  /**
   * Toggle RFID card status (activate/deactivate)
   */
  async toggleCardStatus(cardId: number, status: 'active' | 'inactive'): Promise<CardStatusUpdateResponse> {
    try {
      const response = await api.put<ApiResponse<CardStatusUpdateResponse>>(`${endpoints.tenant.rfid.cards}/${cardId}/status`, {
        status: status
      });
      return this.handleResponse<CardStatusUpdateResponse>(response, 'Failed to update card status');
    } catch (error: unknown) {
      return this.handleError(error, 'toggleCardStatus');
    }
  }

  // ===================================================================
  // IOT DEVICES
  // ===================================================================

  /**
   * Get room IoT devices
   */
  async getRoomDevices(): Promise<IoTDeviceResponse[]> {
    try {
      const response = await api.get<ApiResponse<IoTDeviceResponse[]>>(endpoints.tenant.iotDevices.roomDevices);
      return this.handleResponse<IoTDeviceResponse[]>(response, 'Failed to fetch room devices');
    } catch (error: unknown) {
      return this.handleError(error, 'getRoomDevices');
    }
  }

  /**
   * Get device status
   */
  async getDeviceStatus(deviceId: string): Promise<DeviceStatusResponse> {
    try {
      const response = await api.get<ApiResponse<DeviceStatusResponse>>(endpoints.tenant.iotDevices.deviceStatus(deviceId));
      return this.handleResponse<DeviceStatusResponse>(response, 'Failed to fetch device status');
    } catch (error: unknown) {
      return this.handleError(error, 'getDeviceStatus');
    }
  }

  // ===================================================================
  // NOTIFICATIONS
  // ===================================================================

  /**
   * Get notifications
   */
  async getNotifications(params?: NotificationParams): Promise<NotificationListResponse> {
    try {
      const response = await api.get<ApiResponse<NotificationListResponse>>(tenantApiConfig.notifications, { params });
      const data = this.handleResponse<NotificationListResponse>(response, 'Failed to fetch notifications');
      
      // Backend returns data.notifications, frontend expects just notifications
      return {
        notifications: data.notifications || [],
        total: data.total || 0
      };
    } catch (error: unknown) {
      console.error('TenantService.getNotifications error:', error);
      // Return empty array on error instead of throwing
      return { notifications: [], total: 0 };
    }
  }

  /**
   * Get unread notifications count
   */
  async getUnreadNotificationsCount(): Promise<number> {
    try {
      const response = await api.get<ApiResponse<{ count: number }>>(tenantApiConfig.unreadCount);
      const data = this.handleResponse<{ count: number }>(response, 'Failed to fetch unread count');
      return data.count;
    } catch (error: unknown) {
      console.error('TenantService.getUnreadNotificationsCount error:', error);
      return 0; // Return 0 on error instead of throwing
    }
  }

  /**
   * Mark notification as read
   */
  async markNotificationAsRead(notificationId: number): Promise<{ success: boolean; message: string } | null> {
    try {
      const response = await api.put<ApiResponse<{ success: boolean; message: string }>>(tenantApiConfig.markAsRead(notificationId));
      return this.handleResponse<{ success: boolean; message: string }>(response, 'Failed to mark notification as read');
    } catch (error: unknown) {
      console.error('TenantService.markNotificationAsRead error:', error);
      return null; // Return null on error instead of throwing
    }
  }


  /**
   * Mark all notifications as read
   */
  async markAllNotificationsAsRead(): Promise<{ success: boolean; message: string; count: number }> {
    try {
      const response = await api.post<ApiResponse<{ success: boolean; message: string; count: number }>>('/notifications/mark-all-read');
      return this.handleResponse<{ success: boolean; message: string; count: number }>(response, 'Failed to mark all notifications as read');
    } catch (error: unknown) {
      return this.handleError(error, 'markAllNotificationsAsRead');
    }
  }

  // ===================================================================
  // DOOR CONTROL
  // ===================================================================

  /**
   * Open room door for tenant
   */
  async openMyRoomDoor(reason?: string): Promise<DoorControlResponse> {
    try {
      // Use the new working endpoint that follows the same pattern as debug-tenant-access
      console.log('🔍 Trying new working door control endpoint...');
      
      const response = await api.post<ApiResponse<DoorControlResponse>>(endpoints.tenant.door.open, {
        reason: reason || 'Tenant manual door open'
      });
      
      console.log('✅ Door control endpoint working:', response.data);
      return this.handleResponse<DoorControlResponse>(response, 'Failed to open door');
      
    } catch (error: unknown) {
      console.error('❌ Door control endpoint failed:', error);
      if (error instanceof Error && 'response' in error) {
        const axiosError = error as { response?: { status?: number; data?: unknown } };
        console.log('Error details:', axiosError.response?.status, axiosError.response?.data);
      }
      return this.handleError(error, 'openMyRoomDoor');
    }
  }

  /**
   * Get my room door status
   */
  async getMyRoomDoorStatus(): Promise<DoorStatusResponse> {
    try {
      const response = await api.get<ApiResponse<DoorStatusResponse>>(endpoints.tenant.door.status);
      return this.handleResponse<DoorStatusResponse>(response, 'Failed to get door status');
    } catch (error: unknown) {
      return this.handleError(error, 'getMyRoomDoorStatus');
    }
  }

  // ===================================================================
  // UTILITY METHODS
  // ===================================================================

  /**
   * Health check - test if service is working
   */
  async healthCheck(): Promise<boolean> {
    try {
      await api.get(endpoints.realtime.status);
      return true;
    } catch (error) {
      console.error('TenantService.healthCheck failed:', error);
      return false;
    }
  }

  /**
   * Debug tenant access - for testing purposes
   */
  async debugTenantAccess(): Promise<DebugTenantAccessResponse> {
    try {
      const response = await api.get<ApiResponse<DebugTenantAccessResponse>>('/debug-tenant-access');
      console.log('🔍 Debug tenant access response:', response.data);
      return response.data.data || (response.data as DebugTenantAccessResponse);
    } catch (error: unknown) {
      console.error('❌ Debug tenant access failed:', error);
      throw error;
    }
  }

  // ===================================================================
  // PRIVATE HELPER METHODS
  // ===================================================================

  /**
   * Handle API response consistently
   */
  private handleResponse<T>(response: { data: ApiResponse<T> | T }, defaultError: string): T {
    // Check for explicit failure
    if (response.data && typeof response.data === 'object' && 'success' in response.data && response.data.success === false) {
      throw new Error((response.data as ApiResponse<T> & { message?: string }).message || defaultError);
    }

    // Return data if available
    if (response.data && typeof response.data === 'object' && 'data' in response.data) {
      return (response.data as ApiResponse<T>).data;
    }

    // Fallback for different response structures
    if (response.data && typeof response.data === 'object' && !('success' in response.data) && !('data' in response.data)) {
      return response.data as T;
    }

    throw new Error('Invalid response format');
  }

  /**
   * Handle errors consistently
   */
  private handleError(error: unknown, methodName: string): never {
    console.error(`TenantService.${methodName} error:`, error);
    
    // Map status codes to user-friendly messages
    const statusMessages: Record<number, string> = {
      401: 'Authentication required. Please login again.',
      403: 'Access denied. Tenant role required.',
      404: 'Active tenant record not found. Please contact administration.',
      422: 'Validation failed: Invalid data',
      500: 'Server error. Please try again later.',
    };

    // Handle axios error structure
    if (error && typeof error === 'object' && 'response' in error) {
      const axiosError = error as {
        response: {
          status: number;
          data?: { message?: string };
        };
        message?: string;
      };
      
      const status = axiosError.response.status;
      const backendMessage = axiosError.response.data?.message;
      
      let displayMessage = backendMessage || statusMessages[axiosError.response.status] || axiosError.message || 'Network error occurred';
      
      // Special handling for 422 if a specific message prefix is desired
      if (axiosError.response.status === 422 && backendMessage) {
        displayMessage = `Validation failed: ${backendMessage}`;
      }
      
      throw new Error(displayMessage);
    }
    
    // Handle generic error
    if (error instanceof Error) {
      throw new Error(error.message);
    }
    
    throw new Error('An unknown error occurred');
  }
}

// ===================================================================
// EXPORTS
// ===================================================================

// Export singleton instance
export const tenantService = new TenantService();

// Export class for testing
export default TenantService;