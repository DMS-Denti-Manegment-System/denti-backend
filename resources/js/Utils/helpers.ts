import dayjs from 'dayjs'
import 'dayjs/locale/tr'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.locale('tr')
dayjs.extend(relativeTime)

export const formatDate = (date: string | Date, format = 'DD.MM.YYYY HH:mm') => {
  return dayjs(date).format(format)
}

export const formatRelativeTime = (date: string | Date) => {
  return dayjs(date).fromNow()
}

export const debounce = <T extends (...args: unknown[]) => unknown>(
  func: T,
  delay: number
): ((...args: Parameters<T>) => void) => {
  let timeoutId: ReturnType<typeof setTimeout>
  return (...args: Parameters<T>) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => func(...args), delay)
  }
}

export const getColorFromString = (str: string): string => {
  const colors = [
    '#1890ff', '#52c41a', '#fa8c16', '#722ed1', '#eb2f96',
    '#13c2c2', '#faad14', '#f5222d', '#2f54eb', '#52c41a'
  ]
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash)
  }
  return colors[Math.abs(hash) % colors.length]
}

/**
 * Stok miktarını formatlar.
 * Örn: 10 Kutu + 2 Adet veya sadece 10 Kutu
 */
export const formatStock = (
  current_stock: number,
  unit: string,
  has_sub_unit?: boolean,
  current_sub_stock?: number,
  sub_unit_name?: string
): string => {
  const mainPart = `${current_stock} ${unit}`
  
  if (has_sub_unit && current_sub_stock && current_sub_stock > 0 && sub_unit_name) {
    return `${mainPart} + ${current_sub_stock} ${sub_unit_name}`
  }
  
  return mainPart
}

/**
 * Para birimini formatlar.
 * Guard: null veya undefined gelirse "0,00" döner.
 */
export const formatCurrency = (value?: number | string | null, currency = '₺'): string => {
  if (value === undefined || value === null || value === '') {
    return `0,00 ${currency}`
  }
  
  const numericValue = typeof value === 'string' ? parseFloat(value) : value
  
  if (isNaN(numericValue)) {
    return `0,00 ${currency}`
  }

  return new Intl.NumberFormat('tr-TR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numericValue) + ` ${currency}`
}