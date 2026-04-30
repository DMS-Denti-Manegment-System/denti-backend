// src/modules/stock/Components/StockList.tsx

import React, { useState, useCallback, useMemo } from 'react'
import { Card, Form, Typography } from 'antd'
import { useProducts, useStocks, useProductDetail, useStockStats } from '../Hooks/useStocks'
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

  const {
    adjustStock,
    useStock: executeStockUsage,
    isAdjusting,
    isUsing
  } = useStocks()

  const { data: globalStats, isLoading: isStatsLoading } = useStockStats()

  // Computed data
  const activeStocks = useMemo(() => {
    return stocks || []
  }, [stocks])

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

  const handleAdjust = useCallback((stock: any) => {
    // If it's a product with batches, we need to select a batch. 
    // For now, if it has batches, take the first one, or redirect.
    if (stock.batches && stock.batches.length > 0) {
        setSelectedStock(stock.batches[0])
        setIsAdjustModalVisible(true)
    } else {
        // Redirect to detail to add a batch first
        window.location.href = `/stock/products/${stock.id}`
    }
  }, [])

  const handleUse = useCallback((stock: any) => {
    if (stock.batches && stock.batches.length > 0) {
        setSelectedStock(stock.batches[0])
        setIsUseModalVisible(true)
    } else {
        window.location.href = `/stock/products/${stock.id}`
    }
  }, [])

  const handleViewHistory = useCallback((stock: any) => {
    setSelectedStock(stock)
    setIsHistoryModalVisible(true)
  }, [])

  const onFormSuccess = useCallback(() => {
    setIsFormModalVisible(false)
    setEditingStock(null)
    refetch()
  }, [refetch])

  return (
    <div>
      <Title level={2}>Stok Yönetimi</Title>
      
      <StockStats stats={globalStats} loading={isStatsLoading} />
      
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
          onDelete={() => {}} 
          onSoftDelete={() => {}}
          onHardDelete={() => {}}
          onReactivate={() => {}}
          onAdjust={handleAdjust}
          onUse={handleUse}
          onViewHistory={handleViewHistory}
        />
      </Card>

      <StockModals 
        isFormModalVisible={isFormModalVisible}
        editingStock={editingStock}
        onFormModalClose={() => setIsFormModalVisible(false)}
        onFormSuccess={onFormSuccess}
        
        isAdjustModalVisible={isAdjustModalVisible}
        selectedStock={selectedStock as any}
        adjustForm={adjustForm}
        onAdjustModalClose={() => setIsAdjustModalVisible(false)}
        onAdjustSubmit={async (values) => {
            if (selectedStock) {
                await adjustStock({ id: selectedStock.id, data: values })
                setIsAdjustModalVisible(false)
                adjustForm.resetFields()
                refetch()
            }
        }}
        isAdjusting={isAdjusting}
        
        isUseModalVisible={isUseModalVisible}
        useForm={useForm}
        onUseModalClose={() => setIsUseModalVisible(false)}
        onUseSubmit={async (values) => {
            if (selectedStock) {
                await executeStockUsage({ id: selectedStock.id, data: values })
                setIsUseModalVisible(false)
                useForm.resetFields()
                refetch()
            }
        }}
        isUsing={isUsing}
      />

      <StockHistoryModal 
        visible={isHistoryModalVisible}
        stock={selectedStock as any}
        onClose={() => setIsHistoryModalVisible(false)}
      />

      <BarcodeScannerModal 
        visible={isScannerVisible}
        onClose={() => setIsScannerVisible(false)}
      />
    </div>
  )
}