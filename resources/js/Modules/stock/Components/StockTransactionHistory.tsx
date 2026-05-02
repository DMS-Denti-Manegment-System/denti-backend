import React, { useState, useMemo } from 'react'
import { 
  Card, 
  Table, 
  Tag, 
  Space, 
  Button, 
  DatePicker, 
  Select, 
  Tooltip,
  Badge
} from 'antd'

import { 
  ReloadOutlined
} from '@ant-design/icons'
import dayjs from 'dayjs'
import { useStockTransactions } from '../Hooks/useStocks'

const { RangePicker } = DatePicker
const { Option } = Select

interface StockTransactionHistoryProps {
  stockId: number
  productName?: string
}

export const StockTransactionHistory: React.FC<StockTransactionHistoryProps> = ({ 
  stockId, 
  productName 
}) => {
  const [filters, setFilters] = useState({
    type: '',
    date_from: '',
    date_to: '',
    per_page: 50
  })

  const { 
    data: transactions, 
    isLoading, 
    refetch 
  } = useStockTransactions(stockId, filters)

  const transactionTypes = useMemo(() => [
    { value: 'purchase', label: 'Satın Alma', color: 'green' },
    { value: 'usage', label: 'Kullanım', color: 'red' },
    { value: 'adjustment_increase', label: 'Stok Artışı', color: 'blue' },
    { value: 'adjustment_decrease', label: 'Stok Azalışı', color: 'orange' },
    { value: 'transfer_in', label: 'Transfer Giriş', color: 'cyan' },
    { value: 'transfer_out', label: 'Transfer Çıkış', color: 'purple' },
    { value: 'expired', label: 'Son Kullanma Tarihi', color: 'volcano' },
    { value: 'damaged', label: 'Hasarlı', color: 'magenta' },
    { value: 'returned', label: 'İade', color: 'geekblue' }
  ], [])

  const columns = useMemo(() => [
    {
      title: 'İşlem No',
      dataIndex: 'transaction_number',
      width: 140,
      render: (text: string) => (
        <Tooltip title={text}>
          <span style={{ fontFamily: 'monospace', fontSize: '12px' }}>
            {text}
          </span>
        </Tooltip>
      )
    },
    {
      title: 'Tür',
      dataIndex: 'type',
      width: 120,
      render: (type: string) => {
        const typeConfig = transactionTypes.find(t => t.value === type)
        return (
          <Tag color={typeConfig?.color || 'default'}>
            {typeConfig?.label || type}
          </Tag>
        )
      }
    },
    {
      title: 'Miktar',
      dataIndex: 'quantity',
      width: 100,
      render: (quantity: number, record: any) => {
        const isPositive = ['purchase', 'adjustment_increase', 'transfer_in', 'returned'].includes(record.type)
        return (
          <span style={{ 
            color: isPositive ? '#52c41a' : '#ff4d4f',
            fontWeight: 'bold'
          }}>
            {isPositive ? '+' : '-'}{quantity}
            {record.is_sub_unit && ' (alt birim)'}
          </span>
        )
      }
    },
    {
      title: 'Önceki Stok',
      dataIndex: 'previous_stock',
      width: 100,
      render: (value: number) => <span>{value}</span>
    },
    {
      title: 'Yeni Stok',
      dataIndex: 'new_stock',
      width: 100,
      render: (value: number) => <span style={{ fontWeight: 'bold' }}>{value}</span>
    },
    {
      title: 'Tarih',
      dataIndex: 'transaction_date',
      width: 140,
      render: (date: string) => dayjs(date).format('DD/MM/YYYY HH:mm')
    },
    {
      title: 'Kullanıcı',
      dataIndex: ['user', 'name'],
      width: 120,
      render: (name: string) => name || '-'
    },
    {
      title: 'Notlar',
      dataIndex: 'notes',
      ellipsis: true,
      render: (notes: string) => (
        <Tooltip title={notes}>
          <span>{notes || '-'}</span>
        </Tooltip>
      )
    }
  ], [transactionTypes])

  const handleFilterChange = (key: string, value: any) => {
    setFilters(prev => ({ ...prev, [key]: value }))
  }

  const handleDateRangeChange = (dates: any) => {
    if (dates && dates.length === 2) {
      setFilters(prev => ({
        ...prev,
        date_from: dates[0].format('YYYY-MM-DD'),
        date_to: dates[1].format('YYYY-MM-DD')
      }))
    } else {
      setFilters(prev => ({
        ...prev,
        date_from: '',
        date_to: ''
      }))
    }
  }

  const handleRefresh = () => {
    refetch()
  }

  return (
    <Card 
      title={
        <Space>
          <span>İşlem Geçmişi</span>
          {productName && <Tag color="blue">{productName}</Tag>}
          <Badge count={transactions?.data?.length || 0} showZero />
        </Space>
      }
      extra={
        <Space>
          <RangePicker 
            placeholder={['Başlangıç', 'Bitiş']}
            onChange={handleDateRangeChange}
            style={{ width: 200 }}
          />
          <Select
            placeholder="İşlem Türü"
            allowClear
            style={{ width: 150 }}
            onChange={(value) => handleFilterChange('type', value)}
            value={filters.type || undefined}
          >
            {transactionTypes.map(type => (
              <Option key={type.value} value={type.value}>
                {type.label}
              </Option>
            ))}
          </Select>
          <Button 
            icon={<ReloadOutlined />} 
            onClick={handleRefresh}
            loading={isLoading}
          >
            Yenile
          </Button>
        </Space>
      }
      size="small"
    >
      <Table
        columns={columns}
        dataSource={transactions?.data || []}
        loading={isLoading}
        rowKey="id"
        pagination={{
          current: transactions?.current_page || 1,
          pageSize: transactions?.per_page || 50,
          total: transactions?.total || 0,
          showSizeChanger: true,
          showQuickJumper: true,
          showTotal: (total: number, range: [number, number]) => 
            `${range[0]}-${range[1]} / ${total} işlem`,
          onChange: (_unusedPage: number, pageSize: number) => {
            handleFilterChange('per_page', pageSize)
          }
        }}
        scroll={{ x: 800 }}
        size="small"
      />
    </Card>
  )
}
