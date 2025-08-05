// File: src/pages/Admin/pages/PaymentManagement.tsx
import React, { useState, useEffect } from 'react';
import { RefreshCw, HelpCircle } from 'lucide-react';
import { toast } from 'react-hot-toast';
import { usePayments } from '../hooks';
import api, { endpoints } from '../../../utils/api';
import { 
  PaymentStats,
  PaymentTable,
  PaymentFilters,
  PaymentModal,
  PaymentDashboard,
  GeneratePaymentModalAdvanced,
  GenerateIndividualPaymentModal,
  ManualOverrideModal,
  VoidPaymentModal,
  // StuckPaymentDetector removed during cleanup
  ExpiredPaymentModal,
  InfoHelpModal
} from '../components/feature/payments';
import { Card, DangerousActionModal } from '../components/ui';
import type { AdminPayment as Payment, PaymentFilters as PaymentFiltersType } from '../types';

const PaymentManagement: React.FC = () => {
  const {
    payments,
    stats,
    loading,
    statsLoading,
    statsError,
    loadPayments,
    generateMonthlyPayments,
    syncPaymentStatus,
    preCheckGenerate,
    manualOverrideStatus,
    exportPayments,
    bulkSyncPayments,
    refresh
  } = usePayments();

  const [filters, setFilters] = useState<PaymentFiltersType>({
    search: '',
    status: '',
    month: '',
    generation_type: ''
  });
  
  const [showAdvancedGenerate, setShowAdvancedGenerate] = useState(false);
  const [showIndividualGenerate, setShowIndividualGenerate] = useState(false);
  const [individualGenerating, setIndividualGenerating] = useState(false);
  const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [showManualOverride, setShowManualOverride] = useState(false);
  const [showVoidPayment, setShowVoidPayment] = useState(false);
  const [showExpiredModal, setShowExpiredModal] = useState(false);
  const [showInfoHelp, setShowInfoHelp] = useState(false);
  const [showDangerousAction, setShowDangerousAction] = useState<{
    type: 'generate' | 'sync' | null;
    data?: unknown;
  }>({ type: null });

  // Load payments when filters change
  useEffect(() => {
    loadPayments(filters);
  }, [filters, loadPayments]);

  const handleGeneratePayments = async (month: string) => {
    setShowDangerousAction({ 
      type: 'generate', 
      data: { month } 
    });
  };

  const executeGeneratePayments = async () => {
    try {
      await generateMonthlyPayments((showDangerousAction.data as { month: string }).month);
      setShowDangerousAction({ type: null });
    } catch {
      // Error handled by hook
    }
  };

  const handleViewPayment = (payment: Payment) => {
    setSelectedPayment(payment);
    setShowPaymentModal(true);
  };

  const handleManualOverride = (payment: Payment) => {
    setSelectedPayment(payment);
    setShowManualOverride(true);
  };

  const handleOverrideSubmit = async (paymentId: number, newStatus: string, reason: string) => {
    try {
      await manualOverrideStatus(paymentId, newStatus, reason);
      setShowManualOverride(false);
    } catch {
      // Error handled by hook
    }
  };

  const handleVoidPayment = (payment: Payment) => {
    setSelectedPayment(payment);
    setShowVoidPayment(true);
  };

  const handleVoidSubmit = async (paymentId: number, reason: string, voidType: 'void' | 'cancel') => {
    try {
      // Note: This would need a voidPayment endpoint in the backend
      // For now, we'll use manual override to change status
      await manualOverrideStatus(paymentId, 'pending', `${voidType.toUpperCase()}: ${reason}`);
      setShowVoidPayment(false);
      refresh(); // Refresh data after void
    } catch {
      // Error handled by hook
    }
  };

  const handleSyncPayment = async (paymentId: number) => {
    try {
      await syncPaymentStatus(paymentId);
    } catch {
      // Error handled by hook
    }
  };

  const handleQuickAction = (action: 'generate' | 'generate-individual' | 'overdue' | 'pending' | 'sync' | 'export' | 'expired') => {
    switch (action) {
      case 'generate':
        setShowAdvancedGenerate(true);
        break;
      case 'generate-individual':
        setShowIndividualGenerate(true);
        break;
      case 'overdue':
        setFilters(prev => ({ ...prev, status: 'overdue' }));
        break;
      case 'pending':
        setFilters(prev => ({ ...prev, status: 'pending' }));
        break;
      case 'sync':
        handleSyncAllPayments();
        break;
      case 'export':
        handleExportPayments();
        break;
      case 'expired':
        setShowExpiredModal(true);
        break;
    }
  };

  // Handle individual payment generation
  const handleIndividualGenerate = async (paymentData: {
    tenant_id: number;
    payment_month: string;
    prorate_from_date?: string;
    send_notification: boolean;
    description?: string;
    notes?: string;
  }) => {
    try {
      setIndividualGenerating(true);
      
      const response = await api.post(endpoints.admin.payments.generateIndividual, paymentData);
      
      if (response.data.success) {
        setShowIndividualGenerate(false);
        toast.success(`Payment berhasil dibuat! Order ID: ${response.data.data.order_id}`);
        
        // Refresh payments data
        refresh();
      } else {
        throw new Error(response.data.message || 'Gagal membuat payment');
      }
    } catch (error: unknown) {
      const errorMessage = (error as Record<string, unknown>).response?.data?.message || (error as Error).message || 'Gagal membuat payment';
      toast.error(errorMessage);
      throw error; // Re-throw to let modal handle the error state
    }
  };

  const handlePreCheck = async (month: string) => {
    try {
      return await preCheckGenerate(month);
    } catch {
      // Return error state if API fails
      return {
        valid: false,
        activeTenantsCount: 0,
        duplicatePayments: 0,
        invalidData: 0,
        warnings: [],
        errors: ['Gagal melakukan pengecekan validasi. Silakan coba lagi.']
      };
    }
  };

  const handleFilterChange = (newFilters: PaymentFiltersType) => {
    setFilters(newFilters);
  };

  const handleExportPayments = async () => {
    try {
      await exportPayments(filters);
    } catch {
      // Error handled by hook
    }
  };

  const handleSyncAllPayments = async () => {
    setShowDangerousAction({ 
      type: 'sync', 
      data: {} 
    });
  };

  const executeSyncAllPayments = async () => {
    try {
      await bulkSyncPayments();
      setShowDangerousAction({ type: null });
    } catch {
      // Error handled by hook
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header Section */}
        <div className="mb-8">
          <div className="md:flex md:items-center md:justify-between">
            <div className="min-w-0 flex-1">
              <h1 className="text-3xl font-bold leading-tight text-gray-900">
                Manajemen Pembayaran
              </h1>
              <p className="mt-2 text-sm text-gray-600">
                Kelola pembayaran penyewa dan generate tagihan bulanan dengan mudah
              </p>
            </div>
            
            <div className="mt-4 flex space-x-3 md:mt-0 md:ml-4">
              <button
                onClick={() => setShowInfoHelp(true)}
                className="inline-flex items-center px-4 py-2 border border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg text-sm font-medium shadow-sm transition-colors"
              >
                <HelpCircle className="h-4 w-4 mr-2" />
                Info & Help
              </button>
              <button
                onClick={refresh}
                className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors"
              >
                <RefreshCw className="h-4 w-4 mr-2" />
                Refresh Data
              </button>
            </div>
          </div>
        </div>

        {/* Dashboard Overview */}
        <PaymentDashboard 
          stats={stats}
          loading={statsLoading}
          onQuickAction={handleQuickAction}
        />

        {/* Detailed Stats Section */}
        <div className="mb-8">
          <PaymentStats 
            stats={stats}
            loading={statsLoading}
            error={statsError}
          />
        </div>

        


        {/* Payment Table Section */}
        <div className="mb-8">
          <Card className="overflow-hidden shadow-sm">
            <div className="px-6 py-4 border-b border-gray-200 bg-white">
              <div>
                <h3 className="text-lg font-semibold text-gray-900">
                  Daftar Pembayaran
                </h3>
                <p className="text-sm text-gray-500 mt-1">
                  Kelola dan pantau semua pembayaran penyewa
                </p>
              </div>
            </div>
            
            <PaymentFilters
              filters={filters}
              onFilterChange={handleFilterChange}
            />
            
            <div className="bg-white">
              <PaymentTable
                payments={payments}
                loading={loading}
                onViewPayment={handleViewPayment}
                onSyncPayment={handleSyncPayment}
                onManualOverride={handleManualOverride}
                onVoidPayment={handleVoidPayment}
              />
            </div>
          </Card>
        </div>
      </div>

      {/* Modals */}
      <GeneratePaymentModalAdvanced
        isOpen={showAdvancedGenerate}
        onClose={() => setShowAdvancedGenerate(false)}
        onGenerate={handleGeneratePayments}
        onPreCheck={handlePreCheck}
      />

      <GenerateIndividualPaymentModal
        isOpen={showIndividualGenerate}
        onClose={() => setShowIndividualGenerate(false)}
        onGenerate={handleIndividualGenerate}
        loading={individualGenerating}
      />

      <PaymentModal
        isOpen={showPaymentModal}
        payment={selectedPayment}
        onClose={() => setShowPaymentModal(false)}
        onSyncPayment={handleSyncPayment}
      />

      <ManualOverrideModal
        isOpen={showManualOverride}
        payment={selectedPayment}
        onClose={() => setShowManualOverride(false)}
        onOverride={handleOverrideSubmit}
      />

      <VoidPaymentModal
        isOpen={showVoidPayment}
        payment={selectedPayment}
        onClose={() => setShowVoidPayment(false)}
        onVoid={handleVoidSubmit}
      />

      <ExpiredPaymentModal
        isOpen={showExpiredModal}
        onClose={() => setShowExpiredModal(false)}
        onRefresh={refresh}
      />

      <InfoHelpModal
        isOpen={showInfoHelp}
        onClose={() => setShowInfoHelp(false)}
      />

      <DangerousActionModal
        isOpen={showDangerousAction.type === 'generate'}
        title="Generate Pembayaran Bulanan"
        message={`Anda akan membuat tagihan untuk SEMUA penyewa aktif untuk bulan ${(showDangerousAction.data as { month: string })?.month || ''}. Operasi ini akan membuat banyak data pembayaran baru dan tidak dapat dibatalkan.`}
        confirmText="Ya, Generate Pembayaran"
        cancelText="Batal"
        dangerLevel="high"
        onConfirm={executeGeneratePayments}
        onCancel={() => setShowDangerousAction({ type: null })}
      />

      <DangerousActionModal
        isOpen={showDangerousAction.type === 'sync'}
        title="Sinkronisasi Semua Pembayaran"
        message="Anda akan menyinkronkan status semua pembayaran dengan gateway pembayaran. Proses ini mungkin memakan waktu beberapa menit."
        confirmText="Ya, Sinkronisasi Semua"
        cancelText="Batal"
        dangerLevel="medium"
        onConfirm={executeSyncAllPayments}
        onCancel={() => setShowDangerousAction({ type: null })}
      />
    </div>
  );
};

export default PaymentManagement;