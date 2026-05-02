// src/modules/stock/Components/StockLevelBadge.tsx

import React from 'react'
import { Tag } from 'antd'
import { Stock } from '../Types/stock.types'

interface StockLevelBadgeProps {
  stock: Stock
}

export const StockLevelBadge: React.FC<StockLevelBadgeProps> = ({ stock }) => {
  const getStockLevel = () => {
    const { current_stock, min_stock_level, critical_stock_level, expiry_date } = stock
    
    // Süre kontrolü
    if (expiry_date) {
      const expiryDate = new Date(expiry_date)
      const today = new Date()
      const daysUntilExpiry = Math.ceil((expiryDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
      
      if (daysUntilExpiry <= 0) {
        return { level: 'expired', color: 'default', text: 'Süresi Geçmiş' }
      }
      
      if (daysUntilExpiry <= (stock.expiry_red_days || 15)) {
        return { level: 'critical', color: 'red', text: `S.K.T: ${daysUntilExpiry} Gün!` }
      }

      if (daysUntilExpiry <= (stock.expiry_yellow_days || 30)) {
        return { level: 'warning', color: 'warning', text: `S.K.T: ${daysUntilExpiry} Gün` }
      }
    }
    
    // Stok seviye kontrolü
    const stockAmount = stock.has_sub_unit 
      ? (stock.total_base_units ?? ((stock.current_stock * (stock.sub_unit_multiplier || 1)) + (stock.current_sub_stock || 0)))
      : current_stock;

    if (stockAmount <= (critical_stock_level || 0)) {
      return { level: 'critical', color: 'red', text: 'Kritik Seviye' }
    }
    
    if (stockAmount <= (min_stock_level || 0)) {
      return { level: 'low', color: 'orange', text: 'Düşük Seviye' }
    }
    
    return { level: 'normal', color: 'green', text: 'Normal' }
  }

  const levelInfo = getStockLevel()

  return (
    <Tag color={levelInfo.color} style={{ margin: 0 }}>
      {levelInfo.text}
    </Tag>
  )
}