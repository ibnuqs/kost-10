import React, { useState, useEffect } from 'react';
import { Calendar, DollarSign, User, AlertCircle, CheckCircle, Users } from 'lucide-react';
import { Modal, ModalBody } from '../../ui';
import api, { endpoints } from '../../../../../utils/api';
import type { Tenant } from '../../../types/tenant';

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
};

interface GenerateIndividualPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onGenerate: (data: {
    tenant_id: number;
    payment_month: string;
    prorate_from_date?: string;
    send_notification: boolean;
  }) => Promise<void>;
  loading: boolean;
}

export const GenerateIndividualPaymentModal: React.FC<GenerateIndividualPaymentModalProps> = ({
  isOpen,
  onClose,
  onGenerate,
  loading
}) => {
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [selectedTenant, setSelectedTenant] = useState<number | null>(null);
  const [paymentMonth, setPaymentMonth] = useState(() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  });
  const [prorateFromDate, setProrateFromDate] = useState('');
  const [sendNotification, setSendNotification] = useState(true);
  const [useProrating, setUseProrating] = useState(false);
  const [description, setDescription] = useState('');
  const [notes, setNotes] = useState('');
  const [loadingTenants, setLoadingTenants] = useState(false);

  // Load active tenants when modal opens and reset form when closed
  useEffect(() => {
    if (isOpen) {
      loadActiveTenants();
    } else {
      // Reset form when modal is closed
      setSelectedTenant(null);
      setDescription('');
      setNotes('');
      setProrateFromDate('');
      setUseProrating(false);
      setSendNotification(true);
    }
  }, [isOpen]);

  const loadActiveTenants = async () => {
    try {
      setLoadingTenants(true);
      
      const response = await api.get(endpoints.admin.tenants.index, {
        params: { status: 'active', per_page: 100 }
      });
      
      if (response.data.success) {
        // Backend returns tenants directly in response.data.data (flat array)
        const tenantData = response.data.data || [];
        
        // Convert monthly_rent from string to number for calculations
        const processedTenants = tenantData.map(tenant => ({
          ...tenant,
          monthly_rent: parseFloat(tenant.monthly_rent)
        }));
        
        setTenants(processedTenants);
      } else {
        setTenants([]);
      }
    } catch (error) {
      console.error('Failed to load tenants:', error);
      setTenants([]);
    } finally {
      setLoadingTenants(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!selectedTenant) return;

    const data = {
      tenant_id: selectedTenant,
      payment_month: paymentMonth,
      send_notification: sendNotification,
      ...(description && { description }),
      ...(notes && { notes }),
      ...(useProrating && prorateFromDate && { prorate_from_date: prorateFromDate })
    };

    await onGenerate(data);
  };

  const selectedTenantData = tenants.find(t => t.id === selectedTenant);

  const calculateProratedAmount = () => {
    if (!useProrating || !prorateFromDate || !selectedTenantData) return null;

    const monthStart = new Date(paymentMonth + '-01');
    const prorateDate = new Date(prorateFromDate);
    const daysInMonth = new Date(monthStart.getFullYear(), monthStart.getMonth() + 1, 0).getDate();
    const remainingDays = daysInMonth - prorateDate.getDate() + 1;
    
    const dailyRate = selectedTenantData.monthly_rent / daysInMonth;
    const proratedAmount = dailyRate * remainingDays;

    return {
      originalAmount: selectedTenantData.monthly_rent,
      proratedAmount: Math.round(proratedAmount),
      remainingDays,
      daysInMonth
    };
  };

  const proratedCalculation = calculateProratedAmount();

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Buat Tagihan Manual" maxWidth="lg">
      <ModalBody>
        <form onSubmit={handleSubmit} className="space-y-8">
        {/* Select Tenant */}
        <div className="space-y-3">
          <label className="block text-sm font-medium text-gray-700 mb-3">
            Pilih Penyewa
          </label>
          <div className="relative">
            <Users className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            <select
              value={selectedTenant || ''}
              onChange={(e) => setSelectedTenant(Number(e.target.value))}
              className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
              required
              disabled={loadingTenants}
            >
              <option value="">
                {loadingTenants ? 'Memuat...' : tenants.length > 0 ? 'Pilih penyewa' : 'Tidak ada penyewa aktif'}
              </option>
              {tenants.map(tenant => (
                <option key={tenant.id} value={tenant.id}>
                  {tenant.user.name} - Kamar {tenant.room.room_number} (Rp {tenant.monthly_rent.toLocaleString('id-ID')})
                </option>
              ))}
            </select>
          </div>
          
          {/* No tenants available message */}
          {!loadingTenants && tenants.length === 0 && (
            <div className="mt-3 text-sm text-orange-600 bg-orange-50 border border-orange-200 rounded-lg p-4">
              <div className="flex items-center">
                <AlertCircle className="w-4 h-4 mr-2" />
                <span>Tidak ada penyewa aktif ditemukan. Pastikan ada penyewa dengan status "active".</span>
              </div>
            </div>
          )}
        </div>

        {/* Selected Tenant Info */}
        {selectedTenantData && (
          <div className="bg-gray-50 rounded-lg p-5">
            <div className="flex items-center mb-3">
              <User className="w-5 h-5 text-gray-500 mr-2" />
              <span className="text-sm font-medium text-gray-700">Informasi Penyewa Terpilih</span>
            </div>
            <div className="ml-7 space-y-2">
              <p className="font-semibold text-gray-900 text-base">{selectedTenantData.user.name}</p>
              <p className="text-sm text-gray-600">
                Kamar {selectedTenantData.room.room_number} - {selectedTenantData.room.room_name}
              </p>
              <p className="text-sm text-gray-600 font-medium">
                Sewa bulanan: {formatCurrency(selectedTenantData.monthly_rent)}
              </p>
            </div>
          </div>
        )}

        {/* Payment Month */}
        <div className="space-y-3">
          <label className="block text-sm font-medium text-gray-700 mb-3">
            Bulan Tagihan
          </label>
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="month"
              value={paymentMonth}
              onChange={(e) => setPaymentMonth(e.target.value)}
              className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
              required
            />
          </div>
        </div>

        {/* Prorating Option */}
        <div className="space-y-4">
          <div className="flex items-center mb-4">
            <input
              type="checkbox"
              id="useProrating"
              checked={useProrating}
              onChange={(e) => setUseProrating(e.target.checked)}
              className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
            />
            <label htmlFor="useProrating" className="ml-3 text-sm font-medium text-gray-700">
              Gunakan prorata (untuk penyewa yang masuk di tengah bulan)
            </label>
          </div>

          {useProrating && (
            <div className="ml-7 space-y-5 border-l-2 border-blue-200 pl-5">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Tanggal Mulai Sewa (Prorata)
                </label>
                <input
                  type="date"
                  value={prorateFromDate}
                  onChange={(e) => setProrateFromDate(e.target.value)}
                  className="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  required={useProrating}
                />
              </div>

              {proratedCalculation && (
                <div className="bg-blue-50 rounded-lg p-4">
                  <div className="flex items-center mb-3">
                    <DollarSign className="w-5 h-5 text-blue-600 mr-2" />
                    <span className="text-sm font-medium text-blue-900">Detail Perhitungan Prorata</span>
                  </div>
                  <div className="text-sm text-blue-800 space-y-2">
                    <p>Sewa per bulan: {formatCurrency(proratedCalculation.originalAmount)}</p>
                    <p>Hari tersisa: {proratedCalculation.remainingDays} dari {proratedCalculation.daysInMonth} hari</p>
                    <p className="font-semibold text-base">
                      Jumlah prorata: {formatCurrency(proratedCalculation.proratedAmount)}
                    </p>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Description */}
        <div className="space-y-3">
          <label className="block text-sm font-medium text-gray-700 mb-3">
            Deskripsi (Opsional)
          </label>
          <textarea
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            className="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 resize-none"
            rows={3}
            placeholder="Masukkan deskripsi untuk tagihan ini..."
          />
        </div>

        {/* Notes */}
        <div className="space-y-3">
          <label className="block text-sm font-medium text-gray-700 mb-3">
            Catatan Internal (Opsional)
          </label>
          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            className="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 resize-none"
            rows={2}
            placeholder="Catatan internal untuk admin..."
          />
        </div>

        {/* Notification Option */}
        <div className="space-y-3">
          <div className="flex items-center">
            <input
              type="checkbox"
              id="sendNotification"
              checked={sendNotification}
              onChange={(e) => setSendNotification(e.target.checked)}
              className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
            />
            <label htmlFor="sendNotification" className="ml-3 text-sm font-medium text-gray-700">
              Kirim notifikasi ke penyewa
            </label>
          </div>
          <p className="text-xs text-gray-500 ml-7">
            Notifikasi akan dikirim langsung via notifikasi dalam aplikasi.
          </p>
        </div>

        {/* Warning */}
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <div className="flex items-start">
            <AlertCircle className="w-5 h-5 text-yellow-600 mr-3 mt-0.5" />
            <div className="text-sm">
              <p className="text-yellow-800 font-medium mb-2">Perhatian</p>
              <p className="text-yellow-700 leading-relaxed">
                Pastikan tidak ada tagihan yang sudah dibuat untuk penyewa ini di bulan yang sama. 
                Sistem akan menolak jika ada duplikasi.
              </p>
            </div>
          </div>
        </div>

        {/* Buttons */}
        <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
          <button
            type="button"
            onClick={onClose}
            className="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            disabled={loading}
          >
            Batal
          </button>
          <button
            type="submit"
            disabled={loading || !selectedTenant}
            className="px-6 py-3 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center transition-colors"
          >
            {loading ? (
              <>
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Membuat Tagihan...
              </>
            ) : (
              <>
                <CheckCircle className="w-4 h-4 mr-2" />
                Buat Tagihan
              </>
            )}
          </button>
        </div>
        </form>
      </ModalBody>
    </Modal>
  );
};