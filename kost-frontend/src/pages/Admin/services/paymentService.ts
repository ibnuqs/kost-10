// File: src/pages/Admin/services/paymentService.ts
import api from '../../../utils/api';
import type { AdminPayment as Payment, PaymentStats, PaymentFilters, PaymentStatsApiResponse } from '../types';

export const paymentService = {
  async getPayments(filters?: PaymentFilters): Promise<Payment[]> {
    const params = new URLSearchParams();
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== '' && value !== 'all') {
          params.append(key, value.toString());
        }
      });
    }
    
    const response = await api.get(`/admin/payments?${params}`);
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to fetch payments');
    }
    return response.data.data || response.data;
  },

  async getStats(filters?: PaymentFilters): Promise<PaymentStats> {
    const params = new URLSearchParams();
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== '' && value !== 'all') {
          params.append(key, value.toString());
        }
      });
    }
    const response = await api.get(`/admin/payments/stats?${params}`);
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to fetch payment stats');
    }
    
    // Map backend response to frontend format
    const apiData: PaymentStatsApiResponse = response.data.data;
    
    return {
      total_payments: apiData.overall.total_payments,
      paid_this_month: apiData.current_month.paid_payments,
      pending_this_month: apiData.current_month.pending_payments,
      overdue_count: apiData.current_month.overdue_payments,
      total_revenue_this_month: apiData.current_month.paid_amount,
      total_revenue_all_time: apiData.overall.total_payments * (apiData.overall.average_payment || 0)
    };
  },

  async generateMonthly(month: string): Promise<void> {
    const response = await api.post('/admin/payments/generate-monthly', {
      payment_month: month
    });
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to generate monthly payments');
    }
  },

  async syncStatus(paymentId: number): Promise<void> {
    const response = await api.post(`/admin/payments/${paymentId}/sync-status`);
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to sync payment status');
    }
  },

  async preCheckGenerate(month: string): Promise<{
    valid: boolean;
    activeTenantsCount: number;
    duplicatePayments: number;
    invalidData: number;
    warnings: string[];
    errors: string[];
  }> {
    const response = await api.get(`/admin/payments/pre-check-generate?month=${month}`);
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to perform pre-check');
    }
    return response.data.data;
  },

  async manualOverride(paymentId: number, newStatus: string, reason: string): Promise<void> {
    const response = await api.post(`/admin/payments/${paymentId}/manual-override`, {
      status: newStatus,
      reason: reason
    });
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to override payment status');
    }
  },

  async voidPayment(paymentId: number, reason: string, voidType: 'void' | 'cancel'): Promise<void> {
    const response = await api.post(`/admin/payments/${paymentId}/${voidType}`, {
      reason: reason
    });
    if (response.data.success === false) {
      throw new Error(response.data.message || `Failed to ${voidType} payment`);
    }
  },

  async exportPayments(filters?: PaymentFilters): Promise<Blob> {
    const params = new URLSearchParams();
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== '' && value !== 'all') {
          params.append(key, value.toString());
        }
      });
    }
    
    const response = await api.get(`/admin/payments/export?${params}`, {
      responseType: 'blob'
    });
    return response.data;
  },

  async bulkSyncPayments(paymentIds?: number[]): Promise<void> {
    const response = await api.post('/admin/payments/bulk-sync', {
      payment_ids: paymentIds
    });
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Failed to bulk sync payments');
    }
  }
};