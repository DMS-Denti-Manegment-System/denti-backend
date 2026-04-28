// src/modules/stock/Hooks/useStocks.ts

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { message } from 'antd'
import { stockApi } from '../Services/stockApi'
import { 
  UpdateStockRequest, 
  StockAdjustmentRequest,
  StockUsageRequest,
  StockFilter 
} from '../Types/stock.types'

const STALE_TIME = 300000 // 5 dakika

// Ortak hata işleyici
const handleError = (defaultMsg: string) => (error: any) => {
  message.error(error.response?.data?.message || defaultMsg)
}

export const useProducts = (filters?: any) => {
  const queryClient = useQueryClient()

  const {
    data: products,
    isLoading,
    error,
    refetch
  } = useQuery({
    queryKey: ['products', filters],
    queryFn: () => stockApi.getProducts(filters),
    select: (data) => data.data,
    staleTime: STALE_TIME,
  })

  const createMutation = useMutation({
    mutationFn: stockApi.createProduct,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] })
      queryClient.invalidateQueries({ queryKey: ['stocks'] })
      message.success('Ürün başarıyla oluşturuldu!')
    },
    onError: handleError('Ürün oluşturulurken hata oluştu!')
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      stockApi.updateProduct(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] })
      message.success('Ürün başarıyla güncellendi!')
    },
    onError: handleError('Ürün güncellenirken hata oluştu!')
  })

  const deleteMutation = useMutation({
    mutationFn: stockApi.deleteProduct,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] })
      message.success('Ürün başarıyla silindi!')
    },
    onError: handleError('Ürün silinirken hata oluştu!')
  })

  return {
    products,
    isLoading,
    error,
    refetch,
    createProduct: createMutation.mutateAsync,
    updateProduct: updateMutation.mutateAsync,
    deleteProduct: deleteMutation.mutateAsync,
    isCreating: createMutation.isPending,
    isUpdating: updateMutation.isPending,
    isDeleting: deleteMutation.isPending
  }
}

export const useProductDetail = (id: number) => {
  const queryClient = useQueryClient()

  const {
    data: product,
    isLoading,
    refetch
  } = useQuery({
    queryKey: ['products', id],
    queryFn: () => stockApi.getProductById(id),
    select: (data) => data.data,
    enabled: !!id,
    staleTime: STALE_TIME,
  })

  const addBatchMutation = useMutation({
    mutationFn: stockApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products', id] })
      queryClient.invalidateQueries({ queryKey: ['stock-stats'] })
      queryClient.invalidateQueries({ queryKey: ['stocks', id, 'transactions'] })
      message.success('Stok girişi başarıyla yapıldı!')
    },
    onError: handleError('Stok girişi yapılırken hata oluştu!')
  })

  return {
    product,
    isLoading,
    refetch,
    addBatch: addBatchMutation.mutateAsync,
    isAddingBatch: addBatchMutation.isPending
  }
}

export const useStocks = (filters?: StockFilter) => {
  const queryClient = useQueryClient()

  const {
    data: stocks,
    isLoading,
    error,
    refetch
  } = useQuery({
    queryKey: ['stocks', filters],
    queryFn: () => stockApi.getAll(filters),
    select: (data) => data.data,
    staleTime: STALE_TIME,
  })

  const createMutation = useMutation({
    mutationFn: stockApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stocks'] })
      queryClient.invalidateQueries({ queryKey: ['stock-stats'] })
      queryClient.invalidateQueries({ queryKey: ['stock-levels'] })
      message.success('Stok başarıyla oluşturuldu!')
    },
    onError: handleError('Stok oluşturulurken hata oluştu!')
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateStockRequest }) =>
      stockApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stocks'] })
      queryClient.invalidateQueries({ queryKey: ['stock-stats'] })
      queryClient.invalidateQueries({ queryKey: ['stock-levels'] })
      message.success('Stok başarıyla güncellendi!')
    },
    onError: handleError('Stok güncellenirken hata oluştu!')
  })

  const deleteMutation = useMutation({
    mutationFn: stockApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stocks'] })
      queryClient.invalidateQueries({ queryKey: ['stock-stats'] })
      queryClient.invalidateQueries({ queryKey: ['stock-levels'] })
      message.success('Stok başarıyla silindi!')
    },
    onError: handleError('Stok silinirken hata oluştu!')
  })

  const softDeleteMutation = useMutation({
    mutationFn: stockApi.softDelete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stocks'] })
      queryClient.invalidateQueries({ queryKey: ['stock-stats'] })
      message.success('Stok pasif duruma getirildi!')
    },
    onError: handleError('Stok pasif yapılırken hata oluştu!')
  })

  const hardDeleteMutation = useMutation({
    mutationFn: stockApi.hardDelete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stocks'] })
      queryClient.invalidateQueries({ queryKey: ['stock-stats'] })
      queryClient.invalidateQueries({ queryKey: ['stock-levels'] })
      message.success('Stok kalıcı olarak silindi!')
    },
    onError: handleError('Stok silinirken hata oluştu!')
  })

  const reactivateMutation = useMutation({
    mutationFn: stockApi.reactivate,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stocks'] })
      queryClient.invalidateQueries({ queryKey: ['stock-stats'] })
      message.success('Stok tekrar aktif edildi!')
    },
    onError: handleError('Stok aktif edilirken hata oluştu!')
  })

  const adjustStockMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: StockAdjustmentRequest }) =>
      stockApi.adjustStock(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stocks'] })
      queryClient.invalidateQueries({ queryKey: ['stock-stats'] })
      queryClient.invalidateQueries({ queryKey: ['stock-levels'] })
      queryClient.invalidateQueries({ queryKey: ['stocks', 'transactions'] })
      message.success('Stok miktarı başarıyla ayarlandı!')
    },
    onError: handleError('Stok ayarlanırken hata oluştu!')
  })

  const useStockMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: StockUsageRequest }) =>
      stockApi.useStock(id, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['stocks'] })
      queryClient.invalidateQueries({ queryKey: ['stock-stats'] })
      queryClient.invalidateQueries({ queryKey: ['stock-levels'] })
      // Invalidate specific product transactions if we have the product_id
      // For now, invalidate all transactions to be safe
      queryClient.invalidateQueries({ queryKey: ['stocks', 'transactions'] })
      message.success('Stok kullanımı başarıyla kaydedildi!')
    },
    onError: handleError('Stok kullanımı kaydedilirken hata oluştu!')
  })

  return {
    stocks,
    isLoading,
    error,
    refetch,
    createStock: createMutation.mutateAsync,
    updateStock: updateMutation.mutateAsync,
    deleteStock: deleteMutation.mutateAsync,
    softDeleteStock: softDeleteMutation.mutateAsync,
    hardDeleteStock: hardDeleteMutation.mutateAsync,
    reactivateStock: reactivateMutation.mutateAsync,
    adjustStock: adjustStockMutation.mutateAsync,
    useStock: useStockMutation.mutateAsync,
    
    isCreating: createMutation.isPending,
    isUpdating: updateMutation.isPending,
    isDeleting: deleteMutation.isPending,
    isSoftDeleting: softDeleteMutation.isPending,
    isHardDeleting: hardDeleteMutation.isPending,
    isReactivating: reactivateMutation.isPending,
    isAdjusting: adjustStockMutation.isPending,
    isUsing: useStockMutation.isPending
  }
}

// Tekil stok için hook
export const useStock = (id: number) => {
  return useQuery({
    queryKey: ['stocks', id],
    queryFn: () => stockApi.getById(id),
    select: (data) => data.data,
    enabled: !!id,
    staleTime: STALE_TIME,
  })
}

// Kritik ve azalan stoklar için hook'lar
export const useLowStockItems = () => {
  return useQuery({
    queryKey: ['stock-levels', 'low'],
    queryFn: stockApi.getLowStockItems,
    select: (data) => data.data,
    staleTime: STALE_TIME,
  })
}

export const useCriticalStockItems = () => {
  return useQuery({
    queryKey: ['stock-levels', 'critical'],
    queryFn: stockApi.getCriticalStockItems,
    select: (data) => data.data,
    staleTime: STALE_TIME,
  })
}

export const useExpiringItems = (days?: number) => {
  return useQuery({
    queryKey: ['stock-levels', 'expiring', days],
    queryFn: () => stockApi.getExpiringItems(days),
    select: (data) => data.data,
    staleTime: STALE_TIME,
  })
}

// İstatistikler için hook
export const useStockStats = () => {
  return useQuery({
    queryKey: ['stock-stats'],
    queryFn: stockApi.getStats,
    select: (data) => data.data,
    staleTime: STALE_TIME,
  })
}

// Stok hareketleri için hook
export const useStockTransactions = (id: number) => {
  return useQuery({
    queryKey: ['stocks', id, 'transactions'],
    queryFn: () => stockApi.getProductTransactions(id),
    select: (data) => data.data,
    enabled: !!id,
    staleTime: 60000,
  })
}