import React from 'react';
import { 
  TrendingUp, 
  Clock, 
  AlertTriangle, 
  CheckCircle2, 
  CreditCard,
  Users,
  Calendar,
  ArrowRight,
  Download,
  UserPlus,
  XCircle
} from 'lucide-react';
import { Card } from '../../ui';
import type { PaymentStats } from '../../../types';

interface PaymentDashboardProps {
  stats: PaymentStats | null;
  loading?: boolean;
  onQuickAction: (action: 'generate' | 'generate-individual' | 'overdue' | 'pending' | 'sync' | 'export' | 'expired') => void;
}

export const PaymentDashboard: React.FC<PaymentDashboardProps> = ({ 
  stats, 
  loading, 
  onQuickAction 
}) => {
  const formatCurrency = (amount: number | string) => {
    const num = parseInt(amount?.toString() || '0');
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(num);
  };

  const getCollectionRate = () => {
    if (!stats) return 0;
    const total = stats.paid_this_month + stats.pending_this_month + stats.overdue_count;
    if (total === 0) return 0;
    return Math.round((stats.paid_this_month / total) * 100);
  };

  const getUrgentTasksCount = () => {
    if (!stats) return 0;
    return stats.overdue_count + (stats.pending_this_month > 10 ? 1 : 0);
  };

  if (loading) {
    return (
      <div className="mb-8">
        <Card className="p-6 animate-pulse">
          <div className="h-8 bg-gray-200 rounded mb-4"></div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {Array.from({ length: 3 }).map((_, i) => (
              <div key={i} className="h-32 bg-gray-200 rounded"></div>
            ))}
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div className="mb-8">
      <Card className="overflow-hidden">
        <div className="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 text-white">
          <h2 className="text-xl font-bold">Dashboard Pembayaran</h2>
          <p className="text-blue-100 mt-1">
            Ringkasan status pembayaran dan aksi yang memerlukan perhatian
          </p>
        </div>

        <div className="p-6">
          {/* Overview Stats */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {/* Revenue Overview */}
            <div className="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-5">
              <div className="flex items-center justify-between mb-3">
                <h3 className="font-semibold text-green-900">Pendapatan Bulan Ini</h3>
                <TrendingUp className="w-5 h-5 text-green-600" />
              </div>
              <div className="text-2xl font-bold text-green-900 mb-2">
                {formatCurrency(stats?.total_revenue_this_month || 0)}
              </div>
              <div className="flex items-center text-sm text-green-700">
                <CheckCircle2 className="w-4 h-4 mr-1" />
                {stats?.paid_this_month || 0} pembayaran lunas
              </div>
            </div>

            {/* Collection Rate */}
            <div className="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-5">
              <div className="flex items-center justify-between mb-3">
                <h3 className="font-semibold text-blue-900">Tingkat Penagihan</h3>
                <CreditCard className="w-5 h-5 text-blue-600" />
              </div>
              <div className="text-2xl font-bold text-blue-900 mb-2">
                {getCollectionRate()}%
              </div>
              <div className="flex items-center text-sm text-blue-700">
                <Users className="w-4 w-4 mr-1" />
                dari {(stats?.paid_this_month || 0) + (stats?.pending_this_month || 0) + (stats?.overdue_count || 0)} tagihan
              </div>
            </div>

            {/* Urgent Tasks */}
            <div className={`bg-gradient-to-br rounded-xl p-5 border ${
              getUrgentTasksCount() > 0 
                ? 'from-red-50 to-red-100 border-red-200' 
                : 'from-gray-50 to-gray-100 border-gray-200'
            }`}>
              <div className="flex items-center justify-between mb-3">
                <h3 className={`font-semibold ${getUrgentTasksCount() > 0 ? 'text-red-900' : 'text-gray-900'}`}>
                  Perlu Perhatian
                </h3>
                <AlertTriangle className={`w-5 h-5 ${getUrgentTasksCount() > 0 ? 'text-red-600' : 'text-gray-600'}`} />
              </div>
              <div className={`text-2xl font-bold mb-2 ${getUrgentTasksCount() > 0 ? 'text-red-900' : 'text-gray-900'}`}>
                {getUrgentTasksCount()}
              </div>
              <div className={`flex items-center text-sm ${getUrgentTasksCount() > 0 ? 'text-red-700' : 'text-gray-700'}`}>
                <Clock className="w-4 h-4 mr-1" />
                {getUrgentTasksCount() > 0 ? 'tugas mendesak' : 'semua lancar'}
              </div>
            </div>
          </div>

          {/* Quick Actions */}
          <div>
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              
              {/* Generate Bills */}
              <button
                onClick={() => onQuickAction('generate')}
                className="group p-4 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-all duration-200 text-left"
              >
                <div className="flex items-center justify-between mb-2">
                  <Calendar className="w-5 h-5 text-blue-600" />
                  <ArrowRight className="w-4 h-4 text-blue-400 group-hover:text-blue-600 group-hover:translate-x-1 transition-all" />
                </div>
                <h4 className="font-medium text-blue-900 mb-1">Buat Tagihan Bulanan</h4>
                <p className="text-sm text-blue-700">Jalankan untuk semua penyewa aktif.</p>
              </button>

              {/* Generate Individual */}
              <button
                onClick={() => onQuickAction('generate-individual')}
                className="group p-4 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-all duration-200 text-left"
              >
                <div className="flex items-center justify-between mb-2">
                  <UserPlus className="w-5 h-5 text-green-600" />
                  <ArrowRight className="w-4 h-4 text-green-400 group-hover:text-green-600 group-hover:translate-x-1 transition-all" />
                </div>
                <h4 className="font-medium text-green-900 mb-1">Buat Tagihan Manual</h4>
                <p className="text-sm text-green-700">Buat tagihan untuk satu penyewa.</p>
              </button>

              {/* Handle Overdue & Expired */}
              <button
                onClick={() => onQuickAction('overdue')}
                className={`group p-4 border rounded-lg transition-all duration-200 text-left ${
                  (stats?.overdue_count || 0) > 0 
                    ? 'bg-red-50 hover:bg-red-100 border-red-200' 
                    : 'bg-gray-50 hover:bg-gray-100 border-gray-200'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <AlertTriangle className={`w-5 h-5 ${(stats?.overdue_count || 0) > 0 ? 'text-red-600' : 'text-gray-600'}`} />
                  <ArrowRight className={`w-4 h-4 transition-all group-hover:translate-x-1 ${
                    (stats?.overdue_count || 0) > 0 ? 'text-red-400 group-hover:text-red-600' : 'text-gray-400 group-hover:text-gray-600'
                  }`} />
                </div>
                <h4 className={`font-medium mb-1 ${(stats?.overdue_count || 0) > 0 ? 'text-red-900' : 'text-gray-900'}`}>
                  Tagihan Terlambat ({stats?.overdue_count || 0})
                </h4>
                <p className={`text-sm ${(stats?.overdue_count || 0) > 0 ? 'text-red-700' : 'text-gray-700'}`}>
                  Kelola tunggakan & tagihan kedaluwarsa.
                </p>
              </button>

              {/* Handle Pending */}
              <button
                onClick={() => onQuickAction('pending')}
                className={`group p-4 border rounded-lg transition-all duration-200 text-left ${
                  (stats?.pending_this_month || 0) > 0 
                    ? 'bg-yellow-50 hover:bg-yellow-100 border-yellow-200' 
                    : 'bg-gray-50 hover:bg-gray-100 border-gray-200'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <Clock className={`w-5 h-5 ${(stats?.pending_this_month || 0) > 0 ? 'text-yellow-600' : 'text-gray-600'}`} />
                  <ArrowRight className={`w-4 h-4 transition-all group-hover:translate-x-1 ${
                    (stats?.pending_this_month || 0) > 0 ? 'text-yellow-400 group-hover:text-yellow-600' : 'text-gray-400 group-hover:text-gray-600'
                  }`} />
                </div>
                <h4 className={`font-medium mb-1 ${(stats?.pending_this_month || 0) > 0 ? 'text-yellow-900' : 'text-gray-900'}`}>
                  Pembayaran Tertunda ({stats?.pending_this_month || 0})
                </h4>
                <p className={`text-sm ${(stats?.pending_this_month || 0) > 0 ? 'text-yellow-700' : 'text-gray-700'}`}>
                  Lihat semua pembayaran yang menunggu.
                </p>
              </button>

              {/* Export Report */}
              <button
                onClick={() => onQuickAction('export')}
                className="group p-4 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-all duration-200 text-left"
              >
                <div className="flex items-center justify-between mb-2">
                  <Download className="w-5 h-5 text-indigo-600" />
                  <ArrowRight className="w-4 h-4 text-indigo-400 group-hover:text-indigo-600 group-hover:translate-x-1 transition-all" />
                </div>
                <h4 className="font-medium text-indigo-900 mb-1">Ekspor Laporan</h4>
                <p className="text-sm text-indigo-700">Unduh data pembayaran (CSV).</p>
              </button>
              
            </div>
          </div>
        </div>
      </Card>
    </div>
  );
};