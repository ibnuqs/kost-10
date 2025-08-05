// File: src/pages/Admin/types/payment.ts
import type { BaseEntity } from './common';

export interface TenantUser {
  id: number;
  name: string;
  email: string;
  phone?: string;
}

// Updated Room interface to match room.ts structure completely
export interface Room {
  id: number;
  room_number: string;
  room_name: string;
  monthly_price: string;
  status: 'available' | 'occupied' | 'maintenance';
  tenant?: RoomTenant;
  tenants_count?: number;
  created_at?: string;
  updated_at?: string;
  // Keep backward compatibility with old properties
  name?: string;
  price?: string;
  type?: string;
}

// Add RoomTenant interface to match room.ts
export interface RoomTenant {
  id: number;
  user_id: number;
  room_id: number;
  monthly_rent: string;
  start_date: string;
  status: string;
  user?: TenantUser;
}

export interface Tenant {
  id: number;
  user_id: number;
  room_id: number;
  monthly_rent: string;
  start_date: string;
  end_date?: string;
  status: 'active' | 'inactive' | 'terminated';
  user: TenantUser;
  room?: Room;
  user_name?: string;
  user_email?: string;
}

export interface Payment extends BaseEntity {
  order_id: string;
  tenant_id: number;
  payment_month: string;
  amount: string;
  status: 'pending' | 'paid' | 'overdue' | 'expired' | 'cancelled';
  generation_type: 'auto' | 'manual';
  generated_by_user_id?: number;
  description?: string;
  regenerated_from?: number;
  payment_method?: string;
  snap_token?: string;
  snap_token_created_at?: string;
  paid_at?: string;
  expired_at?: string;
  failure_reason?: string;
  notes?: string;
  tenant: Tenant;
  generated_by_user?: {
    id: number;
    name: string;
    email: string;
  };
  regenerated_from_payment?: {
    id: number;
    order_id: string;
  };
}

export interface PaymentStats {
  total_payments: number;
  paid_this_month: number;
  pending_this_month: number;
  overdue_count: number;
  total_revenue_this_month: number | string;
  total_revenue_all_time: number | string;
}

// Backend API response structure
export interface PaymentStatsApiResponse {
  current_month: {
    month: string;
    total_payments: number;
    paid_payments: number;
    pending_payments: number;
    overdue_payments: number;
    total_amount: number;
    paid_amount: number;
    pending_amount: number;
    overdue_amount: number;
  };
  overall: {
    total_payments: number;
    collection_rate: number;
    average_payment: number;
    payment_methods: Array<{
      method: string;
      count: number;
      total_amount: number;
    }>;
  };
  recent_payments: Array<unknown>;
}

export interface PaymentFilters {
  search?: string;
  status?: string;
  month?: string;
  generation_type?: string;
  page?: number;
  per_page?: number;
}

export interface PaymentFormData {
  tenant_id: number;
  payment_month: string;
  amount: string;
  description?: string;
  notes?: string;
  prorate_from_date?: string;
  send_notification: boolean;
}

export interface PaymentsResponse {
  success: boolean;
  data: Payment[];
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  message?: string;
}

export interface PaymentResponse {
  success: boolean;
  data: Payment;
  message?: string;
}

export interface GeneratePaymentsResponse {
  success: boolean;
  data: {
    generated_count: number;
    payments: Payment[];
  };
  message?: string;
}