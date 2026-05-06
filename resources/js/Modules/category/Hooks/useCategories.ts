import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { message } from 'antd'
import { categoryApi } from '../Services/categoryApi'
import { CreateCategoryRequest, UpdateCategoryRequest } from '../Types/category.types'

export const useCategories = (enabled = true) => {
  const queryClient = useQueryClient()

  const {
    data: categories,
    isLoading,
    error
  } = useQuery({
    queryKey: ['categories'],
    queryFn: categoryApi.getAll,
    select: (data) => data.data,
    enabled,
  })

  const createMutation = useMutation({
    mutationFn: categoryApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      message.success('Kategori başarıyla oluşturuldu!')
    }
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateCategoryRequest }) =>
      categoryApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      message.success('Kategori güncellendi!')
    }
  })

  const deleteMutation = useMutation({
    mutationFn: categoryApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      message.success('Kategori silindi!')
    }
  })

  return {
    categories,
    isLoading,
    error,
    createCategory: createMutation.mutateAsync,
    updateCategory: updateMutation.mutateAsync,
    deleteCategory: deleteMutation.mutateAsync,
    isCreating: createMutation.isPending,
    isUpdating: updateMutation.isPending,
    isDeleting: deleteMutation.isPending
  }
}
