// ===================================================================
// api.ts - Kost Management System API Client v3.2 (CLEAN)
// ===================================================================

import axios, { AxiosInstance, AxiosResponse, AxiosError } from 'axios';
import { toast } from 'react-hot-toast';
import { getAuthToken } from '../pages/Auth/utils/helpers';
import { ENV } from '../config/environment';

// ===================================================================
// TYPES & INTERFACES
// ===================================================================

export interface ApiResponse<T = unknown> {
  success: boolean;
  data: T;
  message?: string;
  timestamp?: string;
}

export interface ApiError {
  success: false;
  message: string;
  error?: string;
  code?: number;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export interface PaymentExpirationInfo {
  payment_id: number | string;
  status: string;
  is_expired: boolean;
  can_regenerate: boolean;
  expires_at: string;
  created_at: string;
  snap_token_created_at?: string;
  time_remaining?: {
    total_minutes: number;
    hours: number;
    minutes: number;
    is_near_expiry: boolean;
    human_readable: string;
  };
}

export interface ExpiredPayment {
  id: number;
  order_id: string;
  amount: number;
  status: string;
  expired_at: string;
  can_regenerate: boolean;
  payment_month: string;
  tenant_id: number;
}

export interface PaymentRegenerationResult {
  old_payment_id: number;
  new_payment_id: number;
  new_order_id: string;
  amount: number;
  status: string;
}

// ===================================================================
// AXIOS INSTANCE & INTERCEPTORS
// ===================================================================

const api: AxiosInstance = axios.create({
  baseURL: ENV.API_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: false,
});

// Request interceptor
api.interceptors.request.use(
  (config) => {
    const token = getAuthToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    // Add cache busting timestamp for GET requests
    if (config.method === 'get') {
      config.params = { ...config.params, _t: Date.now() };
    }
    
    return config;
  },
  (error: AxiosError) => Promise.reject(error)
);

// Response interceptor
api.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error: AxiosError) => {
    const { response } = error;
    
    // Handle different error types
    switch (response?.status) {
      case 401: {
        // Use auth helpers to clear encrypted tokens
        const { clearAuthStorage } = await import('../pages/Auth/utils/helpers');
        clearAuthStorage();
        if (!window.location.pathname.includes('/login')) {
          toast.error('Session expired. Please login again.');
          window.location.href = '/login';
        }
        break;
      }
        
      case 403:
        toast.error('Access denied. You do not have permission to perform this action.');
        break;
        
      case 404:
        toast.error('Resource not found.');
        break;
        
      case 410: {
        const errorData = response?.data as ApiError;
        toast.error(errorData?.message || 'Payment has expired');
        break;
      }
        
      case 422: {
        const validationData = response.data as ApiError;
        if (validationData.errors) {
          Object.values(validationData.errors).flat().forEach(error => {
            toast.error(error);
          });
        } else if (validationData.message) {
          toast.error(validationData.message);
        }
        break;
      }
        
      default:
        if (response?.status && response.status >= 500) {
          toast.error('Server error. Please try again later.');
        } else if (error.code === 'ECONNABORTED') {
          toast.error('Request timeout. Please try again.');
        } else if (!response) {
          toast.error('Network error. Please check your connection.');
        }
    }
    
    return Promise.reject(error);
  }
);

// ===================================================================
// API ENDPOINTS
// ===================================================================

export const endpoints = {
  // System
  system: {
    health: '/health',
    docs: '/docs',
  },

  // Authentication
  auth: {
    login: '/auth/login',
    register: '/auth/register',
    logout: '/auth/logout',
    logoutAll: '/auth/logout-all',
    user: '/auth/user',
    profile: '/auth/profile',
    updateProfile: '/auth/profile',
    changePassword: '/auth/change-password',
    updateAvatar: '/auth/update-avatar',
    forgotPassword: '/auth/forgot-password',
    resetPassword: '/auth/reset-password',
  },

  // Shared Resources
  payments: {
    show: (id: number | string) => `/payments/${id}`,
    status: (id: number | string) => `/payments/${id}/status`,
    sync: (id: number | string) => `/payments/${id}/sync-status`,
    tenantPayments: '/tenant/payments',
  },

  // Shared access logs
  accessLogs: '/access-logs',

  // Shared RFID
  rfid: '/rfid',

  notifications: {
    index: '/notifications',
    unreadCount: '/notifications/unread-count',
    markAsRead: (id: number | string) => `/notifications/${id}/read`,
    markAllAsRead: '/notifications/mark-all-read',
  },

  files: {
    upload: '/files/upload',
    download: (id: number | string) => `/files/${id}/download`,
    delete: (id: number | string) => `/files/${id}`,
  },

  realtime: {
    status: '/realtime/status',
    heartbeat: '/realtime/heartbeat',
  },

  // Admin Endpoints
  admin: {
    users: {
      index: '/admin/users',
      show: (id: number | string) => `/admin/users/${id}`,
      update: (id: number | string) => `/admin/users/${id}`,
      destroy: (id: number | string) => `/admin/users/${id}`,
      register: '/admin/register',
    },

    dashboard: {
      stats: '/admin/dashboard/stats',
      activities: '/admin/dashboard/activities',
      analytics: '/admin/dashboard/analytics',
      health: '/admin/dashboard/health',
      occupancy: '/admin/dashboard/occupancy',
      revenueChart: '/admin/dashboard/revenue-chart',
      refreshCache: '/admin/dashboard/refresh-cache',
    },

    rooms: {
      index: '/admin/rooms',
      store: '/admin/rooms',
      show: (id: number | string) => `/admin/rooms/${id}`,
      update: (id: number | string) => `/admin/rooms/${id}`,
      destroy: (id: number | string) => `/admin/rooms/${id}`,
      assignTenant: (id: number | string) => `/admin/rooms/${id}/assign-tenant`,
      removeTenant: (id: number | string) => `/admin/rooms/${id}/remove-tenant`,
      stats: (id: number | string) => `/admin/rooms/${id}/stats`,
      bulkUpdateStatus: '/admin/rooms/bulk-update-status',
      availableTenants: '/admin/rooms/available-tenants',
      occupancyHistory: (id: number | string) => `/admin/rooms/${id}/occupancy-history`,
      revenueAnalytics: (id: number | string) => `/admin/rooms/${id}/revenue-analytics`,
    },

    tenants: {
      index: '/admin/tenants',
      store: '/admin/tenants',
      show: (id: number | string) => `/admin/tenants/${id}`,
      update: (id: number | string) => `/admin/tenants/${id}`,
      destroy: (id: number | string) => `/admin/tenants/${id}`,
      renew: (id: number | string) => `/admin/tenants/${id}/renew`,
      moveOut: (id: number | string) => `/admin/tenants/${id}/move-out`,
      analytics: (id: number | string) => `/admin/tenants/${id}/analytics`,
    },

    payments: {
      index: '/admin/payments',
      generateMonthly: '/admin/payments/generate-monthly',
      generateIndividual: '/admin/payments/generate-individual',
      overdue: '/admin/payments/overdue',
      stuck: '/admin/payments/stuck',
      stats: '/admin/payments/stats',
      analytics: '/admin/payments/analytics',
      export: '/admin/payments/export',
      expired: '/admin/payments/expired',
      regenerate: (id: number | string) => `/admin/payments/${id}/regenerate`,
      checkExpired: '/admin/payments/check-expired',
      cleanupExpired: '/admin/payments/cleanup-expired',
      expirationReport: '/admin/payments/expiration-report',
      bulkRegenerate: '/admin/payments/bulk-regenerate',
      systemHealth: '/admin/payments/system-health',
    },

    rfid: {
      cards: '/admin/rfid/cards',
      toggle: (id: number | string) => `/admin/rfid/cards/${id}/toggle`,
      assign: (id: number | string) => `/admin/rfid/cards/${id}/assign`,
      delete: (id: number | string) => `/admin/rfid/cards/${id}`,
      logs: '/admin/rfid/access-logs',
      stats: '/admin/rfid/dashboard-stats',
      startScanner: '/admin/rfid/scanner/start',
      stopScanner: '/admin/rfid/scanner/stop',
      analytics: '/admin/rfid/analytics',
      usageReport: '/admin/rfid/usage-report',
    },

    iotDevices: {
      index: '/admin/iot-devices',
      store: '/admin/iot-devices',
      show: (id: number | string) => `/admin/iot-devices/${id}`,
      update: (id: number | string) => `/admin/iot-devices/${id}`,
      destroy: (id: number | string) => `/admin/iot-devices/${id}`,
      updateStatus: (id: number | string) => `/admin/iot-devices/${id}/status`,
      ping: (id: number | string) => `/admin/iot-devices/${id}/ping`,
      bulkStatus: '/admin/iot-devices/bulk/status',
      bulkDelete: '/admin/iot-devices/bulk/delete',
      export: (format: string) => `/admin/iot-devices/export/${format}`,
      import: '/admin/iot-devices/import',
      rooms: '/admin/rooms',
      statistics: '/admin/iot-devices/statistics/overview',
      healthCheck: '/admin/iot-devices/health/check',
    },

    doorControl: {
      remote: '/admin/door-control/remote',
      emergency: '/admin/door-control/emergency-open',
      status: '/admin/door-control/status',
      lockAll: '/admin/door-control/lock-all',
    },

    mqtt: {
      dashboard: '/admin/mqtt/dashboard',
      test: '/admin/mqtt/test-connection',
      statistics: '/admin/mqtt/statistics',
      logs: '/admin/mqtt/logs',
    },

    accessLogs: {
      index: '/admin/access-logs',
      statistics: '/admin/access-logs/statistics',
      export: '/admin/access-logs/export',
      analytics: '/admin/access-logs/analytics',
      suspicious: '/admin/access-logs/suspicious',
    },

    notifications: {
      all: '/admin/notifications/all',
      broadcast: '/admin/notifications/broadcast',
      templates: '/admin/notifications/templates',
      schedule: '/admin/notifications/schedule',
    },

    system: {
      backup: '/admin/system/backup',
      logs: '/admin/system/logs',
      clearCache: '/admin/system/clear-cache',
      performance: '/admin/system/performance',
    },

    reports: {
      monthly: '/admin/reports/monthly',
      occupancy: '/admin/reports/occupancy',
      financial: '/admin/reports/financial',
      tenantSummary: '/admin/reports/tenant-summary',
    },
  },

  // ✅ FIXED: Tenant Endpoints
  tenant: {
    // Dashboard & Profile
    dashboard: '/tenant/dashboard',

    // Payments
    payments: {
      index: '/tenant/payments',
      paymentUrl: (id: number | string) => `/tenant/payments/${id}/payment-url`,
      paymentUrlPost: (id: number | string) => `/tenant/payments/${id}/payment-url`,
      paymentUrlShort: (id: number | string) => `/tenant/payments/${id}/url`,
      checkout: (id: number | string) => `/tenant/payments/${id}/checkout`,
      initiate: (id: number | string) => `/tenant/payments/${id}/initiate`,
      status: (id: number | string) => `/tenant/payments/${id}/status`,
      syncStatus: (id: number | string) => `/tenant/payments/${id}/sync-status`,
      history: '/tenant/payments/history',
      summary: '/tenant/payments/summary',
      expirationInfo: (id: number | string) => `/tenant/payments/${id}/expiration-info`,
      expired: '/tenant/payments/expired/list',
      nearExpiry: '/tenant/payments/near-expiry/list',
      regenerate: (id: number | string) => `/tenant/payments/${id}/regenerate`,
      receipt: {
        download: (id: number | string) => `/tenant/payments/${id}/receipt/download`,
        url: (id: number | string) => `/tenant/payments/${id}/receipt/url`,
        check: (id: number | string) => `/tenant/payments/${id}/receipt/check`,
      },
    },

    // Access History
    access: {
      history: '/tenant/access/history',
      stats: '/tenant/access/stats',
      patterns: '/tenant/access/patterns',
    },

    // RFID Cards
    rfid: {
      cards: '/tenant/rfid/cards',
      requestCard: '/tenant/rfid/request-card',
      reportLost: '/tenant/rfid/report-lost',
    },

    // IoT Devices
    iotDevices: {
      roomDevices: '/tenant/iot-devices/room',
      deviceStatus: (id: number | string) => `/tenant/iot-devices/${id}/status`,
      accessLogs: '/tenant/iot-devices/access-logs',
    },

    // ✅ Profile Settings (separate from dashboard)
    profile: {
      index: '/tenant/profile/settings',
      update: '/tenant/profile/update',
      emergencyContact: '/tenant/profile/emergency-contact',
    },

    // Door Control
    door: {
      open: '/tenant/door/open',
      status: '/tenant/door/status',
      test: '/tenant/door/test',
    },

    // Support
    support: {
      tickets: '/tenant/support/tickets',
      createTicket: '/tenant/support/tickets',
      updateTicket: (id: number | string) => `/tenant/support/tickets/${id}`,
    },
  },

  // IoT Communication (Public)
  iot: {
    register: '/iot/register',
    heartbeat: '/iot/device-heartbeat',
    statusUpdate: '/iot/status-update',
    rfid: {
      process: '/iot/rfid/process',
      checkCard: (uid: string) => `/iot/rfid/check-card/${uid}`,
      registerScan: '/iot/rfid/register-scan',
    },
    doorStatus: '/iot/door/status',
    health: '/iot/health',
    mqttStatus: '/iot/mqtt/status',
  },

  // Webhooks (Public)
  webhooks: {
    mqtt: {
      clientConnected: '/mqtt/webhook/client-connected',
      clientDisconnected: '/mqtt/webhook/client-disconnected',
      messageReceived: '/mqtt/webhook/message-received',
    },
    payment: {
      midtrans: '/webhook/midtrans',
      payment: '/webhook/payment',
      test: '/webhook/test',
    },
  },

  // Debug (Development)
  debug: {
    paymentInfo: (id: number | string) => `/debug/payment-info/${id}`,
    testPaymentEndpoints: (id: number | string) => `/debug/test-payment-endpoints/${id}`,
    paymentMethods: '/debug/payment-methods',
    testWebhook: '/debug/test-webhook',
  },
} as const;

// ===================================================================
// API HELPERS
// ===================================================================

export const apiHelpers = {
  // Authentication
  getAuthHeaders: () => {
    const token = getAuthToken();
    return token ? { Authorization: `Bearer ${token}` } : {};
  },

  // File Operations
  uploadFile: async (endpoint: string, file: File, additionalData?: Record<string, unknown>) => {
    const formData = new FormData();
    formData.append('file', file);
    
    if (additionalData) {
      Object.entries(additionalData).forEach(([key, value]) => {
        formData.append(key, String(value));
      });
    }

    return api.post(endpoint, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        ...apiHelpers.getAuthHeaders(),
      },
    });
  },

  // Pagination
  getPaginated: async <T>(endpoint: string, params?: Record<string, unknown>) => {
    const response = await api.get<ApiResponse<PaginatedResponse<T>>>(endpoint, { params });
    return response.data;
  },

  // Export Data
  exportData: async (endpoint: string, params?: Record<string, unknown>) => {
    const response = await api.get(endpoint, {
      params,
      responseType: 'blob',
    });
    
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    
    const contentDisposition = response.headers['content-disposition'];
    const filename = contentDisposition
      ? contentDisposition.split('filename=')[1]?.replace(/"/g, '')
      : 'export.xlsx';
    
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  },

  // Bulk Operations
  bulkOperation: async (endpoint: string, ids: (number | string)[], operation: string, additionalData?: Record<string, unknown>) => {
    return api.post(endpoint, {
      ids,
      operation,
      ...additionalData,
    });
  },

  // Payment Helpers
  payment: {
    getPaymentUrl: async (paymentId: number | string, method: 'GET' | 'POST' = 'GET') => {
      const endpoint = endpoints.tenant.payments.paymentUrl(paymentId);
      
      try {
        if (method === 'GET') {
          return await api.get(endpoint);
        } else {
          return await api.post(endpoint, {});
        }
      } catch (error: unknown) {
        // Handle expired payment (410 Gone)
        if (error.response?.status === 410) {
          const errorData = error.response.data as ApiError & { data?: unknown };
          
          if (errorData.data?.can_regenerate) {
            toast.error('Payment expired. Attempting to regenerate...');
            try {
              const regenerateResult = await apiHelpers.payment.regenerateExpiredPayment(paymentId);
              if (regenerateResult.data.success) {
                const newPaymentId = regenerateResult.data.data.new_payment_id;
                toast.success('Payment regenerated successfully!');
                return await api.get(endpoints.tenant.payments.paymentUrl(newPaymentId));
              }
            } catch (regenerateError) {
              toast.error('Failed to regenerate expired payment');
              throw regenerateError;
            }
          } else {
            toast.error('Payment has expired and cannot be regenerated. Please contact support.');
          }
          throw error;
        }
        
        // Try alternative endpoints
        if (error.response?.status === 405 || error.response?.status === 404) {
          const alternatives = [
            endpoints.tenant.payments.paymentUrlShort(paymentId),
            endpoints.tenant.payments.checkout(paymentId),
            endpoints.tenant.payments.initiate(paymentId),
          ];
          
          for (const altEndpoint of alternatives) {
            try {
              return await api.get(altEndpoint);
            } catch {
              continue;
            }
          }
          
          try {
            return await api.post(endpoints.tenant.payments.paymentUrlPost(paymentId), {});
          } catch {
            throw error;
          }
        }
        throw error;
      }
    },

    checkStatus: async (paymentId: number | string) => {
      return await api.get(endpoints.payments.status(paymentId));
    },

    syncStatus: async (paymentId: number | string) => {
      return await api.post(endpoints.payments.sync(paymentId), {});
    },

    getList: async (params?: Record<string, unknown>) => {
      return await api.get(endpoints.tenant.payments.index, { params });
    },

    // Expired Payment Management
    getExpirationInfo: async (paymentId: number | string) => {
      return await api.get<ApiResponse<PaymentExpirationInfo>>(
        endpoints.tenant.payments.expirationInfo(paymentId)
      );
    },

    getExpiredPayments: async (params?: Record<string, unknown>) => {
      return await api.get<ApiResponse<ExpiredPayment[]>>(
        endpoints.tenant.payments.expired, 
        { params }
      );
    },

    regenerateExpiredPayment: async (paymentId: number | string) => {
      return await api.post<ApiResponse<PaymentRegenerationResult>>(
        endpoints.tenant.payments.regenerate(paymentId), 
        {}
      );
    },
  },

  // Access Log Helpers
  access: {
    getHistory: async (params?: Record<string, unknown>) => {
      return await api.get(endpoints.tenant.access.history, { params });
    },

    getStats: async (params?: Record<string, unknown>) => {
      return await api.get(endpoints.tenant.access.stats, { params });
    },

    getPatterns: async (params?: Record<string, unknown>) => {
      return await api.get(endpoints.tenant.access.patterns, { params });
    },
  },
};

// ===================================================================
// REACT QUERY KEYS
// ===================================================================

export const reactQueryKeys = {
  payments: {
    all: ['payments'] as const,
    tenant: () => [...reactQueryKeys.payments.all, 'tenant'] as const,
    tenantList: (params?: Record<string, unknown>) => [...reactQueryKeys.payments.tenant(), 'list', params] as const,
    tenantPayment: (id: number | string) => [...reactQueryKeys.payments.tenant(), 'payment', id] as const,
    expirationInfo: (id: number | string) => [...reactQueryKeys.payments.tenant(), 'expiration', id] as const,
  },

  access: {
    all: ['access'] as const,
    tenant: () => [...reactQueryKeys.access.all, 'tenant'] as const,
    tenantHistory: (params?: Record<string, unknown>) => [...reactQueryKeys.access.tenant(), 'history', params] as const,
    tenantStats: () => [...reactQueryKeys.access.tenant(), 'stats'] as const,
  },

  dashboard: {
    all: ['dashboard'] as const,
    tenant: () => [...reactQueryKeys.dashboard.all, 'tenant'] as const,
    admin: () => [...reactQueryKeys.dashboard.all, 'admin'] as const,
  },
} as const;

// ===================================================================
// TYPE-SAFE API WRAPPER
// ===================================================================

export const apiCall = {
  async safe<T>(
    apiFunction: () => Promise<AxiosResponse<ApiResponse<T>>>,
    errorHandler?: (error: unknown) => void
  ): Promise<{ success: true; data: T } | { success: false; error: string }> {
    try {
      const response = await apiFunction();
      return { success: true, data: response.data.data };
    } catch (error: unknown) {
      const errorMessage = error.response?.data?.message || error.message || 'An error occurred';
      
      if (errorHandler) {
        errorHandler(error);
      } else {
        // Error logging removed for security
      }
      
      return { success: false, error: errorMessage };
    }
  },

  async withRetry<T>(
    apiFunction: () => Promise<AxiosResponse<ApiResponse<T>>>,
    retryAttempts: number = 3,
    retryDelay: number = 1000
  ): Promise<{ success: true; data: T } | { success: false; error: string }> {
    let lastError: unknown;
    
    for (let attempt = 1; attempt <= retryAttempts; attempt++) {
      try {
        const response = await apiFunction();
        return { success: true, data: response.data.data };
      } catch (error: unknown) {
        lastError = error;
        
        // Don't retry on client errors (4xx)
        if (error.response?.status && error.response.status >= 400 && error.response.status < 500) {
          break;
        }
        
        // Wait before retrying
        if (attempt < retryAttempts) {
          await new Promise(resolve => setTimeout(resolve, retryDelay * attempt));
        }
      }
    }
    
    const errorMessage = lastError?.response?.data?.message || lastError?.message || 'Request failed after retries';
    return { success: false, error: errorMessage };
  }
};

// Export the configured axios instance as default
export default api;