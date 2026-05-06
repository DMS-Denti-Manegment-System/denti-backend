import React, { useMemo, useState } from 'react'
import { Button, Modal, Space, Switch, Tag, Typography } from 'antd'
import { router } from '@inertiajs/react'
import {
  DeleteOutlined,
  EditOutlined,
  ExclamationCircleOutlined,
  MinusOutlined,
  PauseOutlined,
  PlayCircleOutlined,
  StopOutlined,
} from '@ant-design/icons'
import dayjs from 'dayjs'
import { Stock } from '../Types/stock.types'
import { StockLevelBadge } from './StockLevelBadge'
import { useStockTableLogic } from '../Hooks/useStockTableLogic'

const { Text, Paragraph } = Typography

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
  pagination?: {
    current?: number
    pageSize?: number
    total?: number
    onChange?: (page: number, pageSize: number) => void
  }
}

const getCurrentAmount = (record: Stock, isBatchMode: boolean) => {
  return (isBatchMode ? record.current_stock : (record as any).total_stock) || 0
}

export const StockTable: React.FC<StockTableProps> = React.memo(({
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
  pagination,
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
    handleHardDeleteAction,
  } = useStockTableLogic({ onDelete, onSoftDelete, onHardDelete, onReactivate })

  const [showEmptyBatches, setShowEmptyBatches] = useState(false)

  const filteredStocks = useMemo(() => {
    if (!isBatchMode || showEmptyBatches) return stocks
    return stocks.filter((stock) => (stock.current_stock || 0) > 0)
  }, [stocks, isBatchMode, showEmptyBatches])

  const emptyBatchCount = useMemo(() => {
    if (!isBatchMode) return 0
    return stocks.filter((s) => (s.current_stock || 0) === 0).length
  }, [stocks, isBatchMode])

  const currentPage = pagination?.current || 1
  const pageSize = pagination?.pageSize || 10
  const total = pagination?.total || filteredStocks.length
  const totalPages = Math.max(1, Math.ceil(total / pageSize))

  return (
    <>
      {isBatchMode && emptyBatchCount > 0 && (
        <div style={{ marginBottom: 16, display: 'flex', alignItems: 'center', gap: 8 }}>
          <Text type="secondary">Boş stokları göster:</Text>
          <Switch checked={showEmptyBatches} onChange={setShowEmptyBatches} size="small" />
          <Text type="secondary" style={{ fontSize: 12 }}>
            ({emptyBatchCount} kayıt gizleniyor)
          </Text>
        </div>
      )}

      <div style={{ overflowX: 'auto' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: isBatchMode ? 900 : 820 }}>
          <thead>
            <tr style={{ background: '#fafafa', borderBottom: '1px solid #f0f0f0' }}>
              <th style={thStyle}>Ürün</th>
              <th style={thStyle}>Klinik / Konum</th>
              <th style={thStyle}>Miktar</th>
              <th style={thStyle}>Durum</th>
              <th style={thStyle}>{isBatchMode ? 'Takip' : 'Kayıt'}</th>
              <th style={{ ...thStyle, textAlign: 'right' }}>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan={6} style={emptyCellStyle}>Yükleniyor...</td>
              </tr>
            ) : filteredStocks.length === 0 ? (
              <tr>
                <td colSpan={6} style={emptyCellStyle}>Kayıt bulunamadı.</td>
              </tr>
            ) : (
              filteredStocks.map((record) => {
                const current = getCurrentAmount(record, isBatchMode)
                const clinics = (record as any).clinics || []

                return (
                  <tr
                    key={record.id}
                    style={{
                      borderBottom: '1px solid #f5f5f5',
                      backgroundColor: record.is_active === false ? '#fafafa' : '#fff',
                    }}
                  >
                    <td style={tdStyle}>
                      <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                        <Text strong style={{ color: record.is_active === false ? '#8c8c8c' : '#262626' }}>
                          {record.name}
                        </Text>
                        <Text type="secondary" style={{ fontSize: 12 }}>
                          {isBatchMode ? `ID #${record.id}` : record.category || '-'}
                          {record.sku ? ` • ${record.sku}` : ''}
                        </Text>
                      </div>
                    </td>
                    <td style={tdStyle}>
                      {isBatchMode ? (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                          <Tag color="geekblue" style={{ width: 'fit-content', margin: 0 }}>
                            {record.clinic?.name || '-'}
                          </Tag>
                          {record.storage_location && (
                            <Text type="secondary" style={{ fontSize: 12 }}>
                              {record.storage_location}
                            </Text>
                          )}
                        </div>
                      ) : clinics.length > 0 ? (
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                          {clinics.map((name: string) => (
                            <Tag key={name} color="geekblue" style={{ margin: 0 }}>
                              {name}
                            </Tag>
                          ))}
                        </div>
                      ) : (
                        <Text type="secondary">-</Text>
                      )}
                    </td>
                    <td style={tdStyle}>
                      <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                        <Text strong>
                          {current} {record.unit || ''}
                        </Text>
                        {record.has_sub_unit && !!record.current_sub_stock && (
                          <Text type="secondary" style={{ fontSize: 12 }}>
                            + {record.current_sub_stock} {record.sub_unit_name}
                          </Text>
                        )}
                      </div>
                    </td>
                    <td style={tdStyle}>
                      <StockLevelBadge stock={record} />
                    </td>
                    <td style={tdStyle}>
                      {isBatchMode ? (
                        record.expiry_date ? (
                          <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                            <Text type={dayjs(record.expiry_date).isBefore(dayjs().add(1, 'month')) ? 'danger' : 'secondary'} style={{ fontSize: 12 }}>
                              SKT: {dayjs(record.expiry_date).format('DD/MM/YYYY')}
                            </Text>
                            <Text type="secondary" style={{ fontSize: 11 }}>
                              Giriş: {dayjs(record.purchase_date).format('DD/MM/YY')}
                            </Text>
                          </div>
                        ) : (
                          <Text type="secondary">SKT yok</Text>
                        )
                      ) : (
                        <Tag color="blue" style={{ margin: 0 }}>
                          {(record as any).batches_count || 0} kayıt
                        </Tag>
                      )}
                    </td>
                    <td style={{ ...tdStyle, textAlign: 'right' }}>
                      <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, flexWrap: 'wrap' }}>
                        {isBatchMode ? (
                          <>
                            <Button size="small" onClick={() => onAdjust(record)}>
                              Ayarla
                            </Button>
                            <Button
                              type="primary"
                              size="small"
                              onClick={() => onUse(record)}
                              disabled={record.current_stock <= 0 || !record.is_active}
                            >
                              Kullan
                            </Button>
                          </>
                        ) : (
                          <Button
                            type="primary"
                            ghost
                            size="small"
                            onClick={() => router.visit(`/stock/products/${record.id}`)}
                          >
                            Yönet
                          </Button>
                        )}
                        <Button size="small" icon={<EditOutlined />} onClick={() => onEdit(record)} />
                        <Button size="small" icon={<ExclamationCircleOutlined />} onClick={() => handleAdvancedDelete(record)} />
                        <Button danger size="small" icon={<DeleteOutlined />} onClick={() => handleDeleteConfirm(record.id)} />
                      </div>
                    </td>
                  </tr>
                )
              })
            )}
          </tbody>
        </table>
      </div>

      {pagination && total > pageSize && (
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 16, gap: 12, flexWrap: 'wrap' }}>
          <Text type="secondary">
            Sayfa {currentPage} / {totalPages} • Toplam {total} kayıt
          </Text>
          <Space>
            <Button disabled={currentPage <= 1} onClick={() => pagination.onChange?.(currentPage - 1, pageSize)}>
              Önceki
            </Button>
            <Button disabled={currentPage >= totalPages} onClick={() => pagination.onChange?.(currentPage + 1, pageSize)}>
              Sonraki
            </Button>
          </Space>
        </div>
      )}

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
              <Paragraph strong style={{ fontSize: 16, margin: 0 }}>{advancedModalStock.name}</Paragraph>
            </div>

            <div style={{ background: '#fff1f0', padding: 12, borderRadius: 8, border: '1px solid #ffa39e' }}>
              <Text type="danger" strong>Kritik İşlemler</Text>
              <Paragraph style={{ margin: '8px 0 0', fontSize: 13 }}>
                Zorla silme işlemi veritabanı bütünlüğünü etkileyebilir.
              </Paragraph>
            </div>

            <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end', flexWrap: 'wrap' }}>
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
        <Text type="secondary">Geçmiş hareket varsa sistem silmek yerine pasife alabilir.</Text>
      </Modal>
    </>
  )
})

const thStyle: React.CSSProperties = {
  textAlign: 'left',
  padding: '12px 14px',
  fontSize: 13,
  fontWeight: 600,
  whiteSpace: 'nowrap',
}

const tdStyle: React.CSSProperties = {
  padding: '14px',
  verticalAlign: 'middle',
}

const emptyCellStyle: React.CSSProperties = {
  padding: '32px 14px',
  textAlign: 'center',
  color: '#8c8c8c',
}
