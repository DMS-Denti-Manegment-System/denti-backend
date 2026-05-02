// src/modules/stock/Components/StockTable.tsx

import React, { useState, useMemo } from 'react'
import { Table, Tag, Space, Button, Dropdown, Modal, Typography, Avatar, Progress, Badge, Switch } from 'antd'

import { router } from '@inertiajs/react'

const { Text, Paragraph } = Typography
import { 
  EditOutlined,
  DeleteOutlined,
  MoreOutlined,
  MinusOutlined,
  ExclamationCircleOutlined,
  PauseOutlined,
  PlayCircleOutlined,
  StopOutlined,
  ShoppingOutlined
} from '@ant-design/icons'
import dayjs from 'dayjs'
import type { ColumnsType } from 'antd/es/table'
import { Stock } from '../Types/stock.types'
import { StockLevelBadge } from './StockLevelBadge'
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
  onUse,
}) => {
  const {
    advancedModalStock,
    setAdvancedModalStock,
    deleteStockId,
    setDeleteStockId,
    handleDeleteConfirm,
    handleAdvancedDelete,
    handleStandardDelete,
    handleSoftDeleteAction,
    handleReactivateAction,
    handleHardDeleteAction
  } = useStockTableLogic({ onDelete, onSoftDelete, onHardDelete, onReactivate })

  // 0 stoklu batch'leri gizleme state'i
  const [showEmptyBatches, setShowEmptyBatches] = useState(false)

  // Batch'leri filtrele (0 stoklu olmayanları göster)
  const filteredStocks = useMemo(() => {
    if (!isBatchMode) return stocks
    if (showEmptyBatches) return stocks
    return stocks.filter(stock => (stock.current_stock || 0) > 0)
  }, [stocks, isBatchMode, showEmptyBatches])

  const emptyBatchCount = useMemo(() => {
    if (!isBatchMode) return 0
    return stocks.filter(s => (s.current_stock || 0) === 0).length
  }, [stocks, isBatchMode])

  const columns: ColumnsType<Stock> = [
    {
      title: '📦 Ürün Bilgisi',
      key: 'product_info',
      fixed: 'left',
      width: 280,
      render: (_unused: unknown, record) => (
        <Space size={12} align="start">
          <Avatar 
            shape="square" 
            size={44}
            style={{ 
              backgroundColor: record.is_active ? '#e6f7ff' : '#f5f5f5', 
              color: record.is_active ? '#1890ff' : '#bfbfbf',
              borderRadius: '8px',
              border: '1px solid #d9d9d9'
            }}
            icon={<ShoppingOutlined />}
          />
          <div style={{ display: 'flex', flexDirection: 'column' }}>
            <Text strong style={{ fontSize: '14px', color: record.is_active ? '#262626' : '#8c8c8c' }}>
              {record.name}
            </Text>
            <Space size={4} split={<Text type="secondary" style={{ fontSize: '10px' }}>•</Text>}>
                {!isBatchMode ? (
                   <Text type="secondary" style={{ fontSize: '12px' }}>{record.category}</Text>
                ) : (
                   <Text type="secondary" style={{ fontSize: '12px' }}>ID: #{record.id}</Text>
                )}
                {record.sku && <Text type="secondary" style={{ fontSize: '12px' }}>{record.sku}</Text>}
            </Space>
          </div>
        </Space>
      ),
    },
    {
      title: '🏥 Klinik & Konum',
      key: 'location',
      width: 180,
      render: (_unused: unknown, record) => {
        const clinics = (record as any).clinics || [];
        return (
          <div style={{ display: 'flex', flexDirection: 'column' }}>
            {isBatchMode ? (
              <Tag color="geekblue" style={{ width: 'fit-content', marginBottom: '4px' }}>
                {record.clinic?.name}
              </Tag>
            ) : (
              <Space direction="vertical" size={2}>
                {clinics.length > 0 ? (
                  clinics.map((name: string) => (
                    <Tag key={name} color="geekblue" style={{ margin: 0 }}>
                      {name}
                    </Tag>
                  ))
                ) : (
                  <Tag color="default">-</Tag>
                )}
              </Space>
            )}
            {isBatchMode && record.storage_location && (
              <Text type="secondary" style={{ fontSize: '11px', marginTop: '4px' }}>📍 {record.storage_location}</Text>
            )}
          </div>
        )
      }
    },
    {
      title: '📊 Stok Durumu',
      key: 'stock_level',
      width: 200,
      render: (_unused: unknown, record) => {
        const current = (isBatchMode ? record.current_stock : (record as any).total_stock) || 0;
        const min = record.min_stock_level || 0;
        const critical = record.critical_stock_level || 0;
        const percent = min > 0 ? Math.min((current / (min * 2)) * 100, 100) : 100;
        
        let statusColor = '#52c41a';
        if (current <= critical) statusColor = '#f5222d';
        else if (current <= min) statusColor = '#faad14';

        return (
          <div style={{ width: '100%', paddingRight: '20px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
              <Text strong>{current} {record.unit || ''}</Text>
              <StockLevelBadge stock={record} />
            </div>
            <Progress 
                percent={percent} 
                showInfo={false} 
                strokeColor={statusColor} 
                size="small" 
                trailColor="#f0f0f0"
            />
          </div>
        );
      }
    },
    ...(isBatchMode ? [
      {
        title: '📅 Takip',
        key: 'tracking',
        width: 160,
        render: (_unused: unknown, record: Stock) => (
          <div style={{ display: 'flex', flexDirection: 'column' }}>
            {record.expiry_date ? (
                <Space size={4}>
                    <Text type={dayjs(record.expiry_date).isBefore(dayjs().add(1, 'month')) ? 'danger' : 'secondary'} style={{ fontSize: '12px' }}>
                        SKT: {dayjs(record.expiry_date).format('DD/MM/YYYY')}
                    </Text>
                    {dayjs(record.expiry_date).isBefore(dayjs()) && <Badge status="error" />}
                </Space>
            ) : <Text type="secondary" style={{ fontSize: '12px' }}>SKT Yok</Text>}
            <Text type="secondary" style={{ fontSize: '11px' }}>Giriş: {dayjs(record.purchase_date).format('DD/MM/YY')}</Text>
          </div>
        )
      }
    ] : [
        {
            title: '📦 Partiler',
            key: 'batches',
            width: 100,
            align: 'center' as const,
            render: (_unused: unknown, record: any) => (
                <Badge count={record.batches?.length || 0} color="#1890ff" showZero />
            )
        }
    ]),
    {
      title: '',
      key: 'actions',
      width: 160,
      fixed: 'right' as const,
      align: 'right' as const,
      render: (_unused: unknown, record) => (
        <Space>
          {isBatchMode ? (
            <Button 
              type="primary"
              size="small" 
              onClick={() => onUse(record)}
              disabled={record.current_stock <= 0 || !record.is_active}
              style={{ borderRadius: '4px' }}
            >
              Kullan
            </Button>
          ) : (
            <Button 
                type="primary" 
                ghost
                size="small" 
                onClick={() => router.visit(`/stock/products/${record.id}`)}
                style={{ borderRadius: '4px' }}
            >
                Yönet
            </Button>
          )}
          
          <Dropdown 
            menu={{
              items: [
                { key: 'edit', label: 'Düzenle', icon: <EditOutlined />, onClick: () => onEdit(record) },
                { key: 'use', label: 'Stok Kullan', icon: <MinusOutlined />, onClick: () => onUse(record) },
                { type: 'divider' },
                { key: 'advanced', label: 'Gelişmiş İşlemler', icon: <ExclamationCircleOutlined />, onClick: () => handleAdvancedDelete(record) },
                { type: 'divider' },
                { key: 'delete', label: 'Sil', icon: <DeleteOutlined />, danger: true, onClick: () => handleDeleteConfirm(record.id) }
              ]
            }}
            trigger={['click']}
          >
            <Button type="text" icon={<MoreOutlined />} shape="circle" />
          </Dropdown>
        </Space>
      )
    },
  ]

  return (
    <>
    {/* Boş batch'leri göster/gizle switch'i (sadece batch mode'da) */}
    {isBatchMode && emptyBatchCount > 0 && (
      <div style={{ marginBottom: 16, display: 'flex', alignItems: 'center', gap: 8 }}>
        <Text type="secondary">Boş partileri göster:</Text>
        <Switch
          checked={showEmptyBatches}
          onChange={setShowEmptyBatches}
          size="small"
        />
        <Text type="secondary" style={{ fontSize: '12px' }}>
          ({emptyBatchCount} tane boş parti gizlendi)
        </Text>
      </div>
    )}
    <Table
      columns={columns}
      scroll={{ x: 1000 }} // Width reduced from 1800 to 1000 to prevent extreme sliding
      dataSource={filteredStocks}
      rowKey="id"
      loading={loading}
      pagination={{
        pageSize: 10,
        showSizeChanger: true,
        showTotal: (total) => `Toplam ${total} kayıt`,
      }}
      size="middle"
      onRow={(record) => ({
          style: {
            backgroundColor: record.is_active === false ? '#fafafa' : '#fff',
            cursor: 'default'
          }
      })}
      className="custom-premium-table"
    />

    <Modal
      title={
        <Space>
          <ExclamationCircleOutlined style={{ color: '#faad14' }} />
          <span>Gelişmiş İşlemler</span>
        </Space>
      }
      open={!!advancedModalStock}
      onCancel={() => setAdvancedModalStock(null)}
      footer={null}
      width={450}
    >
      {advancedModalStock && (
        <Space direction="vertical" style={{ width: '100%' }} size="large">
          <div>
            <Text type="secondary">Seçili Kayıt:</Text>
            <Paragraph strong style={{ fontSize: '16px', margin: 0 }}>{advancedModalStock.name}</Paragraph>
          </div>
          
          <div style={{ background: '#fff1f0', padding: '12px', borderRadius: '8px', border: '1px solid #ffa39e' }}>
            <Text type="danger" strong>⚠️ Kritik İşlemler</Text>
            <Paragraph style={{ margin: '8px 0 0', fontSize: '13px' }}>
              Zorla silme işlemi veritabanı bütünlüğünü etkileyebilir. Lütfen dikkatli olun.
            </Paragraph>
          </div>

          <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
            <Button onClick={() => setAdvancedModalStock(null)}>Kapat</Button>
            {advancedModalStock.is_active !== false && (
              <Button icon={<PauseOutlined />} onClick={handleSoftDeleteAction}>Pasife Al</Button>
            )}
            {advancedModalStock.is_active === false && (
              <Button type="primary" icon={<PlayCircleOutlined />} onClick={handleReactivateAction}>Aktif Et</Button>
            )}
            <Button type="primary" danger icon={<StopOutlined />} onClick={handleHardDeleteAction}>Kalıcı Sil</Button>
          </div>
        </Space>
      )}
    </Modal>

    <Modal
      title="Silme Onayı"
      open={!!deleteStockId}
      onCancel={() => setDeleteStockId(null)}
      okText="Evet, Sil"
      cancelText="İptal"
      okButtonProps={{ danger: true }}
      onOk={handleStandardDelete}
    >
      <Paragraph>Bu ürünü silmek istediğinize emin misiniz?</Paragraph>
      <Text type="secondary">Eğer ürünün geçmiş hareketleri varsa, sistem ürünü silmek yerine otomatik olarak pasife alacaktır.</Text>
    </Modal>

    <style dangerouslySetInnerHTML={{ __html: `
      .custom-premium-table .ant-table-thead > tr > th {
        background: #fafafa;
        font-weight: 600;
        border-bottom: 2px solid #f0f0f0;
      }
      .custom-premium-table .ant-table-row:hover > td {
        background: #f9fbfd !important;
      }
      .custom-premium-table .ant-table-cell-fix-right, 
      .custom-premium-table .ant-table-cell-fix-left {
        background: inherit !important;
      }
    `}} />
    </>
  )
}
