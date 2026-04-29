// src/modules/stock/Components/StockList.tsx

import React, { useState, useCallback, useMemo } from 'react'
import { Card, Form, Typography } from 'antd'
import { useProducts, useProductDetail } from '../Hooks/useStocks'
import { Product as Stock, StockFilter, StockAdjustmentRequest, StockUsageRequest } from '../Types/stock.types'

// Component imports
import { StockTable } from './StockTable'
import { StockFilters } from './StockFilters'
import { StockStats } from './StockStats'
import { StockAlerts } from './StockAlerts'
import { StockModals } from './StockModals'
import { StockHistoryModal } from './StockHistoryModal'
import { BarcodeScannerModal } from './BarcodeScannerModal'

import { useAuth } from '@/Modules/auth/Hooks/useAuth'

const { Title } = Typography

export const StockList: React.FC = () => {
  const { user } = useAuth()
  // State management
  const [filters, setFilters] = useState<StockFilter>({})
  const [editingStock, setEditingStock] = useState<Stock | null>(null)
  const [isFormModalVisible, setIsFormModalVisible] = useState(false)
  const [isAdjustModalVisible, setIsAdjustModalVisible] = useState(false)
  const [isUseModalVisible, setIsUseModalVisible] = useState(false)
  const [isHistoryModalVisible, setIsHistoryModalVisible] = useState(false)
  const [selectedStock, setSelectedStock] = useState<Stock | null>(null)
  
  // Form instances
  const [adjustForm] = Form.useForm()
  const [useForm] = Form.useForm()
  const [isScannerVisible, setIsScannerVisible] = useState(false)

  // Hooks
  const { 
    products: stocks, 
    isLoading, 
    refetch, 
    createProduct,
    isCreating
  } = useProducts(filters)

  // Computed data
  const activeStocks = useMemo(() => {
    if (!stocks) return []
    return stocks.filter(s => s.status !== 'deleted')
  }, [stocks])

  const stockStats = useMemo(() => {
    if (!activeStocks) return null
    return {
      total_items: activeStocks.length,
      low_stock_items: activeStocks.filter(s => (s as any).total_stock <= s.min_stock_level).length,
      critical_stock_items: activeStocks.filter(s => (s as any).total_stock <= s.critical_stock_level).length,
      expiring_items: 0, // Will be handled via alerts/reports
      total_value: activeStocks.reduce((sum, p) => {
        const batchesValue = (p.batches || []).reduce((bSum, b) => bSum + (b.purchase_price * b.current_stock), 0)
        return sum + batchesValue
      }, 0)
    }
  }, [activeStocks])

  const handleSearch = useCallback((value: string) => {
    setFilters(prev => ({ ...prev, search: value }))
  }, [])

  const handleFilterChange = useCallback((field: keyof StockFilter, value: string | number | undefined) => {
    setFilters(prev => ({ ...prev, [field]: value }))
  }, [])

  const handleAdd = useCallback(() => {
    setEditingStock(null)
    setIsFormModalVisible(true)
  }, [])

  const handleEdit = useCallback((stock: Stock) => {
    setEditingStock(stock)
    setIsFormModalVisible(true)
  }, [])

  const onFormSuccess = useCallback(() => {
    setIsFormModalVisible(false)
    setEditingStock(null)
    refetch()
  }, [refetch])

  return (
    <div>
      <Title level={2}>Stok Yönetimi</Title>
      
      <StockStats stats={stockStats} />
      
      <StockFilters 
        onSearch={handleSearch}
        onFilterChange={handleFilterChange}
        onAdd={handleAdd}
        onScannerOpen={() => setIsScannerVisible(true)}
      />

      <Card styles={{ body: { padding: 0 } }}>
        <StockTable 
          stocks={activeStocks}
          loading={isLoading}
          onEdit={handleEdit}
          onDelete={() => {}} // TODO: Implement product delete
          onSoftDelete={() => {}}
          onHardDelete={() => {}}
          onReactivate={() => {}}
          onAdjust={() => {}}
          onUse={() => {}}
          onViewHistory={() => {}}
        />
      </Card>

      <StockModals 
        isFormModalVisible={isFormModalVisible}
        editingStock={editingStock}
        onFormModalClose={() => setIsFormModalVisible(false)}
        onFormSuccess={onFormSuccess}
        
        isAdjustModalVisible={false}
        selectedStock={null}
        adjustForm={adjustForm}
        onAdjustModalClose={() => {}}
        onAdjustSubmit={async () => {}}
        isAdjusting={false}
        
        isUseModalVisible={false}
        useForm={useForm}
        onUseModalClose={() => {}}
        onUseSubmit={async () => {}}
        isUsing={false}
      />

      <BarcodeScannerModal 
        visible={isScannerVisible}
        onClose={() => setIsScannerVisible(false)}
      />
    </div>
  )
}