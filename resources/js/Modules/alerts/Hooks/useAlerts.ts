// src/modules/alerts/Hooks/useAlerts.ts

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { message, notification } from 'antd'
import { alertApi } from '../Services/alertApi'
import {
  ResolveAlertRequest,
  AlertFilters
} from '../Types/alert.types'

export const useAlerts = (filters?: AlertFilters) => {
  const queryClient = useQueryClient()

  const {
    data: alerts,
    isLoading,
    error,
    refetch
  } = useQuery({
    queryKey: ['alerts', filters],
    queryFn: () => alertApi.getAll(filters),
    select: (data) => {
      if (!data.data) return [];
      return data.data.map(alert => ({
        ...alert,
        severity: alert.severity || (
          alert.type === 'critical_stock' || alert.type === 'expired' ? 'critical' : 
          alert.type === 'low_stock' || alert.type === 'near_expiry' ? 'medium' : 'low'
        )
      }));
    },
    enabled: true,
    staleTime: 60 * 1000, // 1 dakika
    refetchOnWindowFocus: true,
    refetchOnMount: true,
    refetchInterval: (query) => {
      // Kritik uyarı varsa 45s, yoksa 3 dk — sık polling API ve DB yükünü artırıyordu
      const data = query.state.data?.data as any[] | undefined
      const hasCritical = data?.some((a: any) => a.severity === 'critical' || a.type === 'critical_stock' || a.type === 'expired')
      return hasCritical ? 45000 : 180000
    },
    refetchIntervalInBackground: false,
    retry: false,
  })

  const createMutation = useMutation({
    mutationFn: alertApi.create,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['alerts'] })
      queryClient.invalidateQueries({ queryKey: ['alertStats'] })
      
      // Kritik uyarılar için browser notification
      if (data.data.severity === 'critical') {
        notification.error({
          message: 'Kritik Uyarı!',
          description: data.data.message,
          duration: 0, // Kalıcı notification
          placement: 'topRight'
        })
      }
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'Uyarı oluşturulurken hata oluştu!')
    }
  })

  const resolveMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: ResolveAlertRequest }) =>
      alertApi.resolve(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['alerts'] })
      queryClient.invalidateQueries({ queryKey: ['alertStats'] })
      message.success('Uyarı çözümlendi!')
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'Uyarı çözümlenirken hata oluştu!')
    }
  })

  const dismissMutation = useMutation({
    mutationFn: alertApi.dismiss,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['alerts'] })
      queryClient.invalidateQueries({ queryKey: ['alertStats'] })
      message.success('Uyarı yok sayıldı.')
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'Uyarı yok sayılırken hata oluştu!')
    }
  })

  const deleteMutation = useMutation({
    mutationFn: alertApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['alerts'] })
      queryClient.invalidateQueries({ queryKey: ['alertStats'] })
      message.success('Uyarı silindi.')
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'Uyarı silinirken hata oluştu!')
    }
  })

  const bulkResolveMutation = useMutation({
    mutationFn: ({ ids, data }: { ids: number[]; data: ResolveAlertRequest }) =>
      alertApi.bulkResolve(ids, data),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['alerts'] })
      queryClient.invalidateQueries({ queryKey: ['alertStats'] })
      message.success(`${data.data.length} uyarı çözümlendi!`)
    }
  })

  const bulkDismissMutation = useMutation({
    mutationFn: alertApi.bulkDismiss,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['alerts'] })
      queryClient.invalidateQueries({ queryKey: ['alertStats'] })
      message.success(`${data.data.length} uyarı yok sayıldı!`)
    }
  })

  return {
    alerts,
    isLoading,
    error,
    refetch,
    createAlert: createMutation.mutateAsync,
    resolveAlert: resolveMutation.mutateAsync,
    dismissAlert: dismissMutation.mutateAsync,
    deleteAlert: deleteMutation.mutateAsync,
    bulkResolveAlerts: bulkResolveMutation.mutateAsync,
    bulkDismissAlerts: bulkDismissMutation.mutateAsync,
    isCreating: createMutation.isPending,
    isResolving: resolveMutation.isPending,
    isDismissing: dismissMutation.isPending,
    isDeleting: deleteMutation.isPending,
    isBulkProcessing: bulkResolveMutation.isPending || bulkDismissMutation.isPending,
    syncAlerts: async (clinicId?: number) => {
      try {
        const response = await alertApi.sync(clinicId);
        queryClient.invalidateQueries({ queryKey: ['alerts'] });
        queryClient.invalidateQueries({ queryKey: ['alertStats'] });
        message.success(`${response.data.count} stok tarandı ve uyarılar güncellendi.`);
        return response;
      } catch (error) {
        message.error('Uyarı senkronizasyonu başarısız oldu.');
        throw error;
      }
    }
  }
}

// Aktif uyarılar için hook
export const useActiveAlerts = (clinicId?: number) => {
  return useQuery({
    queryKey: ['alerts', 'active', clinicId],
    queryFn: () => alertApi.getActive(clinicId),
    select: (data) => {
      if (!data.data) return [];
      return data.data.map(alert => ({
        ...alert,
        severity: alert.severity || (
          alert.type === 'critical_stock' || alert.type === 'expired' ? 'critical' : 
          alert.type === 'low_stock' || alert.type === 'near_expiry' ? 'medium' : 'low'
        )
      }));
    },
    enabled: true,
    staleTime: 1 * 60 * 1000, // 1 dakika
    refetchOnWindowFocus: true,
    refetchOnMount: true,
    refetchInterval: 120000, // 2 dakikada bir (önceden 30s — gereksiz yük)
    refetchIntervalInBackground: false,
    retry: false,
  })
}

// Bekleyen uyarı sayısı için hook - EN ÖNEMLİSİ!
export const usePendingAlertCount = (clinicId?: number, enabled = true) => {
  return useQuery({
    queryKey: ['alerts', 'pending', 'count', clinicId],
    queryFn: () => alertApi.getPendingCount(clinicId),
    select: (data) => data.data.count,
    enabled,
    staleTime: 5 * 60 * 1000, // 5 dakika cache'ten oku
    refetchOnWindowFocus: false, // Sayfaya dönünce tekrar çekme
    refetchOnMount: false, // Modül değiştirince tekrar çekme
    refetchInterval: 300000, // 5 dakikada bir (badge sayısı için yeterli)
    refetchIntervalInBackground: false,
    retry: false,
  })
}

// Uyarı istatistikleri için hook
export const useAlertStats = (clinicId?: number) => {
  return useQuery({
    queryKey: ['alertStats', clinicId],
    queryFn: () => alertApi.getStats(clinicId),
    select: (data) => data.data,
    enabled: true,
    staleTime: 5 * 60 * 1000, // 5 dakika
    refetchOnWindowFocus: true,
    refetchOnMount: true,
    refetchInterval: 180000, // 3 dakikada bir
    refetchIntervalInBackground: false,
    retry: false,
  })
}

// Uyarı ayarları için hook
export const useAlertSettings = () => {
  const queryClient = useQueryClient()

  const { data: settings, isLoading } = useQuery({
    queryKey: ['alertSettings'],
    queryFn: alertApi.getSettings,
    select: (data) => data.data,
    enabled: true,
    staleTime: 10 * 60 * 1000, // 10 dakika
    refetchOnWindowFocus: true,
    refetchOnMount: true,
    retry: false,
  })

  const updateMutation = useMutation({
    mutationFn: alertApi.updateSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['alertSettings'] })
      message.success('Uyarı ayarları güncellendi!')
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      message.error(error.response?.data?.message || 'Ayarlar güncellenirken hata oluştu!')
    }
  })

  return {
    settings,
    isLoading,
    updateSettings: updateMutation.mutateAsync,
    isUpdating: updateMutation.isPending
  }
}
