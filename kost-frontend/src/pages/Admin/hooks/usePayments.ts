// File: src/pages/Admin/hooks/usePayments.ts
import { useState, useEffect, useCallback } from 'react';
import { toast } from 'react-hot-toast';
import { paymentService } from '../services';
import type { AdminPayment as Payment, PaymentStats, PaymentFilters } from '../types';

export const usePayments = () => {
  const [payments, setPayments] = useState<Payment[]>([]);
  const [stats, setStats] = useState<PaymentStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [statsLoading, setStatsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [statsError, setStatsError] = useState<string | null>(null);

  const loadPayments = useCallback(async (filters?: PaymentFilters) => {
    try {
      setLoading(true);
      setError(null);
      const data = await paymentService.getPayments(filters);
      setPayments(data);
    } catch (err: unknown) {
      setError((err as Error).message);
      toast.error('Failed to load payments');
    } finally {
      setLoading(false);
    }
  }, []);

  const loadStats = useCallback(async () => {
    try {
      setStatsLoading(true);
      setStatsError(null);
      const data = await paymentService.getStats();
      setStats(data);
    } catch (err: unknown) {
      console.error('Failed to load payment stats:', err);
      setStatsError((err as Error).message || 'Failed to load payment statistics');
    } finally {
      setStatsLoading(false);
    }
  }, []);

  const generateMonthlyPayments = useCallback(async (month: string) => {
    try {
      await paymentService.generateMonthly(month);
      toast.success('Monthly payments generated successfully');
      loadPayments();
      loadStats();
    } catch (err: unknown) {
      toast.error((err as Error).message || 'Failed to generate payments');
      throw err;
    }
  }, [loadPayments, loadStats]);

  const syncPaymentStatus = useCallback(async (paymentId: number) => {
    try {
      await paymentService.syncStatus(paymentId);
      toast.success('Payment status synced successfully');
      loadPayments();
    } catch (err: unknown) {
      toast.error((err as Error).message || 'Failed to sync payment status');
      throw err;
    }
  }, [loadPayments]);

  const preCheckGenerate = useCallback(async (month: string) => {
    try {
      return await paymentService.preCheckGenerate(month);
    } catch (err: unknown) {
      toast.error((err as Error).message || 'Failed to perform pre-check');
      throw err;
    }
  }, []);

  const manualOverrideStatus = useCallback(async (paymentId: number, newStatus: string, reason: string) => {
    try {
      await paymentService.manualOverride(paymentId, newStatus, reason);
      toast.success('Payment status berhasil dioverride');
      loadPayments();
      loadStats();
    } catch (err: unknown) {
      toast.error((err as Error).message || 'Failed to override payment status');
      throw err;
    }
  }, [loadPayments, loadStats]);

  const exportPayments = useCallback(async (format: string = 'csv') => {
    try {
      toast.loading('Mempersiapkan export...');
      const blob = await paymentService.exportPayments(format);
      
      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `payments-export-${new Date().toISOString().slice(0, 10)}.csv`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      toast.dismiss();
      toast.success('Export berhasil didownload');
    } catch (err: unknown) {
      toast.dismiss();
      toast.error((err as Error).message || 'Failed to export payments');
      throw err;
    }
  }, []);

  const bulkSyncPayments = useCallback(async (paymentIds?: number[]) => {
    try {
      await paymentService.bulkSyncPayments(paymentIds);
      toast.success('Bulk sync berhasil completed');
      loadPayments();
      loadStats();
    } catch (err: unknown) {
      toast.error((err as Error).message || 'Failed to bulk sync payments');
      throw err;
    }
  }, [loadPayments, loadStats]);

  useEffect(() => {
    loadPayments();
    loadStats();
  }, [loadPayments, loadStats]);

  return {
    payments,
    stats,
    loading,
    statsLoading,
    error,
    statsError,
    loadPayments,
    generateMonthlyPayments,
    syncPaymentStatus,
    preCheckGenerate,
    manualOverrideStatus,
    exportPayments,
    bulkSyncPayments,
    refresh: (currentFilters?: PaymentFilters) => {
      loadPayments(currentFilters || filters);
      loadStats(currentFilters || filters);
    }
  };
};