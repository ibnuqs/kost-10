import React from 'react';
import { Search, Filter, Calendar, Bot } from 'lucide-react';
import { PaymentFilters as PaymentFiltersType } from '../../../types';

export const PaymentFilters: React.FC<{
  filters: PaymentFiltersType;
  onFilterChange: (filters: PaymentFiltersType) => void;
}> = ({ filters, onFilterChange }) => {
  const handleClearFilters = () => {
    onFilterChange({
      search: '',
      status: '',
      month: '',
      generation_type: ''
    });
  };

  const hasActiveFilters = filters.search || filters.status || filters.month || filters.generation_type;

  return (
    <div className="p-6 border-b border-gray-200 bg-gray-50">
      <div className="flex flex-col lg:flex-row gap-4">
        {/* Search Input */}
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input
            type="text"
            placeholder="Cari pembayaran, nama penyewa, atau kode..."
            value={filters.search || ''}
            onChange={(e) => onFilterChange({ ...filters, search: e.target.value })}
            className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
          />
        </div>

        {/* Filter Controls */}
        <div className="flex flex-col sm:flex-row gap-3">
          {/* Status Filter */}
          <div className="relative">
            <Filter className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <select
              value={filters.status || ''}
              onChange={(e) => onFilterChange({ ...filters, status: e.target.value })}
              className="pl-10 pr-8 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white min-w-[140px]"
            >
              <option value="">Semua Status</option>
              <option value="paid">Lunas</option>
              <option value="pending">Menunggu</option>
              <option value="failed">Gagal</option>
              <option value="overdue">Terlambat</option>
            </select>
          </div>

          {/* Month Filter */}
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <input
              type="month"
              value={filters.month || ''}
              onChange={(e) => onFilterChange({ ...filters, month: e.target.value })}
              className="pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white min-w-[140px]"
            />
          </div>

          {/* Generation Type Filter */}
          <div className="relative">
            <Bot className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <select
              value={filters.generation_type || ''}
              onChange={(e) => onFilterChange({ ...filters, generation_type: e.target.value })}
              className="pl-10 pr-8 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white min-w-[140px]"
            >
              <option value="">Semua Jenis</option>
              <option value="auto">Otomatis</option>
              <option value="manual">Manual</option>
            </select>
          </div>

          {/* Clear Filters */}
          {hasActiveFilters && (
            <button
              onClick={handleClearFilters}
              className="px-4 py-3 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Bersihkan
            </button>
          )}
        </div>
      </div>

      {/* Active Filters Display */}
      {hasActiveFilters && (
        <div className="mt-4 flex flex-wrap gap-2">
          {filters.search && (
            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
              Pencarian: "{filters.search}"
              <button
                onClick={() => onFilterChange({ ...filters, search: '' })}
                className="ml-2 text-blue-600 hover:text-blue-800"
              >
                ×
              </button>
            </span>
          )}
          {filters.status && (
            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
              Status: {
                filters.status === 'paid' ? 'Lunas' :
                filters.status === 'pending' ? 'Menunggu' :
                filters.status === 'failed' ? 'Gagal' :
                filters.status === 'overdue' ? 'Terlambat' : filters.status
              }
              <button
                onClick={() => onFilterChange({ ...filters, status: '' })}
                className="ml-2 text-green-600 hover:text-green-800"
              >
                ×
              </button>
            </span>
          )}
          {filters.month && (
            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
              Bulan: {new Date(filters.month + '-01').toLocaleDateString('id-ID', { year: 'numeric', month: 'long' })}
              <button
                onClick={() => onFilterChange({ ...filters, month: '' })}
                className="ml-2 text-purple-600 hover:text-purple-800"
              >
                ×
              </button>
            </span>
          )}
          {filters.generation_type && (
            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
              Jenis: {filters.generation_type === 'manual' ? 'Manual' : 'Otomatis'}
              <button
                onClick={() => onFilterChange({ ...filters, generation_type: '' })}
                className="ml-2 text-orange-600 hover:text-orange-800"
              >
                ×
              </button>
            </span>
          )}
        </div>
      )}
    </div>
  );
};