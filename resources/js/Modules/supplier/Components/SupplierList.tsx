// src/modules/supplier/Components/SupplierList.tsx

import React, { useState } from 'react'
import { 
  Card, 
  Row, 
  Col, 
  Input, 
  Select, 
  Button, 
  Space, 
  Modal, 
  Typography,
  Statistic,
  Table,
  Tag,
  Tooltip,
  Avatar,
  Popconfirm
} from 'antd'
import type { ColumnsType } from 'antd/es/table'
import { 
  PlusOutlined, 
  ReloadOutlined,
  TeamOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  PhoneOutlined,
  MailOutlined,
  UserOutlined,
  HomeOutlined
} from '@ant-design/icons'
import dayjs from 'dayjs'
import { useSuppliers } from '../Hooks/useSuppliers'
import { Supplier, SupplierFilter } from '../Types/supplier.types'
import { SupplierForm } from './SupplierForm'

const { Option } = Select
const { Search } = Input
const { Title } = Typography

export const SupplierList: React.FC = () => {
  const [filters, setFilters] = useState<SupplierFilter>({})
  const [editingSupplier, setEditingSupplier] = useState<Supplier | null>(null)
  const [isFormModalVisible, setIsFormModalVisible] = useState(false)
  const [viewingSupplier, setViewingSupplier] = useState<Supplier | null>(null)
  const [isViewModalVisible, setIsViewModalVisible] = useState(false)

  const { 
    suppliers, 
    isLoading, 
    refetch, 
    deleteSupplier
  } = useSuppliers(filters)

  const handleSearch = (value: string) => {
    setFilters(prev => ({ ...prev, name: value }))
  }

  const handleFilterChange = (field: keyof SupplierFilter, value: any) => {
    let mappedValue = value;
    if (field === 'is_active') {
      mappedValue = value === 'active' ? true : value === 'inactive' ? false : undefined;
    }
    setFilters(prev => ({ ...prev, [field]: mappedValue }))
  }

  const handleAdd = () => {
    setEditingSupplier(null)
    setIsFormModalVisible(true)
  }

  const handleEdit = (supplier: Supplier) => {
    setEditingSupplier(supplier)
    setIsFormModalVisible(true)
  }

  const handleView = (supplier: Supplier) => {
    setViewingSupplier(supplier)
    setIsViewModalVisible(true)
  }

  const handleDelete = async (id: number) => {
    await deleteSupplier(id)
  }

  const onFormSuccess = () => {
    setIsFormModalVisible(false)
    setEditingSupplier(null)
  }

  // Stats hesaplama
  const calculateStats = () => {
    if (!suppliers) return { total: 0, active: 0, inactive: 0 }
    
    const total = suppliers.length
    const active = suppliers.filter(s => s.is_active).length
    const inactive = total - active
    
    return { total, active, inactive }
  }

  const stats = calculateStats()

  // Tablo kolonları
  const columns: ColumnsType<Supplier> = [
    {
      title: 'Firma',
      dataIndex: 'name',
      key: 'name',
      width: 200,
      sorter: (a, b) => a.name.localeCompare(b.name),
      render: (text: string, record: Supplier) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <Avatar 
            size={32} 
            style={{ backgroundColor: record.is_active ? '#52c41a' : '#ff4d4f' }}
          >
            {text.charAt(0).toUpperCase()}
          </Avatar>
          <div>
            <div style={{ fontWeight: 500, fontSize: '14px' }}>{text}</div>
            <div style={{ fontSize: '12px', color: '#666' }}>
              ID: {record.id}
            </div>
          </div>
        </div>
      ),
    },
    {
      title: 'İletişim Kişisi',
      dataIndex: 'contact_person',
      key: 'contact_person',
      width: 150,
      sorter: (a, b) => (a.contact_person || '').localeCompare(b.contact_person || ''),
      render: (text: string) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
          <UserOutlined style={{ color: '#1890ff', fontSize: '12px' }} />
          <span>{text || '-'}</span>
        </div>
      ),
    },
    {
      title: 'İletişim Bilgileri',
      key: 'contact',
      width: 200,
      render: (_, record: Supplier) => (
        <Space direction="vertical" size="small">
          {record.phone && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
              <PhoneOutlined style={{ color: '#52c41a', fontSize: '12px' }} />
              <span style={{ fontSize: '12px' }}>{record.phone}</span>
            </div>
          )}
          {record.email && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
              <MailOutlined style={{ color: '#faad14', fontSize: '12px' }} />
              <span style={{ fontSize: '12px' }}>{record.email}</span>
            </div>
          )}
        </Space>
      ),
    },
    {
      title: 'Adres',
      dataIndex: 'address',
      key: 'address',
      width: 200,
      ellipsis: {
        showTitle: false,
      },
      render: (text: string) => (
        <Tooltip placement="topLeft" title={text}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
            <HomeOutlined style={{ color: '#722ed1', fontSize: '12px' }} />
            <span style={{ fontSize: '12px' }}>{text || '-'}</span>
          </div>
        </Tooltip>
      ),
    },
    {
      title: 'Vergi No',
      dataIndex: 'tax_number',
      key: 'tax_number',
      width: 120,
      render: (text: string) => (
        <span style={{ fontSize: '12px', fontFamily: 'monospace' }}>
          {text || '-'}
        </span>
      ),
    },
    {
      title: 'Durum',
      dataIndex: 'is_active',
      key: 'is_active',
      width: 100,
      filters: [
        { text: 'Aktif', value: true },
        { text: 'Pasif', value: false },
      ],
      onFilter: (value, record) => record.is_active === value,
      render: (isActive: boolean) => (
        <Tag color={isActive ? 'green' : 'red'} style={{ fontSize: '11px' }}>
          {isActive ? 'Aktif' : 'Pasif'}
        </Tag>
      ),
    },
    {
      title: 'Ek Bilgiler',
      key: 'additional_info',
      width: 150,
      render: (_, record: Supplier) => (
        <Space direction="vertical" size="small">
          {record.additional_info?.delivery_time && (
            <Tag color="blue" style={{ fontSize: '10px' }}>
              📦 {record.additional_info.delivery_time}
            </Tag>
          )}
          {record.additional_info?.payment_terms && (
            <Tag color="orange" style={{ fontSize: '10px' }}>
              💳 {record.additional_info.payment_terms}
            </Tag>
          )}
        </Space>
      ),
    },
    {
      title: 'Kayıt Tarihi',
      dataIndex: 'created_at',
      key: 'created_at',
      width: 120,
      sorter: (a, b) => dayjs(a.created_at).unix() - dayjs(b.created_at).unix(),
      render: (date: string) => (
        <span style={{ fontSize: '12px' }}>
          {dayjs(date).format('DD/MM/YYYY')}
        </span>
      ),
    },
    {
      title: 'İşlemler',
      key: 'actions',
      width: 120,
      fixed: 'right',
      render: (_, record: Supplier) => (
        <Space size="small">
          <Tooltip title="Detay Görüntüle">
            <Button 
              type="text" 
              size="small"
              icon={<EyeOutlined />}
              onClick={() => handleView(record)}
            />
          </Tooltip>
          <Tooltip title="Düzenle">
            <Button 
              type="text" 
              size="small"
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
            />
          </Tooltip>
          <Popconfirm
            title="Tedarikçi Silme"
            description="Bu tedarikçiyi silmek istediğinizden emin misiniz?"
            onConfirm={() => handleDelete(record.id)}
            okText="Evet, Sil"
            cancelText="İptal"
            okType="danger"
          >
            <Tooltip title="Sil">
              <Button 
                type="text" 
                size="small"
                danger
                icon={<DeleteOutlined />}
              />
            </Tooltip>
          </Popconfirm>
        </Space>
      ),
    },
  ]

  const statsCards = (
    <Row gutter={16} style={{ marginBottom: 24 }}>
      <Col span={6}>
        <Card>
          <Statistic 
            title="Toplam Tedarikçi" 
            value={stats.total}
            prefix={<TeamOutlined />}
          />
        </Card>
      </Col>
      <Col span={6}>
        <Card>
          <Statistic 
            title="Aktif Tedarikçi" 
            value={stats.active}
            styles={{ content: { color: '#52c41a' } }}
            prefix={<CheckCircleOutlined />}
          />
        </Card>
      </Col>
      <Col span={6}>
        <Card>
          <Statistic 
            title="Pasif Tedarikçi" 
            value={stats.inactive}
            styles={{ content: { color: '#ff4d4f' } }}
            prefix={<CloseCircleOutlined />}
          />
        </Card>
      </Col>
      <Col span={6}>
        <Card>
          <Statistic 
            title="Bu Ay Yeni" 
            value={suppliers?.filter(s => 
              dayjs(s.created_at).isAfter(dayjs().startOf('month'))
            ).length || 0}
            styles={{ content: { color: '#1890ff' } }}
          />
        </Card>
      </Col>
    </Row>
  )

  const filterSection = (
    <Card style={{ marginBottom: 24 }}>
      <Row gutter={16} align="middle">
        <Col span={8}>
          <Search
            placeholder="Tedarikçi adı ile ara..."
            onSearch={handleSearch}
            style={{ width: '100%' }}
            allowClear
          />
        </Col>
        <Col span={4}>
          <Select
            placeholder="Durum"
            style={{ width: '100%' }}
            allowClear
            onChange={(value) => handleFilterChange('is_active', value)}
          >
            <Option value="active">Aktif</Option>
            <Option value="inactive">Pasif</Option>
          </Select>
        </Col>
        <Col span={8}>
          <Input
            placeholder="İletişim kişisi ile ara..."
            onChange={(e) => handleFilterChange('contact_person', e.target.value)}
            allowClear
          />
        </Col>
        <Col span={4}>
          <Space>
            <Button 
              type="primary" 
              icon={<PlusOutlined />} 
              onClick={handleAdd}
            >
              Yeni Tedarikçi
            </Button>
            <Button 
              icon={<ReloadOutlined />} 
              onClick={() => refetch()}
            >
              Yenile
            </Button>
          </Space>
        </Col>
      </Row>
    </Card>
  )

  return (
    <div>
      <Title level={2}>Tedarikçi Yönetimi</Title>
      
      {statsCards}
      {filterSection}

      <Card>
        <Table
          columns={columns}
          dataSource={suppliers}
          rowKey="id"
          loading={isLoading}
          pagination={{
            total: suppliers?.length || 0,
            pageSize: 10,
            showSizeChanger: true,
            showQuickJumper: true,
            showTotal: (total, range) => 
              `${range[0]}-${range[1]} / ${total} tedarikçi`,
          }}
          scroll={{ x: 1200 }}
          size="small"
          bordered
          rowClassName={(record) => 
            !record.is_active ? 'row-disabled' : ''
          }
        />
      </Card>

      {/* Form Modal */}
      <Modal
        title={editingSupplier ? 'Tedarikçi Düzenle' : 'Yeni Tedarikçi Ekle'}
        open={isFormModalVisible}
        onCancel={() => setIsFormModalVisible(false)}
        footer={null}
        width={800}
        
      >
        <SupplierForm 
          supplier={editingSupplier || undefined}
          onSuccess={onFormSuccess}
          onCancel={() => setIsFormModalVisible(false)}
        />
      </Modal>

      {/* View Modal */}
      <Modal
        title="Tedarikçi Detayları"
        open={isViewModalVisible}
        onCancel={() => setIsViewModalVisible(false)}
        footer={[
          <Button key="close" onClick={() => setIsViewModalVisible(false)}>
            Kapat
          </Button>,
          <Button key="edit" type="primary" onClick={() => {
            setIsViewModalVisible(false)
            handleEdit(viewingSupplier!)
          }}>
            Düzenle
          </Button>
        ]}
        width={600}
      >
        {viewingSupplier && (
          <div>
            <Row gutter={16}>
              <Col span={12}>
                <Card size="small" title="Temel Bilgiler">
                  <p><strong>Firma:</strong> {viewingSupplier.name}</p>
                  <p><strong>İletişim Kişisi:</strong> {viewingSupplier.contact_person || '-'}</p>
                  <p><strong>Telefon:</strong> {viewingSupplier.phone || '-'}</p>
                  <p><strong>E-mail:</strong> {viewingSupplier.email || '-'}</p>
                  <p><strong>Durum:</strong> <Tag color={viewingSupplier.is_active ? 'green' : 'red'}>
                    {viewingSupplier.is_active ? 'Aktif' : 'Pasif'}
                  </Tag></p>
                </Card>
              </Col>
              <Col span={12}>
                <Card size="small" title="Diğer Bilgiler">
                  <p><strong>Adres:</strong> {viewingSupplier.address || '-'}</p>
                  <p><strong>Vergi No:</strong> {viewingSupplier.tax_number || '-'}</p>
                  <p><strong>Kayıt:</strong> {dayjs(viewingSupplier.created_at).format('DD/MM/YYYY HH:mm')}</p>
                  <p><strong>Güncellenme:</strong> {dayjs(viewingSupplier.updated_at).format('DD/MM/YYYY HH:mm')}</p>
                </Card>
              </Col>
            </Row>
            
            {viewingSupplier.additional_info && (
              <Card size="small" title="Ticari Bilgiler" style={{ marginTop: 16 }}>
                <Row gutter={16}>
                  <Col span={8}>
                    <p><strong>Teslimat:</strong> {viewingSupplier.additional_info.delivery_time || '-'}</p>
                  </Col>
                  <Col span={8}>
                    <p><strong>İndirim:</strong> {viewingSupplier.additional_info.discount_rate || '-'}</p>
                  </Col>
                  <Col span={8}>
                    <p><strong>Ödeme:</strong> {viewingSupplier.additional_info.payment_terms || '-'}</p>
                  </Col>
                </Row>
              </Card>
            )}
          </div>
        )}
      </Modal>

      <style>{`
        .row-disabled {
          background-color: #f5f5f5;
          opacity: 0.7;
        }
        .ant-table-small .ant-table-tbody > tr > td {
          padding: 8px;
        }
      `}</style>
    </div>
  )
}