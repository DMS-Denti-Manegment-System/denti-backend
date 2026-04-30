// src/modules/stock/Hooks/useStocks.ts

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { message } from 'antd'
import { stockApi } from '../Services/stockApi'
import { STOCK_QUERY_KEYS } from '../Constants/queryKeys'
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
    queryKey: [STOCK_QUERY_KEYS.PRODUCTS, filters],
    queryFn: () => stockApi.getProducts(filters),
    select: (response: any) => response.data?.data || response.data,
    staleTime: STALE_TIME,
  })

  const createMutation = useMutation({
    mutationFn: stockApi.createProduct,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.PRODUCTS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      message.success('Ürün başarıyla oluşturuldu!')
    },
    onError: handleError('Ürün oluşturulurken hata oluştu!')
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      stockApi.updateProduct(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.PRODUCTS] })
      message.success('Ürün başarıyla güncellendi!')
    },
    onError: handleError('Ürün güncellenirken hata oluştu!')
  })

  const deleteMutation = useMutation({
    mutationFn: stockApi.deleteProduct,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.PRODUCTS] })
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
    queryKey: [STOCK_QUERY_KEYS.PRODUCTS, id],
    queryFn: () => stockApi.getProductById(id),
    select: (response: any) => response.data?.data || response.data,
    enabled: !!id,
    staleTime: STALE_TIME,
  })

  const addBatchMutation = useMutation({
    mutationFn: stockApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.PRODUCTS, id] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS, id, STOCK_QUERY_KEYS.TRANSACTIONS] })
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
    queryKey: [STOCK_QUERY_KEYS.STOCKS, filters],
    queryFn: () => stockApi.getAll(filters),
    select: (response: any) => response.data?.data || response.data,
    staleTime: STALE_TIME,
  })

  const createMutation = useMutation({
    mutationFn: stockApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_LEVELS] })
      message.success('Stok başarıyla oluşturuldu!')
    },
    onError: handleError('Stok oluşturulurken hata oluştu!')
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateStockRequest }) =>
      stockApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_LEVELS] })
      message.success('Stok başarıyla güncellendi!')
    },
    onError: handleError('Stok güncellenirken hata oluştu!')
  })

  const deleteMutation = useMutation({
    mutationFn: stockApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_LEVELS] })
      message.success('Stok başarıyla silindi!')
    },
    onError: handleError('Stok silinirken hata oluştu!')
  })

  const softDeleteMutation = useMutation({
    mutationFn: stockApi.softDelete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      message.success('Stok pasif duruma getirildi!')
    },
    onError: handleError('Stok pasif yapılırken hata oluştu!')
  })

  const hardDeleteMutation = useMutation({
    mutationFn: stockApi.hardDelete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_LEVELS] })
      message.success('Stok kalıcı olarak silindi!')
    },
    onError: handleError('Stok silinirken hata oluştu!')
  })

  const reactivateMutation = useMutation({
    mutationFn: stockApi.reactivate,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      message.success('Stok tekrar aktif edildi!')
    },
    onError: handleError('Stok aktif edilirken hata oluştu!')
  })

  const adjustStockMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: StockAdjustmentRequest }) =>
      stockApi.adjustStock(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_LEVELS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS, STOCK_QUERY_KEYS.TRANSACTIONS] })
      message.success('Stok miktarı başarıyla ayarlandı!')
    },
    onError: handleError('Stok ayarlanırken hata oluştu!')
  })

  const useStockMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: StockUsageRequest }) =>
      stockApi.useStock(id, data),
    onMutate: async ({ id, data }) => {
      // 🛡️ Cancel outgoing refetches to avoid race conditions
      await queryClient.cancelQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      await queryClient.cancelQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS, id] })

      // Snapshot the previous value
      const previousStocks = queryClient.getQueryData([STOCK_QUERY_KEYS.STOCKS])
      const previousStock = queryClient.getQueryData([STOCK_QUERY_KEYS.STOCKS, id])

      return { previousStocks, previousStock }
    },
    onSuccess: (_, variables) => {
      // Granular invalidation
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS, variables.id] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS, variables.id, STOCK_QUERY_KEYS.TRANSACTIONS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      
      message.success('Stok kullanımı başarıyla kaydedildi!')
    },
    onError: (error, variables, context) => {
      // 🛡️ Rollback on error
      if (context?.previousStocks) {
        queryClient.setQueryData([STOCK_QUERY_KEYS.STOCKS], context.previousStocks)
      }
      if (context?.previousStock) {
        queryClient.setQueryData([STOCK_QUERY_KEYS.STOCKS, variables.id], context.previousStock)
      }
      handleError('Stok kullanımı kaydedilirken hata oluştu!')(error)
    },
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

export const useTransactionActions = () => {
  const queryClient = useQueryClient()

  const reverseMutation = useMutation({
    mutationFn: stockApi.reverseTransaction,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCKS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.PRODUCTS] })
      queryClient.invalidateQueries({ queryKey: [STOCK_QUERY_KEYS.STOCK_STATS] })
      message.success('İşlem başarıyla geri alındı!')
    },
    onError: handleError('İşlem geri alınırken hata oluştu!')
  })

  return {
    reverseTransaction: reverseMutation.mutateAsync,
    isReversing: reverseMutation.isPending
  }
}

// Tekil stok için hook
export const useStock = (id: number) => {
  return useQuery({
    queryKey: [STOCK_QUERY_KEYS.STOCKS, id],
    queryFn: () => stockApi.getById(id),
    select: (response: any) => response.data?.data || response.data,
    enabled: !!id,
    staleTime: STALE_TIME,
  })
}

// Kritik ve azalan stoklar için hook'lar
export const useLowStockItems = () => {
  return useQuery({
    queryKey: [STOCK_QUERY_KEYS.STOCK_LEVELS, 'low'],
    queryFn: stockApi.getLowStockItems,
    select: (response: any) => response.data?.data || response.data,
    staleTime: STALE_TIME,
  })
}

export const useCriticalStockItems = () => {
  return useQuery({
    queryKey: [STOCK_QUERY_KEYS.STOCK_LEVELS, 'critical'],
    queryFn: stockApi.getCriticalStockItems,
    select: (response: any) => response.data?.data || response.data,
    staleTime: STALE_TIME,
  })
}

export const useExpiringItems = (days?: number) => {
  return useQuery({
    queryKey: [STOCK_QUERY_KEYS.STOCK_LEVELS, 'expiring', days],
    queryFn: () => stockApi.getExpiringItems(days),
    select: (response: any) => response.data?.data || response.data,
    staleTime: STALE_TIME,
  })
}

// İstatistikler için hook
export const useStockStats = () => {
  return useQuery({
    queryKey: [STOCK_QUERY_KEYS.STOCK_STATS],
    queryFn: stockApi.getStats,
    select: (response: any) => response.data?.data || response.data,
    staleTime: STALE_TIME,
  })
}

// Stok hareketleri için hook
export const useStockTransactions = (id: number) => {
  return useQuery({
    queryKey: [STOCK_QUERY_KEYS.STOCKS, id, STOCK_QUERY_KEYS.TRANSACTIONS],
    queryFn: () => stockApi.getProductTransactions(id),
    select: (response: any) => response.data?.data || response.data,
    enabled: !!id,
    staleTime: 60000,
  })
}
