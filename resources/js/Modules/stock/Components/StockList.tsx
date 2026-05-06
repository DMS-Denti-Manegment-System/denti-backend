// src/modules/stock/Components/StockList.tsx

import React, { useState, useCallback, useMemo, useEffect, lazy, Suspense } from 'react'
import { Card, Form, Typography } from 'antd'
import { router } from '@inertiajs/react'
import { useProducts, useStocks, useStockStats } from '../Hooks/useStocks'
import { Product as Stock, StockFilter } from '../Types/stock.types'

// Component imports
import { StockTable } from './StockTable'
import { StockFilters } from './StockFilters'
import { StockStats } from './StockStats'

const { Title } = Typography
const StockModals = lazy(() => import('./StockModals').then((module) => ({ default: module.StockModals })))
const StockHistoryModal = lazy(() => import('./StockHistoryModal').then((module) => ({ default: module.StockHistoryModal })))
const BarcodeScannerModal = lazy(() => import('./BarcodeScannerModal').then((module) => ({ default: module.BarcodeScannerModal })))

export const StockList: React.FC = () => {
  const [filters, setFilters] = useState<StockFilter & { page?: number; per_page?: number }>({
    page: 1,
    per_page: 15
  })
  const [searchTerm, setSearchTerm] = useState('')
  const [editingStock, setEditingStock] = useState<Stock | null>(null)
  const [isFormModalVisible, setIsFormModalVisible] = useState(false)
  const [isAdjustModalVisible, setIsAdjustModalVisible] = useState(false)
  const [isUseModalVisible, setIsUseModalVisible] = useState(false)
  const [isHistoryModalVisible, setIsHistoryModalVisible] = useState(false)
  const [selectedStock, setSelectedStock] = useState<Stock | null>(null)
  const [enableSecondaryData, setEnableSecondaryData] = useState(false)
  
  // Form instances
  const [adjustForm] = Form.useForm()
  const [useForm] = Form.useForm()
  const [isScannerVisible, setIsScannerVisible] = useState(false)

  // Debounce search
  useEffect(() => {
    const timer = setTimeout(() => {
      setFilters(prev => ({ ...prev, search: searchTerm, page: 1 }))
    }, 500)
    return () => clearTimeout(timer)
  }, [searchTerm])

  // Hooks
  const { 
    products: stocksData, 
    meta,
    isLoading, 
    refetch
  } = useProducts(filters, filters.page, filters.per_page)

  const stocks = stocksData || []

  const {
    adjustStock,
    useStock: executeStockUsage,
    deleteStock,
    softDeleteStock,
    hardDeleteStock,
    reactivateStock,
    isAdjusting,
    isUsing
  } = useStocks(null)

  const { data: globalStats } = useStockStats(enableSecondaryData)

  useEffect(() => {
    const schedule = window.requestIdleCallback
      ? window.requestIdleCallback(() => setEnableSecondaryData(true), { timeout: 1500 })
      : window.setTimeout(() => setEnableSecondaryData(true), 600)

    return () => {
      if (typeof schedule === 'number') {
        window.clearTimeout(schedule)
      } else if (window.cancelIdleCallback) {
        window.cancelIdleCallback(schedule)
      }
    }
  }, [])

  // Computed data
  const activeStocks = useMemo(() => {
    return stocks || []
  }, [stocks])

  const handleSearch = useCallback((value: string) => {
    setSearchTerm(value)
  }, [])

  const handleFilterChange = useCallback((field: keyof StockFilter, value: string | number | undefined) => {
    setFilters(prev => ({ ...prev, [field]: value, page: 1 }))
  }, [])

  const handleAdd = useCallback(() => {
    setEditingStock(null)
    setIsFormModalVisible(true)
  }, [])

  const handleEdit = useCallback((stock: any) => {
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
        router.visit(`/stock/products/${stock.id}`)
    }
  }, [])

  const handleUse = useCallback((stock: any) => {
    if (stock.batches && stock.batches.length > 0) {
        setSelectedStock(stock.batches[0])
        setIsUseModalVisible(true)
    } else {
        router.visit(`/stock/products/${stock.id}`)
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
      
      <StockStats stats={globalStats} />
      
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
          onDelete={deleteStock} 
          onSoftDelete={softDeleteStock}
          onHardDelete={hardDeleteStock}
          onReactivate={reactivateStock}
          onAdjust={handleAdjust}
          onUse={handleUse}
          onViewHistory={handleViewHistory}
          pagination={{
            current: meta?.current_page || filters.page,
            pageSize: meta?.per_page || filters.per_page,
            total: meta?.total || 0,
            onChange: (page, pageSize) => {
                setFilters(prev => ({ ...prev, page, per_page: pageSize }))
            }
          }}
        />
      </Card>

      <Suspense fallback={null}>
        {(isFormModalVisible || isAdjustModalVisible || isUseModalVisible) && (
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
        )}

        {isHistoryModalVisible && (
          <StockHistoryModal 
            visible={isHistoryModalVisible}
            stock={selectedStock as any}
            onClose={() => setIsHistoryModalVisible(false)}
          />
        )}

        {isScannerVisible && (
          <BarcodeScannerModal 
            visible={isScannerVisible}
            onClose={() => setIsScannerVisible(false)}
          />
        )}
      </Suspense>
    </div>
  )
}
