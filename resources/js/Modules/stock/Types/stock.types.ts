// src/modules/stock/Types/stock.types.ts

export interface Product {
  id: number
  name: string
  sku?: string
  description?: string
  unit: string
  category?: string
  brand?: string
  min_stock_level: number
  critical_stock_level: number
  yellow_alert_level?: number
  red_alert_level?: number
  is_active: boolean
  total_stock: number
  current_stock: number // Alias for compatibility
  status?: 'active' | 'inactive' | 'critical' | 'low' | 'normal'
  batches?: Stock[]
  
  // Finansal Bilgiler
  average_cost?: number
  last_purchase_price?: number
  total_stock_value?: number
  potential_revenue?: number
  potential_profit?: number
  profit_margin?: number
  
  // Transaction Summary
  total_in?: number
  total_out?: number
  
  created_at?: string
  updated_at?: string
}

export interface Stock {
  id: number
  product_id: number
  product?: Product
  name: string // Provided by backend Resource for compatibility
  code?: string // Provided by backend Resource for compatibility
  
  // Product computed fields (when used as product list)
  category?: string
  sku?: string
  unit?: string
  brand?: string
  description?: string
  min_stock_level?: number
  critical_stock_level?: number
  yellow_alert_level?: number
  red_alert_level?: number
  expiry_yellow_days?: number
  expiry_red_days?: number
  total_stock?: number
  
  // Batch specific
  supplier_id: number
  supplier?: {
    id: number
    name: string
  }
  purchase_price: number
  currency?: string
  purchase_date: string
  expiry_date?: string
  current_stock: number
  reserved_stock?: number
  available_stock?: number
  
  // Alt Birim
  has_sub_unit?: boolean
  sub_unit_name?: string
  sub_unit_multiplier?: number
  current_sub_stock?: number
  total_base_units?: number
  
  // Status
  status?: 'active' | 'inactive' | 'deleted' | 'discontinued'
  is_active?: boolean
  track_expiry?: boolean
  storage_location?: string
  clinic_id: number
  clinic?: {
    id: number
    name: string
  }
  
  // Computed
  is_expired?: boolean
  is_near_expiry?: boolean
  days_to_expiry?: number
  stock_status?: string
  
  // Transaction Summary
  total_in?: number
  total_out?: number
  
  created_at?: string
  updated_at?: string
}

export interface CreateProductRequest {
  name: string
  sku?: string
  description?: string
  unit: string
  category?: string
  brand?: string
  min_stock_level?: number
  critical_stock_level?: number
  company_id: number
}

export interface CreateStockRequest {
  product_id: number
  supplier_id: number
  purchase_price: number
  currency?: string
  purchase_date: string
  expiry_date?: string
  current_stock: number
  clinic_id: number
  storage_location?: string
  track_expiry?: boolean
  has_sub_unit?: boolean
  sub_unit_name?: string
  sub_unit_multiplier?: number
  is_active?: boolean
}

export interface UpdateStockRequest {
  name?: string
  description?: string
  brand?: string
  unit?: string
  category?: string
  current_stock?: number
  current_sub_stock?: number
  min_stock_level?: number
  critical_stock_level?: number
  yellow_alert_level?: number
  red_alert_level?: number
  has_sub_unit?: boolean
  sub_unit_name?: string
  sub_unit_multiplier?: number
  purchase_price?: number
  currency?: string
  supplier_id?: number
  clinic_id?: number
  expiry_date?: string
  track_expiry?: boolean
  track_batch?: boolean
  storage_location?: string
  is_active?: boolean
  status?: 'active' | 'inactive' | 'deleted' | 'discontinued'
}

export interface StockAdjustmentRequest {
  type: 'increase' | 'decrease'
  quantity: number
  is_sub_unit: boolean
  reason: string
  notes?: string
  performed_by: string
}

export interface StockUsageRequest {
  quantity: number
  reason: string
  notes?: string
  used_by?: string
  performed_by: string
}

export interface StockLevel {
  level: 'normal' | 'low' | 'critical' | 'expired'
  color: 'green' | 'yellow' | 'red' | 'gray'
  message: string
}

export interface StockFilter {
  name?: string
  category?: string
  supplier_id?: number
  clinic_id?: number
  status?: string
  level?: 'normal' | 'low' | 'critical' | 'expired'
  expiry_days?: number
}

export interface StockStats {
  total_items: number
  low_stock_items: number
  critical_stock_items: number
  expiring_items: number
  total_value: number
}