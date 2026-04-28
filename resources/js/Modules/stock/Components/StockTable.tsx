// src/modules/stock/Components/StockTable.tsx

import React from 'react'
import { Table, Tag, Tooltip, Space, Button, Dropdown, Modal, Typography, Avatar } from 'antd'

const { Text } = Typography
import { 
  EditOutlined,
  DeleteOutlined,
  MoreOutlined,
  MinusOutlined,
  PlusOutlined,
  ExclamationCircleOutlined,
  PauseOutlined,
  PlayCircleOutlined,
  StopOutlined,
  LineChartOutlined
} from '@ant-design/icons'
import dayjs from 'dayjs'
import type { ColumnsType } from 'antd/es/table'
import type { MenuProps } from 'antd'
import { Stock } from '../Types/stock.types'
import { StockLevelBadge } from './StockLevelBadge'
import { formatStock } from '@/Utils/helpers'
import { useStockTableLogic } from '../Hooks/useStockTableLogic'

interface StockTableProps {
  stocks: Stock[]
  loading: boolean
  isBatchMode?: boolean
  onEdit: (stock: Stock) => void
  onDelete: (id: number) => void
  onSoftDelete: (id: number) => void
  onHardDelete: (id: number) => void
  onReactivate: (id: number) => void
  onAdjust: (stock: Stock) => void
  onUse: (stock: Stock) => void
  onViewHistory: (stock: Stock) => void
}

export const StockTable: React.FC<StockTableProps> = ({
  stocks,
  loading,
  isBatchMode = false,
  onEdit,
  onDelete,
  onSoftDelete,
  onHardDelete,
  onReactivate,
  onAdjust,
  onUse,
  onViewHistory,
}) => {
  const {
    advancedModalStock,
    setAdvancedModalStock,
    deleteStockId,
    setDeleteStockId,
    getStockStatus,
    handleDeleteConfirm,
    handleAdvancedDelete,
    handleStandardDelete,
    handleSoftDeleteAction,
    handleReactivateAction,
    handleHardDeleteAction
  } = useStockTableLogic({ onDelete, onSoftDelete, onHardDelete, onReactivate })

  const columns: ColumnsType<Stock> = [
    {
      title: '📦 Ürün / Parti',
      key: 'name',
      fixed: 'left',
      width: 250,
      render: (_, record) => (
        <Space size={12}>
          <Avatar 
            shape="rounded"
            style={{ backgroundColor: '#e6f7ff', color: '#1890ff', fontWeight: 'bold' }}
          >
            {record.name.charAt(0).toUpperCase()}
          </Avatar>
          <div>
            <Text strong style={{ fontSize: 13, display: 'block' }}>{record.name}</Text>
            {isBatchMode ? (
              <Text type="secondary" style={{ fontSize: 11 }}>
                ID: #{record.id} | {record.storage_location || 'Konum yok'}
              </Text>
            ) : (
              record.sku && <Text type="secondary" style={{ fontSize: 11 }}>SKU: {record.sku}</Text>
            )}
          </div>
        </Space>
      ),
    },
    !isBatchMode ? {
      title: '🗂️ Kategori',
      dataIndex: 'category',
      key: 'category',
      width: 120,
      render: (cat) => <Tag color="cyan">{cat}</Tag>
    } : {
      title: '📅 Giriş Tarihi',
      dataIndex: 'purchase_date',
      key: 'purchase_date',
      width: 120,
      render: (date) => dayjs(date).format('DD/MM/YYYY')
    },
    {
      title: '🏥 Klinik',
      key: 'clinic',
      width: 150,
      render: (_, record) => (
        <Tag color="geekblue">
          {isBatchMode ? record.clinic?.name : (record as any).clinic_name || '-'}
        </Tag>
      )
    },
    {
      title: isBatchMode ? '🔢 Parti Stoğu' : '🔢 Toplam Stok',
      key: 'current_stock',
      width: 150,
      render: (_, record) => (
        <div>
          <Text strong style={{ fontSize: 14 }}>
            {isBatchMode ? record.current_stock : (record as any).total_stock} {record.unit}
          </Text>
        </div>
      )
    },
    isBatchMode ? {
      title: '📅 S.K.T',
      dataIndex: 'expiry_date',
      key: 'expiry_date',
      width: 120,
      render: (date) => date ? dayjs(date).format('DD/MM/YYYY') : '-'
    } : {
      title: '📦 Partiler',
      key: 'batches_count',
      width: 100,
      align: 'center',
      render: (_, record) => <Tag color="blue">{(record as any).batches?.length || 0} Parti</Tag>
    },
    {
      title: '⚡ Durum',
      key: 'status',
      width: 120,
      align: 'center',
      render: (_, record) => <StockLevelBadge stock={record} />
    },
    isBatchMode ? {
      title: '🏪 Tedarikçi',
      key: 'supplier',
      width: 150,
      render: (_, record) => record.supplier?.name || '-'
    } : {
      title: '📉 Min',
      dataIndex: 'min_stock_level',
      key: 'min_stock_level',
      width: 80,
      align: 'center',
    },
    {
      title: isBatchMode ? '💰 Fiyat' : '⚠️ Kritik',
      key: 'price_or_critical',
      width: 120,
      render: (_, record) => isBatchMode ? (
        <Text style={{ color: '#52c41a' }}>{record.purchase_price} {record.currency}</Text>
      ) : (
        <Text type="danger" strong>{record.critical_stock_level}</Text>
      )
    },
    {
      title: '⚙️',
      key: 'actions',
      width: 120,
      fixed: 'right',
      align: 'center',
      render: (_, record) => (
        <Space>
          {!isBatchMode && (
            <Button 
              type="primary" 
              size="small" 
              onClick={() => window.location.href = `/stock/products/${record.id}`}
            >
              Detay
            </Button>
          )}
          {isBatchMode && (
            <Button 
              size="small" 
              onClick={() => onUse(record)}
              disabled={record.current_stock <= 0}
            >
              Kullan
            </Button>
          )}
          <Dropdown 
            menu={{ 
              items: [
                { key: 'edit', label: 'Düzenle', icon: <EditOutlined />, onClick: () => onEdit(record) },
                { key: 'delete', label: 'Sil', icon: <DeleteOutlined />, danger: true, onClick: () => handleDeleteConfirm(record.id) }
              ] 
            }} 
            trigger={['click']}
          >
            <Button type="text" icon={<MoreOutlined />} />
          </Dropdown>
        </Space>
      )
    },
  ].filter(Boolean) as ColumnsType<Stock>

  return (
    <>
    <Table
      columns={columns}
      scroll={{ x: 1800 }}
      dataSource={stocks}
      rowKey="id"
      loading={loading}
      pagination={{
        pageSize: 10,
        showSizeChanger: true,
        showTotal: (total) => `Toplam ${total} ürün`,
      }}
      size="small"
      onRow={(record) => {
        const isInactive = record.is_active === false
        return {
          style: {
            backgroundColor: isInactive ? '#fafafa' : '#fff',
            cursor: 'pointer',
            borderLeft: isInactive ? '3px solid #faad14' : 'none'
          },
          onClick: () => onViewHistory(record)
        }
      }}
      className="premium-table"
      style={{ borderRadius: '8px', overflow: 'hidden' }}
    />

    <Modal
      title={
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <ExclamationCircleOutlined style={{ color: '#faad14' }} />
          <span>Gelişmiş Durum & Güvenlik İşlemleri</span>
        </div>
      }
      open={!!advancedModalStock}
      onCancel={() => setAdvancedModalStock(null)}
      footer={null}
      width={500}
      destroyOnHidden
    >
      {advancedModalStock && (
        <div>
          <p><strong>Stok:</strong> {advancedModalStock.name}</p>
          <p><strong>Mevcut Durum:</strong> {
             advancedModalStock.status === 'deleted' ? '🗑️ Silinmiş' : 
             advancedModalStock.is_active === false ? '⏸️ Pasif' : 
             '✅ Aktif'
          }</p>
          
          <div style={{ background: '#fff1f0', padding: 12, borderRadius: 6, margin: '16px 0' }}>
            <p style={{ margin: 0, fontSize: 13, color: '#cf1322' }}>
              <strong>⚠️ Zorla Silme (Force Delete):</strong><br/>
              İşlem geçmişi olan stokları bile zorla siler. Raporlarda tutarsızlığa neden olabilir.
            </p>
          </div>

          <Space style={{ width: '100%', justifyContent: 'flex-end' }}>
            <Button onClick={() => setAdvancedModalStock(null)}>İptal</Button>
            
            {advancedModalStock.is_active !== false && advancedModalStock.status !== 'deleted' && (
              <Button 
                icon={<PauseOutlined />}
                onClick={handleSoftDeleteAction}
              >
                Pasife Al
              </Button>
            )}

            {advancedModalStock.is_active === false && advancedModalStock.status !== 'deleted' && (
              <Button 
                type="primary"
                icon={<PlayCircleOutlined />}
                onClick={handleReactivateAction}
              >
                Aktif Et
              </Button>
            )}

            {advancedModalStock.status !== 'deleted' && (
              <Button 
                type="primary" 
                danger 
                icon={<StopOutlined />}
                onClick={handleHardDeleteAction}
              >
                Zorla Sil
              </Button>
            )}
          </Space>
        </div>
      )}
    </Modal>

    <Modal
      title="Stoku Silmek İstediğinize Emin Misiniz?"
      open={!!deleteStockId}
      onCancel={() => setDeleteStockId(null)}
      okText="Evet, Sil"
      cancelText="İptal"
      okButtonProps={{ danger: true }}
      onOk={handleStandardDelete}
    >
      <p>Bu işlem, stok kullanım geçmişine göre ürünü ya tamamen siler ya da otomatik olarak pasife alır.</p>
    </Modal>
    </>
  )
}
