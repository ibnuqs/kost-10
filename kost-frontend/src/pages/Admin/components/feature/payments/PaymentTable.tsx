import React from 'react';
import { Eye, RefreshCw, AlertCircle, User, Settings, XCircle, Bot, UserCheck } from 'lucide-react';
import { StatusBadge, IconButton } from '../../ui';
import type { AdminPayment as Payment } from '../../../types';

interface PaginatedPayments {
  data: Payment[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
  from: number;
  to: number;
}

export const PaymentTable: React.FC<{
  payments?: Payment[] | PaginatedPayments; // Accept both array and paginated object
  loading?: boolean;
  onViewPayment: (payment: Payment) => void;
  onSyncPayment: (id: number) => void;
  onManualOverride?: (payment: Payment) => void;
  onVoidPayment?: (payment: Payment) => void;
}> = ({ 
  payments, 
  loading = false,
  onViewPayment, 
  onSyncPayment,
  onManualOverride,
  onVoidPayment
}) => {

  // Extract payments data from pagination or direct array
  const safePayments = React.useMemo(() => {
    if (!payments) {
      console.warn('PaymentTable - payments is null/undefined, using empty array');
      return [];
    }
    
    // If payments is an array, use it directly
    if (Array.isArray(payments)) {
      return payments;
    }
    
    // If payments is a pagination object, extract the data array
    if (typeof payments === 'object' && 'data' in payments && Array.isArray(payments.data)) {
      console.log('PaymentTable - extracting data from pagination object');
      return payments.data;
    }
    
    console.error('PaymentTable - payments is not a valid format:', payments);
    return [];
  }, [payments]);


  const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    try {
      return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    } catch (error) {
      console.error('Error formatting date:', dateString, error);
      return '-';
    }
  };

  const formatCurrency = (amount: string | number) => {
    try {
      const value = typeof amount === 'string' ? parseInt(amount) : amount;
      if (isNaN(value)) return 'Rp 0';
      return `Rp ${value.toLocaleString('id-ID')}`;
    } catch (error) {
      console.error('Error formatting currency:', amount, error);
      return 'Rp 0';
    }
  };

  // Safe accessor for nested properties with better fallbacks
  const getTenantName = (payment: Payment) => {
    // Handle new structure: payment.tenant.user.name
    if (payment?.tenant?.user?.name) {
      return payment.tenant.user.name;
    }
    // Handle old structure: payment.tenant.user_name (fallback)
    if (payment?.tenant && 'user_name' in payment.tenant && payment.tenant.user_name && payment.tenant.user_name !== 'N/A') {
      return payment.tenant.user_name;
    }
    return 'Nama penyewa tidak tersedia';
  };

  const getTenantEmail = (payment: Payment) => {
    // Handle new structure: payment.tenant.user.email
    if (payment?.tenant?.user?.email) {
      return payment.tenant.user.email;
    }
    // Handle old structure: payment.tenant.user_email (fallback)
    if (payment?.tenant && 'user_email' in payment.tenant && payment.tenant.user_email && payment.tenant.user_email !== 'N/A') {
      return payment.tenant.user_email;
    }
    return 'Email tidak tersedia';
  };

  const getStatusText = (status: string) => {
    switch (status?.toLowerCase()) {
      case 'paid':
        return 'Lunas';
      case 'pending':
        return 'Menunggu';
      case 'failed':
        return 'Gagal';
      case 'expired':
        return 'Kedaluwarsa';
      case 'overdue':
        return 'Terlambat';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return status || 'Tidak diketahui';
    }
  };

  const getGenerationTypeIcon = (type: string) => {
    return type === 'manual' ? <UserCheck className="h-3 w-3" /> : <Bot className="h-3 w-3" />;
  };

  const getGenerationTypeText = (type: string) => {
    return type === 'manual' ? 'Manual' : 'Otomatis';
  };

  const getGenerationTypeColor = (type: string) => {
    return type === 'manual' ? 'text-blue-600 bg-blue-50' : 'text-green-600 bg-green-50';
  };

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              ID Pesanan
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Penyewa
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Jumlah
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Bulan Bayar
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Jenis
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Kadaluarsa
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Dibayar Pada
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Aksi
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {loading ? (
            <tr>
              <td colSpan={9} className="px-6 py-12 text-center text-gray-500">
                <div className="flex items-center justify-center">
                  <RefreshCw className="animate-spin w-5 h-5 mr-2" />
                  Memuat data pembayaran...
                </div>
              </td>
            </tr>
          ) : safePayments.length === 0 ? (
            <tr>
              <td colSpan={9} className="px-6 py-12 text-center text-gray-500">
                <div className="text-center">
                  <p className="text-lg font-medium">Tidak ada pembayaran</p>
                  <p className="text-sm text-gray-400 mt-1">
                    Belum ada data pembayaran yang dapat ditampilkan.
                  </p>
                </div>
              </td>
            </tr>
          ) : (
            safePayments.map((payment) => {
              // Additional safety check for each payment
              if (!payment || !payment.id) {
                console.error('Invalid payment object:', payment);
                return null;
              }

              return (
                <tr key={payment.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-mono text-gray-900">
                      {payment.order_id || 'N/A'}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 h-8 w-8">
                        <div className="h-8 w-8 rounded-lg bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                          <User className="h-4 w-4 text-blue-600" />
                        </div>
                      </div>
                      <div className="ml-3">
                        <div className="text-sm font-medium text-gray-900">
                          {getTenantName(payment)}
                        </div>
                        <div className="text-sm text-gray-500">
                          {getTenantEmail(payment)}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-semibold text-gray-900">
                      {formatCurrency(payment.amount)}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      {payment.payment_month || '-'}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <StatusBadge 
                      status={payment.status} 
                      size="md"
                    >
                      {getStatusText(payment.status)}
                    </StatusBadge>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getGenerationTypeColor(payment.generation_type || 'auto')}`}>
                      {getGenerationTypeIcon(payment.generation_type || 'auto')}
                      <span className="ml-1">{getGenerationTypeText(payment.generation_type || 'auto')}</span>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className={`text-sm ${payment.expired_at && new Date(payment.expired_at) < new Date() ? 'text-red-600 font-medium' : 'text-gray-900'}`}>
                      {payment.expired_at ? formatDate(payment.expired_at) : '-'}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      {payment.paid_at ? formatDate(payment.paid_at) : '-'}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div className="flex items-center justify-end space-x-1">
                      <IconButton
                        icon={<Eye className="h-4 w-4" />}
                        tooltip="Lihat detail pembayaran"
                        variant="primary"
                        onClick={() => onViewPayment(payment)}
                      />
                      
                      <IconButton
                        icon={<RefreshCw className="h-4 w-4" />}
                        tooltip="Sinkronisasi status pembayaran"
                        variant="secondary"
                        onClick={() => onSyncPayment(payment.id)}
                      />

                      {onManualOverride && (
                        <IconButton
                          icon={<Settings className="h-4 w-4" />}
                          tooltip="Manual override status"
                          variant="secondary"
                          onClick={() => onManualOverride(payment)}
                        />
                      )}

                      {onVoidPayment && payment.status !== 'cancelled' && payment.status !== 'void' && (
                        <IconButton
                          icon={<XCircle className="h-4 w-4" />}
                          tooltip="Void/Cancel payment"
                          variant="danger"
                          onClick={() => onVoidPayment(payment)}
                        />
                      )}
                      
                      {payment.status === 'failed' && (
                        <IconButton
                          icon={<AlertCircle className="h-4 w-4" />}
                          tooltip="Pembayaran gagal - perlu perhatian"
                          variant="danger"
                          onClick={() => onViewPayment(payment)}
                        />
                      )}
                    </div>
                  </td>
                </tr>
              );
            })
          )}
        </tbody>
      </table>
    </div>
  );
};