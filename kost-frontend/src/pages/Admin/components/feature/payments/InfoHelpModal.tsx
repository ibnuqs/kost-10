import React from 'react';
import { 
  HelpCircle, 
  CreditCard, 
  Users, 
  Calendar, 
  Eye, 
  RefreshCw, 
  Settings, 
  XCircle, 
  Bot, 
  UserCheck, 
  Download, 
  Search, 
  Filter, 
  BarChart3,
  Plus,
  AlertTriangle,
  FileText,
  Clock,
  DollarSign
} from 'lucide-react';
import { Modal, ModalBody, ModalFooter } from '../../ui';

interface InfoHelpModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const InfoHelpModal: React.FC<InfoHelpModalProps> = ({ isOpen, onClose }) => {
  const features = [
    {
      icon: <BarChart3 className="w-5 h-5 text-blue-600" />,
      title: "Dashboard Overview",
      description: "Melihat statistik pembayaran secara real-time",
      features: [
        "Total pembayaran bulan ini",
        "Pembayaran lunas dan tertunggak", 
        "Total revenue dan collection rate",
        "Quick actions untuk operasi cepat"
      ]
    },
    {
      icon: <CreditCard className="w-5 h-5 text-green-600" />,
      title: "Generate Pembayaran",
      description: "Membuat tagihan pembayaran untuk penyewa",
      features: [
        "Generate massal bulanan untuk semua penyewa aktif",
        "Generate individual dengan prorata untuk penyewa baru", 
        "Pre-check validasi sebelum generate",
        "Notifikasi otomatis ke penyewa"
      ]
    },
    {
      icon: <Search className="w-5 h-5 text-purple-600" />,
      title: "Filter & Pencarian",
      description: "Mencari dan memfilter pembayaran dengan mudah",
      features: [
        "Pencarian berdasarkan nama penyewa atau order ID",
        "Filter status: Lunas, Menunggu, Terlambat, Expired",
        "Filter bulan pembayaran",
        "Filter jenis generate: Manual atau Otomatis"
      ]
    },
    {
      icon: <Eye className="w-5 h-5 text-indigo-600" />,
      title: "Detail Pembayaran",
      description: "Melihat informasi lengkap setiap pembayaran",
      features: [
        "Info lengkap penyewa dan kamar",
        "Timeline pembayaran dengan timestamp",
        "Detail teknis Midtrans (snap token)",
        "Info audit trail (siapa yang membuat)"
      ]
    },
    {
      icon: <RefreshCw className="w-5 h-5 text-orange-600" />,
      title: "Sinkronisasi Status",
      description: "Memperbarui status pembayaran dari gateway",
      features: [
        "Sync individual per pembayaran",
        "Bulk sync semua pembayaran sekaligus", 
        "Auto update status dari Midtrans",
        "Notifikasi hasil sinkronisasi"
      ]
    },
    {
      icon: <Settings className="w-5 h-5 text-gray-600" />,
      title: "Manual Override",
      description: "Mengubah status pembayaran secara manual",
      features: [
        "Override status untuk kasus khusus",
        "Wajib memberikan alasan perubahan",
        "Audit trail lengkap",
        "Hanya untuk admin yang berwenang"
      ]
    },
    {
      icon: <XCircle className="w-5 h-5 text-red-600" />,
      title: "Void/Cancel Payment",
      description: "Membatalkan pembayaran yang bermasalah",
      features: [
        "Void payment yang error",
        "Cancel payment yang tidak valid",
        "Wajib memberikan alasan pembatalan",
        "Riwayat pembatalan tersimpan"
      ]
    },
    {
      icon: <Download className="w-5 h-5 text-teal-600" />,
      title: "Export Data",
      description: "Mengunduh data pembayaran untuk laporan",
      features: [
        "Export ke Excel/CSV",
        "Filter data sebelum export",
        "Include semua detail pembayaran",
        "Format siap untuk laporan"
      ]
    },
    {
      icon: <AlertTriangle className="w-5 h-5 text-yellow-600" />,
      title: "Deteksi Expired Payment",
      description: "Mengelola pembayaran yang kadaluarsa",
      features: [
        "Auto highlight payment expired",
        "Notifikasi visual di tabel",
        "Filter khusus untuk expired payments",
        "Action untuk regenerate payment"
      ]
    }
  ];

  const tableColumns = [
    {
      name: "ID Pesanan",
      description: "Unique identifier untuk setiap pembayaran"
    },
    {
      name: "Penyewa", 
      description: "Nama dan email penyewa"
    },
    {
      name: "Jumlah",
      description: "Total tagihan dalam format Rupiah"
    },
    {
      name: "Bulan Bayar",
      description: "Periode pembayaran (YYYY-MM)"
    },
    {
      name: "Status",
      description: "Status pembayaran dengan color coding"
    },
    {
      name: "Jenis",
      description: "Manual (dibuat admin) atau Otomatis (sistem)"
    },
    {
      name: "Kadaluarsa", 
      description: "Tanggal expired payment (highlight merah jika lewat)"
    },
    {
      name: "Dibayar Pada",
      description: "Timestamp pembayaran sukses"
    },
    {
      name: "Aksi",
      description: "Tombol operasi: View, Sync, Override, Void"
    }
  ];

  const statusTypes = [
    {
      status: "Lunas",
      color: "bg-green-100 text-green-800",
      description: "Pembayaran berhasil dan confirmed"
    },
    {
      status: "Menunggu", 
      color: "bg-yellow-100 text-yellow-800",
      description: "Menunggu pembayaran dari penyewa"
    },
    {
      status: "Terlambat",
      color: "bg-orange-100 text-orange-800", 
      description: "Melewati due date namun belum expired"
    },
    {
      status: "Kedaluwarsa",
      color: "bg-red-100 text-red-800",
      description: "Payment expired dan perlu regenerate"
    },
    {
      status: "Dibatalkan",
      color: "bg-gray-100 text-gray-800",
      description: "Payment dibatalkan oleh admin"
    }
  ];

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Info & Panduan Halaman Manajemen Pembayaran" maxWidth="2xl">
      <ModalBody>
        <div className="space-y-8">
          {/* Overview */}
          <div className="bg-blue-50 rounded-lg p-4 sm:p-6">
            <div className="flex items-center mb-4">
              <HelpCircle className="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 mr-2 sm:mr-3 flex-shrink-0" />
              <h3 className="text-base sm:text-lg font-semibold text-blue-900">Tentang Halaman Ini</h3>
            </div>
            <p className="text-sm sm:text-base text-blue-800 leading-relaxed">
              Halaman Manajemen Pembayaran adalah pusat kontrol untuk mengelola seluruh aspek pembayaran penyewa kost. 
              Dari generate tagihan bulanan hingga monitoring status pembayaran secara real-time, semua fitur terintegrasi 
              dengan sistem pembayaran Midtrans untuk kemudahan dan akurasi.
            </p>
          </div>

          {/* Main Features */}
          <div>
            <h4 className="text-base sm:text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <BarChart3 className="w-4 h-4 sm:w-5 sm:h-5 mr-2 flex-shrink-0" />
              Fitur Utama
            </h4>
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
              {features.map((feature, index) => (
                <div key={index} className="border border-gray-200 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                  <div className="flex items-start mb-3">
                    <div className="flex-shrink-0">
                      {feature.icon}
                    </div>
                    <div className="ml-2 sm:ml-3">
                      <h5 className="text-sm sm:text-base font-medium text-gray-900">{feature.title}</h5>
                      <p className="text-xs sm:text-sm text-gray-600 mt-1">{feature.description}</p>
                    </div>
                  </div>
                  <ul className="space-y-1">
                    {feature.features.map((item, idx) => (
                      <li key={idx} className="text-xs sm:text-sm text-gray-700 flex items-start">
                        <span className="w-1.5 h-1.5 bg-gray-400 rounded-full mt-1.5 sm:mt-2 mr-2 flex-shrink-0"></span>
                        {item}
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
          </div>

          {/* Table Columns */}
          <div>
            <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <FileText className="w-5 h-5 mr-2" />
              Kolom Tabel Pembayaran
            </h4>
            <div className="bg-gray-50 rounded-lg p-4">
              <div className="space-y-3">
                {tableColumns.map((column, index) => (
                  <div key={index} className="flex justify-between items-start">
                    <span className="font-medium text-gray-900 min-w-[120px]">{column.name}:</span>
                    <span className="text-sm text-gray-600 flex-1 ml-4">{column.description}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Status Types */}
          <div>
            <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <Clock className="w-5 h-5 mr-2" />
              Status Pembayaran
            </h4>
            <div className="space-y-3">
              {statusTypes.map((status, index) => (
                <div key={index} className="flex items-center space-x-3">
                  <span className={`px-3 py-1 rounded-full text-xs font-medium ${status.color}`}>
                    {status.status}
                  </span>
                  <span className="text-sm text-gray-600">{status.description}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Generation Types */}
          <div>
            <h4 className="text-base sm:text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <Bot className="w-4 h-4 sm:w-5 sm:h-5 mr-2 flex-shrink-0" />
              Jenis Generate Payment
            </h4>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
              <div className="border border-green-200 rounded-lg p-3 sm:p-4 bg-green-50">
                <div className="flex items-center mb-2">
                  <Bot className="w-4 h-4 text-green-600 mr-2 flex-shrink-0" />
                  <span className="text-sm sm:text-base font-medium text-green-900">Otomatis</span>
                </div>
                <p className="text-xs sm:text-sm text-green-800">
                  Dibuat oleh sistem secara otomatis saat generate bulanan. 
                  Biasanya untuk semua penyewa aktif pada waktu bersamaan.
                </p>
              </div>
              <div className="border border-blue-200 rounded-lg p-3 sm:p-4 bg-blue-50">
                <div className="flex items-center mb-2">
                  <UserCheck className="w-4 h-4 text-blue-600 mr-2 flex-shrink-0" />
                  <span className="text-sm sm:text-base font-medium text-blue-900">Manual</span>
                </div>
                <p className="text-xs sm:text-sm text-blue-800">
                  Dibuat manual oleh admin untuk kasus khusus seperti penyewa baru, 
                  prorata, atau regenerate payment yang bermasalah.
                </p>
              </div>
            </div>
          </div>

          {/* Tips */}
          <div className="bg-yellow-50 rounded-lg p-4 sm:p-6">
            <h4 className="text-base sm:text-lg font-semibold text-yellow-900 mb-4 flex items-center">
              <DollarSign className="w-4 h-4 sm:w-5 sm:h-5 mr-2 flex-shrink-0" />
              Tips Penggunaan
            </h4>
            <ul className="space-y-2 text-yellow-800">
              <li className="flex items-start">
                <span className="w-1.5 h-1.5 bg-yellow-600 rounded-full mt-1.5 sm:mt-2 mr-3 flex-shrink-0"></span>
                <span className="text-xs sm:text-sm">
                  Gunakan <strong>Pre-check</strong> sebelum generate bulanan untuk menghindari duplikasi
                </span>
              </li>
              <li className="flex items-start">
                <span className="w-1.5 h-1.5 bg-yellow-600 rounded-full mt-1.5 sm:mt-2 mr-3 flex-shrink-0"></span>
                <span className="text-xs sm:text-sm">
                  <strong>Sync berkala</strong> status pembayaran untuk data yang akurat
                </span>
              </li>
              <li className="flex items-start">
                <span className="w-1.5 h-1.5 bg-yellow-600 rounded-full mt-1.5 sm:mt-2 mr-3 flex-shrink-0"></span>
                <span className="text-xs sm:text-sm">
                  Gunakan <strong>filter Generation Type</strong> untuk membedakan payment manual vs otomatis
                </span>
              </li>
              <li className="flex items-start">
                <span className="w-1.5 h-1.5 bg-yellow-600 rounded-full mt-1.5 sm:mt-2 mr-3 flex-shrink-0"></span>
                <span className="text-xs sm:text-sm">
                  <strong>Export data</strong> secara berkala untuk backup dan laporan
                </span>
              </li>
              <li className="flex items-start">
                <span className="w-1.5 h-1.5 bg-yellow-600 rounded-full mt-1.5 sm:mt-2 mr-3 flex-shrink-0"></span>
                <span className="text-xs sm:text-sm">
                  Monitor payment <strong>expired</strong> dan regenerate jika diperlukan
                </span>
              </li>
            </ul>
          </div>

        </div>
      </ModalBody>
      
      <ModalFooter>
        <button
          onClick={onClose}
          className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Mengerti
        </button>
      </ModalFooter>
    </Modal>
  );
};