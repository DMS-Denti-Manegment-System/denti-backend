import React, { useMemo } from 'react'
import { Modal, Table, Tag, Typography, Empty, Skeleton, Row, Col, Card, Statistic } from 'antd'
import { 
  LineChart, 
  Line, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip as ChartTooltip, 
  ResponsiveContainer,
  AreaChart,
  Area
} from 'recharts'
import { Stock } from '../Types/stock.types'
import { useStockTransactions, useTransactionActions } from '../Hooks/useStocks'
import { UndoOutlined } from '@ant-design/icons'
import dayjs from 'dayjs'

const { Title, Text } = Typography

interface StockHistoryModalProps {
  visible: boolean
  stock: Stock | null
  onClose: () => void
}

export const StockHistoryModal: React.FC<StockHistoryModalProps> = ({
  visible,
  stock,
  onClose
}) => {
  const { data: transactions, isLoading } = useStockTransactions(stock?.id || 0)

  const chartData = useMemo(() => {
    if (!transactions) return []
    // Son 15 işlemi alalım ve kronolojik sıralayalım
    return [...transactions]
      .slice(0, 15)
      .reverse()
      .map(t => ({
        date: dayjs(t.transaction_date).format('DD/MM HH:mm'),
        amount: t.new_stock,
        type: t.type_text
      }))
  }, [transactions])

  const columns = [
    {
      title: 'Tarih',
      dataIndex: 'transaction_date',
      key: 'date',
      render: (date: string) => dayjs(date).format('DD/MM/YYYY HH:mm'),
      width: 150
    },
    {
      title: 'İşlem',
      dataIndex: 'type_text',
      key: 'type',
      render: (text: string, record: any) => {
        let color = 'blue'
        if (record.type === 'usage' || record.type === 'transfer_out') color = 'orange'
        if (record.type === 'purchase' || record.type === 'transfer_in') color = 'green'
        if (record.type === 'adjustment') color = 'purple'
        return <Tag color={color}>{text}</Tag>
      }
    },
    {
      title: 'Miktar',
      dataIndex: 'quantity',
      key: 'quantity',
      render: (qty: number) => <Text strong>{qty}</Text>
    },
    {
      title: 'Yeni Stok',
      dataIndex: 'new_stock',
      key: 'new_stock',
    },
    {
      title: 'İşlemi Yapan',
      dataIndex: 'performed_by',
      key: 'user',
    },
    {
      title: 'Aksiyon',
      key: 'actions',
      render: (_: any, record: any) => {
        const canReverse = ['usage', 'adjustment', 'adjustment_increase', 'adjustment_decrease', 'damaged', 'expired'].includes(record.type)
        return canReverse ? (
          <Tag 
            icon={<UndoOutlined />} 
            color="red" 
            style={{ cursor: 'pointer' }}
            onClick={() => {
              Modal.confirm({
                title: 'İşlemi Geri Al',
                content: 'Bu işlemi geri almak istediğinize emin misiniz? Stok miktarı eski haline dönecektir.',
                onOk: () => reverseTransaction(record.id)
              })
            }}
          >
            Geri Al
          </Tag>
        ) : null
      }
    }
  ]

  const { reverseTransaction } = useTransactionActions()

  const stats = useMemo(() => {
    if (!transactions) return { added: 0, used: 0 }
    return transactions.reduce((acc, t) => {
      if (t.type === 'purchase' || (t.type === 'adjustment' && t.quantity > 0)) {
        acc.added += t.quantity
      } else if (t.type === 'usage' || (t.type === 'adjustment' && t.quantity < 0)) {
        acc.used += Math.abs(t.quantity)
      }
      return acc
    }, { added: 0, used: 0 })
  }, [transactions])

  return (
    <Modal
      title={
        <div style={{ paddingBottom: 8 }}>
          <Title level={4} style={{ margin: 0 }}>{stock?.name} - Stok Hareketleri</Title>
          <Text type="secondary">{stock?.brand} | {stock?.category}</Text>
        </div>
      }
      open={visible}
      onCancel={onClose}
      footer={null}
      width={900}
      destroyOnClose
    >
      {isLoading ? (
        <Skeleton active paragraph={{ rows: 10 }} />
      ) : transactions && transactions.length > 0 ? (
        <Row gutter={[16, 16]}>
          <Col span={24}>
            <Row gutter={16}>
              <Col span={8}>
                <Card size="small" styles={{ body: { textAlign: 'center' } }}>
                  <Statistic title="Mevcut Stok" value={stock?.current_stock} suffix={stock?.unit} />
                </Card>
              </Col>
              <Col span={8}>
                <Card size="small" styles={{ body: { textAlign: 'center' } }}>
                  <Statistic 
                    title="Toplam Giriş" 
                    value={stats.added} 
                    suffix={stock?.unit}
                    valueStyle={{ color: '#3f8600' }}
                  />
                </Card>
              </Col>
              <Col span={8}>
                <Card size="small" styles={{ body: { textAlign: 'center' } }}>
                  <Statistic 
                    title="Toplam Çıkış" 
                    value={stats.used} 
                    suffix={stock?.unit}
                    valueStyle={{ color: '#cf1322' }}
                  />
                </Card>
              </Col>
            </Row>
          </Col>
          <Col span={24}>
            <Card title="Stok Değişim Grafiği" size="small" styles={{ body: { padding: '20px 10px 0 0' } }}>
              <div style={{ width: '100%', height: 250 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={chartData}>
                    <defs>
                      <linearGradient id="colorAmount" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#1890ff" stopOpacity={0.3}/>
                        <stop offset="95%" stopColor="#1890ff" stopOpacity={0}/>
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                    <XAxis dataKey="date" fontSize={12} />
                    <YAxis fontSize={12} />
                    <ChartTooltip />
                    <Area 
                      type="monotone" 
                      dataKey="amount" 
                      name="Stok Miktarı"
                      stroke="#1890ff" 
                      fillOpacity={1} 
                      fill="url(#colorAmount)" 
                      strokeWidth={2}
                    />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </Card>
          </Col>
          <Col span={24}>
            <Table 
              dataSource={transactions} 
              columns={columns} 
              rowKey="id"
              size="small"
              pagination={{ pageSize: 5 }}
            />
          </Col>
        </Row>
      ) : (
        <Empty description="Bu ürün için henüz bir hareket kaydı bulunmuyor." />
      )}
    </Modal>
  )
}
