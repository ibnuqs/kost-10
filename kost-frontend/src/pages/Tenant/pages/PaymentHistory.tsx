// File: src/pages/Tenant/pages/PaymentHistory.tsx
// MODERN REDESIGNED PAYMENT HISTORY PAGE - COMPLETELY OVERHAULED

import React, { useState, useMemo, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { 
  CreditCard, 
  Filter, 
  Search, 
  TrendingUp, 
  Download,
  ArrowUpRight,
  Clock,
  CheckCircle,
  AlertCircle,
  Wallet,
  Grid3X3,
  List,
  SortAsc,
  SortDesc
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import { useTenantPayments } from '../hooks/useTenantPayments';
import { PaymentHistoryTable, PaymentFilters, CustomSnapPayment } from '../components/feature/payments';
import { Button } from '../components/ui/Buttons';
import { Card } from '../components/ui/Card';
import { StatusBadge } from '../components/ui/Status';
import { mergeClasses } from '../utils/helpers';
import { MOBILE_SPECIFIC } from '../utils/constants';
import { formatCurrency, formatDate } from '../utils/formatters';
import { Payment } from '../types/payment';
import { paymentService } from '../services/paymentService';

const PaymentHistory: React.FC = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const [filters, setFilters] = useState({});
  const [showFilters, setShowFilters] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [sortBy, setSortBy] = useState<'date' | 'amount' | 'status'>('date');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
  
  // Snap payment modal state
  interface SnapPaymentData {
    snap_token: string;
    payment_url?: string;
    transaction_details?: Record<string, unknown>;
  }
  
  const [showSnapModal, setShowSnapModal] = useState(false);
  const [snapData, setSnapData] = useState<SnapPaymentData | null>(null);
  const [currentPayment, setCurrentPayment] = useState<Payment | null>(null);

  const {
    payments,
    pagination,
    isLoading,
    isError,
    error,
    refreshPayments,
  } = useTenantPayments({
    ...filters,
    search: searchQuery,
    sort_by: sortBy,
    sort_order: sortOrder,
    per_page: 12,
  });

  // Handle auto-open payment from dashboard
  interface LocationState {
    autoOpenPayment?: boolean;
    highlightPayment?: string;
  }
  
  useEffect(() => {
    const state = location.state as LocationState | null;
    if (state?.autoOpenPayment && state?.highlightPayment && payments?.length) {
      const paymentToOpen = payments.find(p => p.id.toString() === state.highlightPayment);
      if (paymentToOpen && paymentToOpen.status === 'pending') {
        // Auto open payment after a short delay
        setTimeout(() => {
          handlePayNow(paymentToOpen);
        }, 500);
        
        // Clear the state to prevent reopening
        window.history.replaceState({}, document.title, location.pathname);
      }
    }
  }, [payments, location.state]);

  // Handle callback URL parameters from Midtrans
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const orderId = urlParams.get('order_id');
    
    if (status && orderId) {
      console.log('📡 Received Midtrans callback:', { status, orderId });
      
      // Show appropriate message based on status
      switch (status) {
        case 'success':
          toast.success('🎉 Pembayaran berhasil! Terima kasih.');
          break;
        case 'pending':
          toast.loading('⏳ Pembayaran sedang diproses. Anda akan diberitahu setelah selesai.');
          break;
        case 'failed':
          toast.error('❌ Pembayaran gagal. Silakan coba lagi.');
          break;
      }
      
      // Clean up URL parameters
      const cleanUrl = window.location.pathname;
      window.history.replaceState({}, document.title, cleanUrl);
      
      // Refresh payment data to get latest status
      setTimeout(() => {
        refreshPayments();
      }, 1000);
    }
  }, [refreshPayments]);

  // Calculate statistics
  const stats = useMemo(() => {
    if (!payments?.length) return null;
    
    const totalPaid = payments
      .filter(p => ['paid', 'success', 'settlement', 'capture'].includes(p.status))
      .reduce((sum, p) => sum + p.amount, 0);
    
    const totalPending = payments
      .filter(p => ['pending', 'authorize'].includes(p.status))
      .reduce((sum, p) => sum + p.amount, 0);
    
    const totalOverdue = payments
      .filter(p => {
        const isOverdue = p.due_date && new Date(p.due_date) < new Date();
        const isUnpaid = !['paid', 'success', 'settlement', 'capture'].includes(p.status);
        return isOverdue && isUnpaid;
      })
      .reduce((sum, p) => sum + p.amount, 0);

    const paidCount = payments.filter(p => ['paid', 'success', 'settlement', 'capture'].includes(p.status)).length;
    const pendingCount = payments.filter(p => ['pending', 'authorize'].includes(p.status)).length;
    const overdueCount = payments.filter(p => {
      const isOverdue = p.due_date && new Date(p.due_date) < new Date();
      const isUnpaid = !['paid', 'success', 'settlement', 'capture'].includes(p.status);
      return isOverdue && isUnpaid;
    }).length;

    return {
      totalPaid,
      totalPending,
      totalOverdue,
      paidCount,
      pendingCount,
      overdueCount,
      totalPayments: payments.length,
      paymentRate: payments.length > 0 ? (paidCount / payments.length) * 100 : 0
    };
  }, [payments]);

  const handleSearch = (query: string) => {
    setSearchQuery(query);
  };

  const toggleSort = (field: 'date' | 'amount' | 'status') => {
    if (sortBy === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(field);
      setSortOrder('desc');
    }
  };

  const handleRefresh = () => {
    refreshPayments();
  };

  // Handle Custom Snap payment initiation
  const handlePayNow = async (payment: Payment) => {
    try {
      console.log('🔄 Initiating custom payment for:', payment.id);
      
      const data = await paymentService.getSnapPaymentData(payment.id);
      console.log('✅ Custom Snap data loaded:', data);
      
      setSnapData(data);
      setCurrentPayment(payment);
      setShowSnapModal(true);
      
    } catch (error: unknown) {
      console.error('❌ Failed to load Custom Snap payment:', error);
      toast.error('Gagal memuat pembayaran. Silakan coba lagi.');
    }
  };

  // Handle Snap payment success
  interface SnapPaymentResult {
    status_code?: string;
    status_message?: string;
    transaction_id?: string;
    order_id?: string;
    payment_type?: string;
    transaction_status?: string;
    fraud_status?: string;
  }
  
  const handleSnapSuccess = async (result: SnapPaymentResult) => {
    try {
      console.log('✅ Payment successful:', result);
      
      await paymentService.handleSnapSuccess(result);
      toast.success('Pembayaran berhasil! 🎉');
      
      setShowSnapModal(false);
      refreshPayments(); // Refresh payment list
      navigate(`/tenant/payments/success?order_id=${result.order_id}`);
      navigate(`/tenant/payments/success?order_id=${result.order_id}`);
      
    } catch (error) {
      console.error('Error handling payment success:', error);
      toast.error('Pembayaran selesai tetapi ada masalah. Silakan hubungi dukungan.');
    }
  };

  // Handle Snap payment pending
  const handleSnapPending = async (result: SnapPaymentResult) => {
    try {
      console.log('⏳ Payment pending:', result);
      
      await paymentService.handleSnapPending(result);
      toast.loading('Pembayaran sedang diproses. Anda akan diberitahu setelah selesai.', {
        duration: 4000
      });
      
      setShowSnapModal(false);
      refreshPayments();
      
    } catch (error) {
      console.error('Error handling payment pending:', error);
    }
  };

  // Handle Snap payment error
  const handleSnapError = async (result: SnapPaymentResult) => {
    try {
      console.error('❌ Payment error:', result);
      
      await paymentService.handleSnapError(result);
      toast.error(`Pembayaran gagal: ${result.status_message || 'Kesalahan tidak diketahui'}`);
      
    } catch (error) {
      console.error('Error handling payment error:', error);
    }
  };

  // Handle Snap popup close
  const handleSnapClose = () => {
    console.log('🔒 Snap popup closed by user');
    toast('Pembayaran dibatalkan. Anda dapat mencoba lagi kapan saja.');
  };

  if (isError) {
    return (
      <div className="min-h-screen flex items-center justify-center p-4">
        <div className="text-center max-w-md">
          <div className="w-20 h-20 bg-gradient-to-br from-red-100 to-red-200 rounded-full flex items-center justify-center mx-auto mb-6">
            <AlertCircle className="w-10 h-10 text-red-600" />
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-3">
            Gagal Memuat Riwayat Pembayaran
          </h2>
          <p className="text-gray-600 mb-6">
            {error?.message || 'Terjadi kesalahan saat memuat pembayaran Anda.'}
          </p>
          <Button onClick={handleRefresh} variant="primary" className="bg-gradient-to-r from-blue-600 to-purple-600">
            Coba Lagi
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className={mergeClasses(
      'min-h-screen bg-gradient-to-br from-gray-50 via-blue-50/20 to-purple-50/20',
      MOBILE_SPECIFIC.MOBILE_PADDING,
      'pb-24 md:pb-6'
    )}>
      <div className="max-w-7xl mx-auto space-y-8">
        {/* Modern Header */}
        <div className="bg-white/80 backdrop-blur-sm rounded-2xl border border-white/50 shadow-xl p-4 sm:p-6 md:p-8">
          <div className="flex flex-col gap-4 sm:gap-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                  <Wallet className="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                </div>
                <div className="min-w-0 flex-1">
                  <h1 className="text-xl sm:text-2xl md:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent truncate">
                    Riwayat Pembayaran
                  </h1>
                  <p className="text-xs sm:text-sm text-gray-600 mt-1 truncate">
                    {pagination?.total || 0} pembayaran • Diperbarui {formatDate(new Date().toISOString())}
                  </p>
                </div>
              </div>
              
              <div className="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={handleRefresh}
                  loading={isLoading}
                  className="border-gray-200 min-w-[44px] min-h-[44px] px-3 sm:px-4"
                >
                  <span className="hidden sm:inline">Muat Ulang</span>
                  <span className="sm:hidden">⟳</span>
                </Button>
                <Button
                  variant="primary"
                  size="sm"
                  onClick={() => setShowFilters(!showFilters)}
                  className="bg-gradient-to-r from-blue-600 to-purple-600 min-w-[44px] min-h-[44px] px-3 sm:px-4"
                  icon={Filter}
                >
                  <span className="hidden sm:inline">Filter</span>
                </Button>
              </div>
            </div>
          </div>
        </div>

        {/* Statistics Dashboard */}
        {stats && (
          <div className="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
            {/* Total Lunas */}
            <Card className="bg-gradient-to-br from-green-50 to-emerald-50 border-green-200/50 hover:shadow-lg transition-all duration-300">
              <div className="p-3 sm:p-4 md:p-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <div className="min-w-0 flex-1">
                    <p className="text-green-700 text-xs sm:text-sm font-medium mb-1 truncate">Total Lunas</p>
                    <p className="text-lg sm:text-xl md:text-2xl font-bold text-green-900 truncate">{formatCurrency(stats.totalPaid)}</p>
                    <p className="text-green-600 text-xs sm:text-sm mt-1">{stats.paidCount} pembayaran</p>
                  </div>
                  <div className="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0 self-end sm:self-auto">
                    <CheckCircle className="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-green-600" />
                  </div>
                </div>
              </div>
            </Card>

            {/* Total Pending */}
            <Card className="bg-gradient-to-br from-yellow-50 to-amber-50 border-yellow-200/50 hover:shadow-lg transition-all duration-300">
              <div className="p-3 sm:p-4 md:p-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <div className="min-w-0 flex-1">
                    <p className="text-yellow-700 text-xs sm:text-sm font-medium mb-1 truncate">Menunggu</p>
                    <p className="text-lg sm:text-xl md:text-2xl font-bold text-yellow-900 truncate">{formatCurrency(stats.totalPending)}</p>
                    <p className="text-yellow-600 text-xs sm:text-sm mt-1">{stats.pendingCount} pembayaran</p>
                  </div>
                  <div className="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-yellow-100 rounded-xl flex items-center justify-center flex-shrink-0 self-end sm:self-auto">
                    <Clock className="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-yellow-600" />
                  </div>
                </div>
              </div>
            </Card>

            {/* Total Terlambat */}
            <Card className="bg-gradient-to-br from-red-50 to-rose-50 border-red-200/50 hover:shadow-lg transition-all duration-300">
              <div className="p-3 sm:p-4 md:p-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <div className="min-w-0 flex-1">
                    <p className="text-red-700 text-xs sm:text-sm font-medium mb-1 truncate">Terlambat</p>
                    <p className="text-lg sm:text-xl md:text-2xl font-bold text-red-900 truncate">{formatCurrency(stats.totalOverdue)}</p>
                    <p className="text-red-600 text-xs sm:text-sm mt-1">{stats.overdueCount} pembayaran</p>
                  </div>
                  <div className="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0 self-end sm:self-auto">
                    <AlertCircle className="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-red-600" />
                  </div>
                </div>
              </div>
            </Card>

            {/* Payment Rate */}
            <Card className="bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-200/50 hover:shadow-lg transition-all duration-300">
              <div className="p-3 sm:p-4 md:p-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <div className="min-w-0 flex-1">
                    <p className="text-blue-700 text-xs sm:text-sm font-medium mb-1 truncate">Tingkat Bayar</p>
                    <p className="text-lg sm:text-xl md:text-2xl font-bold text-blue-900 truncate">{stats.paymentRate.toFixed(1)}%</p>
                    <p className="text-blue-600 text-xs sm:text-sm mt-1">{stats.totalPayments} total</p>
                  </div>
                  <div className="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0 self-end sm:self-auto">
                    <TrendingUp className="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-blue-600" />
                  </div>
                </div>
              </div>
            </Card>
          </div>
        )}

        {/* Search and Controls */}
        <Card className="bg-white/80 backdrop-blur-sm border-white/50">
          <div className="p-4 sm:p-6">
            <div className="flex flex-col gap-4">
              {/* Search */}
              <div className="relative w-full">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 sm:w-5 sm:h-5 text-gray-400" />
                <input
                  type="text"
                  placeholder="Cari pembayaran..."
                  value={searchQuery}
                  onChange={(e) => handleSearch(e.target.value)}
                  className="w-full pl-9 sm:pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base min-h-[44px]"
                />
              </div>

              {/* Controls Row */}
              <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                {/* View Mode Toggle */}
                <div className="flex items-center gap-1 p-1 bg-gray-100 rounded-lg w-fit">
                  <button
                    onClick={() => setViewMode('grid')}
                    className={mergeClasses(
                      'p-2 rounded-md transition-all min-w-[40px] min-h-[40px] flex items-center justify-center',
                      viewMode === 'grid' 
                        ? 'bg-white text-blue-600 shadow-sm' 
                        : 'text-gray-500 hover:text-gray-700'
                    )}
                  >
                    <Grid3X3 className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => setViewMode('list')}
                    className={mergeClasses(
                      'p-2 rounded-md transition-all min-w-[40px] min-h-[40px] flex items-center justify-center',
                      viewMode === 'list' 
                        ? 'bg-white text-blue-600 shadow-sm' 
                        : 'text-gray-500 hover:text-gray-700'
                    )}
                  >
                    <List className="w-4 h-4" />
                  </button>
                </div>

                {/* Sort Controls - Hide on small screens, use dropdown instead */}
                <div className="hidden sm:flex items-center gap-2 overflow-x-auto">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => toggleSort('date')}
                    className={mergeClasses(
                      'whitespace-nowrap min-h-[40px] px-3',
                      sortBy === 'date' ? 'bg-blue-50 text-blue-600' : ''
                    )}
                  >
                    <span className="text-xs sm:text-sm">Tanggal</span>
                    {sortBy === 'date' && (
                      sortOrder === 'asc' ? <SortAsc className="w-3 h-3 sm:w-4 sm:h-4 ml-1" /> : <SortDesc className="w-3 h-3 sm:w-4 sm:h-4 ml-1" />
                    )}
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => toggleSort('amount')}
                    className={mergeClasses(
                      'whitespace-nowrap min-h-[40px] px-3',
                      sortBy === 'amount' ? 'bg-blue-50 text-blue-600' : ''
                    )}
                  >
                    <span className="text-xs sm:text-sm">Jumlah</span>
                    {sortBy === 'amount' && (
                      sortOrder === 'asc' ? <SortAsc className="w-3 h-3 sm:w-4 sm:h-4 ml-1" /> : <SortDesc className="w-3 h-3 sm:w-4 sm:h-4 ml-1" />
                    )}
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => toggleSort('status')}
                    className={mergeClasses(
                      'whitespace-nowrap min-h-[40px] px-3',
                      sortBy === 'status' ? 'bg-blue-50 text-blue-600' : ''
                    )}
                  >
                    <span className="text-xs sm:text-sm">Status</span>
                    {sortBy === 'status' && (
                      sortOrder === 'asc' ? <SortAsc className="w-3 h-3 sm:w-4 sm:h-4 ml-1" /> : <SortDesc className="w-3 h-3 sm:w-4 sm:h-4 ml-1" />
                    )}
                  </Button>
                </div>

                {/* Mobile Sort Dropdown */}
                <div className="sm:hidden">
                  <select 
                    value={`${sortBy}_${sortOrder}`}
                    onChange={(e) => {
                      const [field, order] = e.target.value.split('_');
                      setSortBy(field as 'date' | 'amount' | 'status');
                      setSortOrder(order as 'asc' | 'desc');
                    }}
                    className="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm min-h-[44px]"
                  >
                    <option value="date_desc">Tanggal (Terbaru)</option>
                    <option value="date_asc">Tanggal (Terlama)</option>
                    <option value="amount_desc">Jumlah (Tertinggi)</option>
                    <option value="amount_asc">Jumlah (Terendah)</option>
                    <option value="status_asc">Status (A-Z)</option>
                    <option value="status_desc">Status (Z-A)</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </Card>

        {/* Filters Panel */}
        {showFilters && (
          <Card className="bg-white/80 backdrop-blur-sm border-white/50">
            <div className="p-6">
              <PaymentFilters
                filters={filters}
                onFiltersChange={setFilters}
                onClear={() => setFilters({})}
                onClose={() => setShowFilters(false)}
              />
            </div>
          </Card>
        )}

        {/* Payment List */}
        <div className="space-y-6">
          {isLoading ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
              {Array.from({ length: 6 }).map((_, i) => (
                <Card key={i} className="animate-pulse">
                  <div className="p-4 sm:p-6 space-y-4">
                    <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div className="h-6 sm:h-8 bg-gray-200 rounded w-1/2"></div>
                    <div className="h-4 bg-gray-200 rounded w-full"></div>
                    <div className="h-10 bg-gray-200 rounded"></div>
                  </div>
                </Card>
              ))}
            </div>
          ) : payments?.length === 0 ? (
            <Card className="bg-white/80 backdrop-blur-sm border-white/50">
              <div className="text-center py-16">
                <div className="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                  <CreditCard className="w-10 h-10 text-gray-400" />
                </div>
                <h3 className="text-2xl font-bold text-gray-800 mb-3">Tidak Ada Pembayaran</h3>
                <p className="text-gray-500 mb-6">
                  {searchQuery 
                    ? `Tidak ditemukan pembayaran yang cocok dengan "${searchQuery}"`
                    : 'Anda belum melakukan pembayaran apapun.'
                  }
                </p>
                {searchQuery && (
                  <Button 
                    variant="secondary" 
                    onClick={() => setSearchQuery('')}
                  >
                    Hapus Filter
                  </Button>
                )}
              </div>
            </Card>
          ) : (
            <>
              {viewMode === 'grid' ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                  {payments.map((payment) => (
                    <ModernPaymentCard 
                      key={payment.id} 
                      payment={payment} 
                      onPayNow={handlePayNow}
                      refreshPayments={refreshPayments}
                    />
                  ))}
                </div>
              ) : (
                <PaymentHistoryTable payments={payments} isLoading={isLoading} />
              )}
            </>
          )}
        </div>

        {/* Pagination */}
        {pagination && pagination.last_page > 1 && (
          <Card className="bg-white/80 backdrop-blur-sm border-white/50">
            <div className="p-4 sm:p-6">
              <div className="flex items-center justify-center">
                <p className="text-xs sm:text-sm text-gray-600 text-center">
                  <span className="block sm:inline">Halaman {pagination.current_page} dari {pagination.last_page}</span>
                  <span className="hidden sm:inline"> • </span>
                  <span className="block sm:inline">Menampilkan {payments?.length || 0} dari {pagination.total} pembayaran</span>
                </p>
              </div>
            </div>
          </Card>
        )}

        {/* Custom Snap Payment Modal */}
        {showSnapModal && snapData && currentPayment && (
          <CustomSnapPayment
            snapToken={snapData.snap_token}
            paymentData={{
              order_id: currentPayment.order_id,
              amount: currentPayment.amount,
              payment_month: currentPayment.payment_month || new Date().toISOString().slice(0, 7)
            }}
            onSuccess={handleSnapSuccess}
            onPending={handleSnapPending}
            onError={handleSnapError}
            onClose={handleSnapClose}
          />
        )}
      </div>
    </div>
  );
};

// Modern Payment Card Component
const ModernPaymentCard: React.FC<{ 
  payment: Payment; 
  onPayNow: (payment: Payment) => void;
  refreshPayments?: () => void;
}> = ({ payment, onPayNow, refreshPayments }) => {
  const [isProcessingPayment] = useState(false);
  const isPaid = ['paid', 'success', 'settlement', 'capture'].includes(payment.status);
  const isPending = ['pending', 'authorize'].includes(payment.status);
  const isFailed = ['failed', 'failure', 'cancel', 'deny', 'expire'].includes(payment.status);
  const isOverdue = payment.status === 'overdue';
  
  // Determine if payment can be made
  const canPay = ['pending', 'unpaid', 'failed'].includes(payment.status) || isOverdue;

  const getCardStyle = () => {
    if (isPaid) return 'border-green-200/50 bg-gradient-to-br from-green-50 to-emerald-50';
    if (isPending) return 'border-yellow-200/50 bg-gradient-to-br from-yellow-50 to-amber-50';
    if (isOverdue) return 'border-red-200/50 bg-gradient-to-br from-red-50 to-rose-50';
    if (isFailed) return 'border-gray-200/50 bg-gradient-to-br from-gray-50 to-slate-50';
    return 'border-gray-200/50 bg-white';
  };

  const getStatusBadge = () => {
    if (isPaid) return <StatusBadge status="success" label="Lunas" size="sm" />;
    if (isPending) return <StatusBadge status="warning" label="Menunggu" size="sm" />;
    if (isOverdue) return <StatusBadge status="error" label="Terlambat" size="sm" />;
    if (isFailed) return <StatusBadge status="error" label="Gagal" size="sm" />;
    return <StatusBadge status="neutral" label="Pending" size="sm" />;
  };

  return (
    <Card className={mergeClasses(
      getCardStyle(),
      'hover:shadow-xl transition-all duration-300 hover:-translate-y-1 group'
    )}>
      <div className="p-4 sm:p-6 space-y-3 sm:space-y-4">
        {/* Header */}
        <div className="flex items-start justify-between gap-2">
          <div className="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
            <div className={mergeClasses(
              'w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0',
              isPaid ? 'bg-green-100' : isPending ? 'bg-yellow-100' : 'bg-gray-100'
            )}>
              <CreditCard className={mergeClasses(
                'w-4 h-4 sm:w-5 sm:h-5',
                isPaid ? 'text-green-600' : isPending ? 'text-yellow-600' : 'text-gray-600'
              )} />
            </div>
            <div className="min-w-0 flex-1">
              <h3 className="font-semibold text-gray-900 text-xs sm:text-sm truncate">
                {payment.payment_month ? new Date(payment.payment_month + '-01').toLocaleDateString('id-ID', { 
                  month: 'long', 
                  year: 'numeric' 
                }) : 'Bulan Tidak Diketahui'}
              </h3>
              <p className="text-xs text-gray-500 truncate">ID: {payment.order_id}</p>
            </div>
          </div>
          <div className="flex-shrink-0">
            {getStatusBadge()}
          </div>
        </div>

        {/* Amount */}
        <div className="text-center py-2">
          <p className="text-xl sm:text-2xl font-bold text-gray-900">
            {formatCurrency(payment.amount)}
          </p>
          {payment.due_date && (
            <p className={mergeClasses(
              'text-xs sm:text-sm mt-1',
              isOverdue ? 'text-red-600 font-medium' : 'text-gray-500'
            )}>
              Jatuh Tempo: {formatDate(payment.due_date)}
            </p>
          )}
        </div>

        {/* Payment Date */}
        {payment.paid_at && (
          <div className="text-center py-2 border-t border-white/50">
            <p className="text-xs text-gray-500">Dibayar pada</p>
            <p className="text-xs sm:text-sm font-medium text-green-600">
              {formatDate(payment.paid_at)}
            </p>
          </div>
        )}

        {/* Actions */}
        <div className="pt-2 border-t border-white/50 space-y-2">
          {isPaid ? (
            <Button 
              variant="secondary" 
              size="sm" 
              className="w-full min-h-[44px] text-xs sm:text-sm"
              icon={Download}
            >
              <span className="hidden sm:inline">Unduh Kwitansi</span>
              <span className="sm:hidden">Unduh</span>
            </Button>
          ) : canPay ? (
            <>
              <Button 
                variant="primary" 
                size="sm" 
                className="w-full bg-gradient-to-r from-blue-600 to-purple-600 min-h-[44px] text-xs sm:text-sm"
                icon={ArrowUpRight}
                onClick={() => onPayNow(payment)}
                loading={isProcessingPayment}
              >
                {isProcessingPayment ? 'Memuat...' : (
                  <>
                    <span className="hidden sm:inline">Bayar Sekarang</span>
                    <span className="sm:hidden">Bayar</span>
                  </>
                )}
              </Button>
              
              {isPending && (
                <Button 
                  variant="ghost" 
                  size="sm" 
                  className="w-full text-xs min-h-[40px]"
                  onClick={async () => {
                    try {
                      const updatedPayment = await paymentService.checkPaymentStatus(payment.id);
                      
                      if (updatedPayment.status !== payment.status) {
                        toast.success(`Status terbaru: ${updatedPayment.status}`);
                        refreshPayments?.();
                      } else {
                        toast.success('Status sudah terbaru');
                      }
                    } catch (error: unknown) {
                      console.error('Error checking payment status:', error);
                      toast.error('Gagal mengecek status pembayaran');
                    }
                  }}
                >
                  <span className="hidden sm:inline">🔄 Cek Status Pembayaran</span>
                  <span className="sm:hidden">🔄 Cek Status</span>
                </Button>
              )}
            </>
          ) : (
            <Button 
              variant="secondary" 
              size="sm" 
              className="w-full min-h-[44px] text-xs sm:text-sm"
              disabled
            >
              {isPending ? (
                <>
                  <span className="hidden sm:inline">Sedang Diproses</span>
                  <span className="sm:hidden">Diproses</span>
                </>
              ) : (
                <>
                  <span className="hidden sm:inline">Tidak Dapat Dibayar</span>
                  <span className="sm:hidden">Tidak Bisa</span>
                </>
              )}
            </Button>
          )}
        </div>
      </div>
    </Card>
  );
};

export default PaymentHistory;