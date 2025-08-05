import React, { useState, useEffect } from 'react';
import { Clock, AlertTriangle, RefreshCw, CheckCircle, XCircle } from 'lucide-react';
import { Modal } from '../../ui';
import api, { endpoints } from '../../../../../utils/api';
import type { AdminPayment as Payment } from '../../../types';
import { AxiosError } from 'axios';

interface ExpiredPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onRefresh: () => void;
}

export const ExpiredPaymentModal: React.FC<ExpiredPaymentModalProps> = ({
  isOpen,
  onClose,
  onRefresh
}) => {
  const [expiredPayments, setExpiredPayments] = useState<Payment[]>([]);
  const [loading, setLoading] = useState(false);
  const [regenerating, setRegenerating] = useState<Set<number>>(new Set());

  useEffect(() => {
    if (isOpen) {
      loadExpiredPayments();
    }
  }, [isOpen]);

  const loadExpiredPayments = async () => {
    try {
      setLoading(true);
      const response = await api.get(endpoints.admin.payments.expired);
      
      if (response.data.success) {
        setExpiredPayments(response.data.data || []);
      }
    } catch (error: unknown) {
      console.error('Failed to load expired payments:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleRegeneratePayment = async (paymentId: number) => {
    try {
      setRegenerating(prev => new Set([...prev, paymentId]));
      
      const response = await api.post(endpoints.admin.payments.regenerate(paymentId));
      
      if (response.data.success) {
        // Remove from expired list
        setExpiredPayments(prev => prev.filter(p => p.id !== paymentId));
        onRefresh(); // Refresh main payment table
      }
    } catch (error: unknown) {
      console.error('Failed to regenerate payment:', error);
      if (error instanceof AxiosError && error.response?.data?.message) {
        alert(error.response.data.message);
      } else if (error instanceof Error) {
        alert(error.message);
      } else {
        alert('Failed to regenerate payment');
      }
    } finally {
      setRegenerating(prev => {
        const newSet = new Set([...prev]);
        newSet.delete(paymentId);
        return newSet;
      });
    }
  };

  const formatDate = (dateString: string) => {
    try {
      return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch {
      return 'Invalid date';
    }
  };

  const formatCurrency = (amount: number) => {
    return `Rp ${amount.toLocaleString('id-ID')}`;
  };

  const getExpiryReason = (payment: Payment) => {
    if (payment.status === 'expired') {
      return payment.failure_reason || 'Payment expired';
    }
    
    if (payment.snap_token_created_at) {
      const hoursElapsed = Math.floor(
        (new Date().getTime() - new Date(payment.snap_token_created_at).getTime()) / (1000 * 60 * 60)
      );
      if (hoursElapsed >= 24) {
        return `Snap token expired (${hoursElapsed}h ago)`;
      }
    }
    
    const daysElapsed = Math.floor(
      (new Date().getTime() - new Date(payment.created_at).getTime()) / (1000 * 60 * 60 * 24)
    );
    if (daysElapsed >= 7) {
      return `Payment too old (${daysElapsed} days)`;
    }
    
    return 'Expired';
  };

  const getTimeRemaining = (payment: Payment) => {
    if (payment.status === 'expired') {
      return 'Expired';
    }

    if (payment.snap_token_created_at) {
      const hoursElapsed = Math.floor(
        (new Date().getTime() - new Date(payment.snap_token_created_at).getTime()) / (1000 * 60 * 60)
      );
      const remaining = 24 - hoursElapsed;
      if (remaining <= 0) {
        return 'Expired';
      }
      return `${remaining}h remaining`;
    }

    const daysElapsed = Math.floor(
      (new Date().getTime() - new Date(payment.created_at).getTime()) / (1000 * 60 * 60 * 24)
    );
    const remaining = 7 - daysElapsed;
    if (remaining <= 0) {
      return 'Expired';
    }
    return `${remaining} days remaining`;
  };

  return (
    <Modal 
      isOpen={isOpen} 
      onClose={onClose} 
      title="Expired Payments Management"
      maxWidth="xl"
    >
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <AlertTriangle className="w-5 h-5 text-orange-600" />
            <span className="text-lg font-semibold text-gray-900">
              Expired Payments ({expiredPayments.length})
            </span>
          </div>
          
          <button
            onClick={loadExpiredPayments}
            disabled={loading}
            className="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
          >
            <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            <span>Refresh</span>
          </button>
        </div>

        {/* Description */}
        <div className="bg-orange-50 border border-orange-200 rounded-lg p-4">
          <div className="flex items-start">
            <Clock className="w-5 h-5 text-orange-600 mr-3 mt-0.5" />
            <div className="text-sm text-orange-800">
              <p className="font-medium">About Payment Expiry</p>
              <p className="mt-1">
                Payments expire in two scenarios: 1) Snap token expires 24 hours after payment method selection, 
                or 2) Payment is older than 7 days without completion. Expired payments can be regenerated with a new payment link.
              </p>
            </div>
          </div>
        </div>

        {/* Payments List */}
        <div className="space-y-4 max-h-96 overflow-y-auto">
          {loading ? (
            <div className="flex items-center justify-center py-8">
              <RefreshCw className="animate-spin w-6 h-6 text-gray-400 mr-2" />
              <span className="text-gray-500">Loading expired payments...</span>
            </div>
          ) : expiredPayments.length === 0 ? (
            <div className="text-center py-8">
              <CheckCircle className="w-12 h-12 text-green-500 mx-auto mb-3" />
              <p className="text-lg font-medium text-gray-900">No Expired Payments</p>
              <p className="text-sm text-gray-500 mt-1">
                All payments are current or have been processed.
              </p>
            </div>
          ) : (
            expiredPayments.map((payment) => (
              <div
                key={payment.id}
                className="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
              >
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-3">
                      <XCircle className="w-5 h-5 text-red-500" />
                      <div>
                        <p className="font-medium text-gray-900">
                          {payment.tenant?.user?.name || 'Unknown Tenant'}
                        </p>
                        <p className="text-sm text-gray-500">
                          Order #{payment.order_id} • Room {payment.tenant?.room?.room_number}
                        </p>
                      </div>
                    </div>
                    
                    <div className="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                      <div>
                        <span className="text-gray-500">Amount:</span>
                        <p className="font-medium">{formatCurrency(typeof payment.amount === 'string' ? parseFloat(payment.amount) : payment.amount)}</p>
                      </div>
                      <div>
                        <span className="text-gray-500">Created:</span>
                        <p className="font-medium">{formatDate(payment.created_at)}</p>
                      </div>
                      <div>
                        <span className="text-gray-500">Status:</span>
                        <p className="font-medium text-red-600">{getTimeRemaining(payment)}</p>
                      </div>
                    </div>
                    
                    <div className="mt-2 text-sm text-gray-600">
                      <span className="font-medium">Reason:</span> {getExpiryReason(payment)}
                    </div>
                  </div>
                  
                  <div className="ml-4">
                    <button
                      onClick={() => handleRegeneratePayment(payment.id)}
                      disabled={regenerating.has(payment.id)}
                      className="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {regenerating.has(payment.id) ? (
                        <>
                          <RefreshCw className="w-4 h-4 animate-spin" />
                          <span>Regenerating...</span>
                        </>
                      ) : (
                        <>
                          <RefreshCw className="w-4 h-4" />
                          <span>Regenerate</span>
                        </>
                      )}
                    </button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-end space-x-3 pt-4 border-t">
          <button
            onClick={onClose}
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Close
          </button>
        </div>
      </div>
    </Modal>
  );
};