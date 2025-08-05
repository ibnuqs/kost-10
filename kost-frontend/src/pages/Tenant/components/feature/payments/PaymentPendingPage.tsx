import React from 'react';
import { CheckCircle, Clock, AlertCircle } from 'lucide-react';
import { Button } from '../../ui/Buttons';
import { Card } from '../../ui/Card';
import { useNavigate, useSearchParams } from 'react-router-dom';

interface PaymentPendingPageProps {
  // No specific props needed for now, will use URL params
}

const PaymentPendingPage: React.FC<PaymentPendingPageProps> = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const orderId = searchParams.get('order_id');

  const handleGoToPayments = () => {
    navigate('/tenant/payments');
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-4 bg-gray-50">
      <Card className="max-w-md w-full text-center p-6 sm:p-8">
        <div className="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <Clock className="w-10 h-10 text-yellow-600" />
        </div>
        <h2 className="text-2xl font-bold text-gray-900 mb-3">Pembayaran Sedang Diproses</h2>
        <p className="text-gray-600 mb-4">
          Pembayaran Anda dengan Order ID <span className="font-medium text-gray-800">{orderId || 'N/A'}</span> sedang dalam proses verifikasi.
        </p>
        <p className="text-gray-600 mb-6">
          Kami akan memberitahu Anda setelah status pembayaran diperbarui.
        </p>
        <Button onClick={handleGoToPayments} variant="primary">
          Kembali ke Riwayat Pembayaran
        </Button>
      </Card>
    </div>
  );
};

export default PaymentPendingPage;
