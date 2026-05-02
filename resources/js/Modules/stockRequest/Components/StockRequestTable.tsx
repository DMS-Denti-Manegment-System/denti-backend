// src/modules/stockRequest/Components/StockRequestTable.tsx

import React from 'react'
import { Table, Tag, Typography, Space, Tooltip, Avatar } from 'antd'
import type { ColumnsType } from 'antd/es/table'
import { StockRequest } from '../Types/stockRequest.types'
import { StockRequestStatusBadge } from './StockRequestStatusBadge'
import { StockRequestActions } from './StockRequestActions'
import dayjs from 'dayjs'
import { 
  ArrowRightOutlined, 
  UserOutlined, 
  CommentOutlined
} from '@ant-design/icons'

const { Text } = Typography

interface StockRequestTableProps {
  requests: StockRequest[]
  loading: boolean
  currentUser: string
  onRefresh: () => void
}

export const StockRequestTable: React.FC<StockRequestTableProps> = ({
  requests,
  loading,
  currentUser,
  onRefresh
}) => {
  const columns: ColumnsType<StockRequest> = [
    {
      title: '📦 Ürün & Miktar',
      key: 'stock',
      width: 200,
      render: (_, record) => (
        <Space direction="vertical" size={0}>
          <Text strong>{record.stock?.name || 'Bilinmeyen Ürün'}</Text>
          <Space>
            <Tag color="blue">{record.requested_quantity} {record.stock?.unit || 'Adet'}</Tag>
            {record.approved_quantity !== undefined && record.status !== 'pending' && (
              <Tooltip title="Onaylanan Miktar">
                <Tag color="green">✓ {record.approved_quantity}</Tag>
              </Tooltip>
            )}
          </Space>
        </Space>
      )
    },
    {
      title: '🔄 Akış (Klinik)',
      key: 'flow',
      width: 300,
      render: (_, record) => (
        <Space align="center" size="small">
          <Tooltip title="Talep Eden">
            <Text type="secondary" style={{ fontSize: '12px' }}>
              {record.requester_clinic?.name || 'Bilinmiyor'}
            </Text>
          </Tooltip>
          <ArrowRightOutlined style={{ fontSize: '10px', color: '#bfbfbf' }} />
          <Tooltip title="Talep Edilen">
            <Text strong style={{ fontSize: '12px' }}>
              {record.requested_from_clinic?.name || 'Bilinmiyor'}
            </Text>
          </Tooltip>
        </Space>
      )
    },
    {
      title: '👤 Talep Eden',
      key: 'requester',
      width: 150,
      render: (_, record) => (
        <Space>
          <Avatar size="small" icon={<UserOutlined />} />
          <Text style={{ fontSize: '13px' }}>{record.requested_by}</Text>
        </Space>
      )
    },
    {
      title: '📅 Tarih',
      dataIndex: 'requested_at',
      key: 'date',
      width: 150,
      render: (date) => (
        <Space direction="vertical" size={0}>
          <Text style={{ fontSize: '13px' }}>{dayjs(date).format('DD/MM/YYYY')}</Text>
          <Text type="secondary" style={{ fontSize: '11px' }}>{dayjs(date).format('HH:mm')}</Text>
        </Space>
      )
    },
    {
      title: '⚡ Durum',
      key: 'status',
      width: 130,
      align: 'center',
      render: (_, record) => <StockRequestStatusBadge status={record.status} />
    },
    {
      title: '📝 Sebep/Not',
      key: 'reason',
      width: 200,
      render: (_, record) => (
        <Tooltip title={record.request_reason}>
          <Text ellipsis style={{ maxWidth: 180, fontSize: '12px' }}>
            <CommentOutlined style={{ marginRight: 4, color: '#bfbfbf' }} />
            {record.request_reason}
          </Text>
        </Tooltip>
      )
    },
    {
      title: '⚙️ İşlemler',
      key: 'actions',
      fixed: 'right',
      width: 120,
      align: 'center',
      render: (_, record) => (
        <StockRequestActions 
          request={record} 
          currentUser={currentUser} 
          onSuccess={onRefresh}
        />
      )
    }
  ]

  return (
    <Table
      columns={columns}
      dataSource={requests}
      rowKey="id"
      loading={loading}
      pagination={{
        pageSize: 10,
        showSizeChanger: true,
        showTotal: (total) => `Toplam ${total} talep`,
      }}
      size="middle"
      scroll={{ x: 1200 }}
      className="premium-table"
      style={{ borderRadius: '8px', overflow: 'hidden' }}
    />
  )
}
