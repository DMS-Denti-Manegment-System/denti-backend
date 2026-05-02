// src/modules/alerts/Types/alert.types.ts

export interface Alert {
    id: number
    type: AlertType
    severity: AlertSeverity
    title: string
    message: string
    stock_id?: number
    clinic_id?: number
    threshold_level?: number
    current_stock_level?: number
    expiry_date?: string
    days_until_expiry?: number
    status: AlertStatus
    is_resolved: boolean
    resolved_by?: string
    resolved_at?: string
    resolution_notes?: string
    created_at: string
    updated_at: string
    
    // Relations
    stock?: {
      id: number
      name: string
      current_stock: number
      min_stock_level: number
      critical_stock_level: number
      unit: string
      category: string
      brand?: string
      storage_location?: string
    }
    clinic?: {
      id: number
      name: string
      specialty_code?: string
    }
  }
  
  export type AlertType = 
    | 'low_stock'           // Düşük stok
    | 'critical_stock'      // Kritik stok
    | 'out_of_stock'        // Stok bitti
    | 'expiry_warning'      // Son kullanma uyarısı
    | 'expiry_critical'     // Son kullanma kritik
    | 'expired'             // Süresi geçmiş
    | 'near_expiry'         // Son kullanma yaklaşıyor
    | 'critical_expiry'     // Son kullanma kritik yaklaşıyor
    | 'stock_request'       // Stok talebi
    | 'stock_transfer'      // Stok transferi
    | 'system'              // Sistem uyarısı
  
  export type AlertSeverity = 
    | 'low'                 // Düşük (Info)
    | 'medium'              // Orta (Warning) 
    | 'high'                // Yüksek (Error)
    | 'critical'            // Kritik (Critical)
  
  export type AlertStatus = 
    | 'active'              // Aktif
    | 'resolved'            // Çözülmüş
    | 'dismissed'           // Yok sayılmış
    | 'auto_resolved'       // Otomatik çözülmüş
  
  export interface CreateAlertRequest {
    type: AlertType
    severity: AlertSeverity
    title: string
    message: string
    stock_id?: number
    clinic_id?: number
    threshold_level?: number
    current_stock_level?: number
    expiry_date?: string
    days_until_expiry?: number
  }
  
  export interface ResolveAlertRequest {
    resolution_notes?: string
    resolved_by?: string
  }
  
  export interface AlertFilters {
    type?: AlertType
    severity?: AlertSeverity
    status?: AlertStatus
    clinic_id?: number
    stock_id?: number
    is_resolved?: boolean
    date_from?: string
    date_to?: string
    search?: string
  }
  
  export interface AlertStats {
    total: number
    active: number
    resolved: number
    dismissed: number
    critical: number
    warning: number
    info: number
    by_severity: {
      low: number
      medium: number
      high: number
      critical: number
    }
    by_type: {
      low_stock: number
      critical_stock: number
      out_of_stock: number
      expiry_warning: number
      expiry_critical: number
      expired: number
      near_expiry?: number
      stock_request: number
      stock_transfer: number
      system: number
    }
  }
  
  export interface AlertSettings {
    email_notifications: boolean
    sms_notifications: boolean
    browser_notifications: boolean
    severity_threshold: AlertSeverity
    auto_resolve_expired: boolean
    notification_frequency: 'realtime' | 'hourly' | 'daily'
  }