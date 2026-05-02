// src/modules/clinics/Components/ClinicDetail.tsx

import React from 'react'
import { 
  Row, 
  Col, 
  Card, 
  Descriptions, 
  Tag, 
  Avatar, 
  Statistic, 
  Typography, 
  Space, 
  Button,
  Alert,
  Skeleton,
  Empty,
  Table
} from 'antd'
import {
  ShopOutlined,
  PhoneOutlined,
  MailOutlined,
  EnvironmentOutlined,
  UserOutlined,
  GlobalOutlined,
  ClockCircleOutlined,
  EditOutlined,
  CalendarOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  BarChartOutlined,
  InboxOutlined,
  ExclamationCircleOutlined
} from '@ant-design/icons'
import { Clinic } from '../Types/clinic.types'
import { Stock as BaseStock } from '../../stock/Types/stock.types'
import { useClinicStocks, useClinicSummary } from '../Hooks/useClinics'

// Extend the base Stock interface with required fields
interface LocalStock extends BaseStock {
  unit_price?: number
}

const { Title, Text, Paragraph } = Typography

interface ClinicDetailProps {
  clinic: Clinic
  onEdit?: (clinic: Clinic) => void
}

export const ClinicDetail: React.FC<ClinicDetailProps> = ({ 
  clinic, 
  onEdit
}) => {
  // Hooks for additional data
  const { 
    data: stocks, 
    isLoading: stocksLoading, 
    error: stocksError 
  } = useClinicStocks(clinic.id)
  
  const { 
    data: summary, 
    isLoading: summaryLoading, 
    error: summaryError 
  } = useClinicSummary(clinic.id)

  // Stock columns for table
  const stockColumns = [
    {
      title: 'Ürün Adı',
      dataIndex: 'name',
      key: 'name',
      render: (text: string) => <strong>{text || 'İsimsiz Ürün'}</strong>
    },
    {
      title: 'Kategori',
      dataIndex: 'category',
      key: 'category',
      render: (category: string) => <Tag color="blue">{category || 'Kategorisiz'}</Tag>
    },
    {
      title: 'Stok Miktarı',
      dataIndex: 'current_stock',
      key: 'current_stock',
      render: (quantity: number, record: LocalStock) => {
        const qty = quantity || 0
        const minStock = record?.min_stock_level || 0
        return (
          <Tag color={qty > minStock ? 'green' : qty > 0 ? 'orange' : 'red'}>
            {qty} {record.unit || ''}
          </Tag>
        )
      }
    },
    {
      title: 'Min. Stok',
      dataIndex: 'min_stock_level',
      key: 'min_stock_level',
      render: (minStock: number) => minStock || 0
    },
    {
      title: 'Birim Fiyat',
      dataIndex: 'purchase_price',
      key: 'purchase_price',
      render: (price: number) => {
        if (price === null || price === undefined || isNaN(price)) {
          return <span style={{ color: '#999' }}>Fiyat yok</span>
        }
        return `₺${price.toFixed(2)}`
      }
    }
  ]

  return (
    <div>
      {/* Klinik Başlık Kartı */}
      <Card style={{ marginBottom: 16 }}>
        <Row gutter={16} align="middle">
          <Col flex="auto">
            <Space size="large" align="start">
              <Avatar 
                src={clinic.logo_url} 
                icon={<ShopOutlined />}
                size={80}
              />
              <div>
                <Title level={3} style={{ margin: 0, marginBottom: 8 }}>
                  {clinic.name}
                </Title>
                <Space wrap>
                  <Tag color={clinic.is_active ? 'green' : 'red'}>
                    {clinic.is_active ? (
                      <>
                        <CheckCircleOutlined /> Aktif
                      </>
                    ) : (
                      <>
                        <CloseCircleOutlined /> Pasif
                      </>
                    )}
                  </Tag>
                  <Tag color="purple">
                    <EnvironmentOutlined /> {clinic.city}, {clinic.district}
                  </Tag>
                </Space>
                <Paragraph 
                  style={{ marginTop: 8, marginBottom: 0, color: '#666' }}
                  ellipsis={{ rows: 2 }}
                >
                  {clinic.description || 'Açıklama bulunmuyor.'}
                </Paragraph>
              </div>
            </Space>
          </Col>
          <Col>
            <Space direction="vertical">
              {onEdit && (
                <Button 
                  type="primary" 
                  icon={<EditOutlined />}
                  onClick={() => onEdit(clinic)}
                >
                  Düzenle
                </Button>
              )}
              <Text type="secondary" style={{ fontSize: '12px' }}>
                <CalendarOutlined /> Oluşturulma: {new Date(clinic.created_at).toLocaleDateString('tr-TR')}
              </Text>
            </Space>
          </Col>
        </Row>
      </Card>

      <Row gutter={16}>
        {/* Sol Kolon - Temel Bilgiler */}
        <Col xs={24} lg={12}>
          {/* İletişim Bilgileri */}
          <Card 
            title={
              <Space>
                <PhoneOutlined />
                İletişim Bilgileri
              </Space>
            }
            style={{ marginBottom: 16 }}
          >
            <Descriptions column={1} size="small">
              <Descriptions.Item label="Telefon">
                <Space>
                  <PhoneOutlined />
                  <a href={`tel:${clinic.phone}`}>{clinic.phone}</a>
                </Space>
              </Descriptions.Item>
              
              {clinic.email && (
                <Descriptions.Item label="E-mail">
                  <Space>
                    <MailOutlined />
                    <a href={`mailto:${clinic.email}`}>{clinic.email}</a>
                  </Space>
                </Descriptions.Item>
              )}
              
              {clinic.manager_name && (
                <Descriptions.Item label="Yönetici">
                  <Space>
                    <UserOutlined />
                    {clinic.manager_name}
                  </Space>
                </Descriptions.Item>
              )}
              
              {clinic.website && (
                <Descriptions.Item label="Website">
                  <Space>
                    <GlobalOutlined />
                    <a href={clinic.website} target="_blank" rel="noopener noreferrer">
                      {clinic.website}
                    </a>
                  </Space>
                </Descriptions.Item>
              )}
            </Descriptions>
          </Card>

          {/* Adres Bilgileri */}
          <Card 
            title={
              <Space>
                <EnvironmentOutlined />
                Adres Bilgileri
              </Space>
            }
            style={{ marginBottom: 16 }}
          >
            <Descriptions column={1} size="small">
              <Descriptions.Item label="Şehir">{clinic.city}</Descriptions.Item>
              <Descriptions.Item label="İlçe">{clinic.district}</Descriptions.Item>
              {clinic.postal_code && (
                <Descriptions.Item label="Posta Kodu">{clinic.postal_code}</Descriptions.Item>
              )}
              <Descriptions.Item label="Adres">
                <Paragraph copyable={{ text: clinic.address }}>
                  {clinic.address}
                </Paragraph>
              </Descriptions.Item>
            </Descriptions>
            
          </Card>

          {/* Çalışma Saatleri */}
          {clinic.opening_hours && (
            <Card 
              title={
                <Space>
                  <ClockCircleOutlined />
                  Çalışma Saatleri
                </Space>
              }
              style={{ marginBottom: 16 }}
            >
              <Paragraph style={{ whiteSpace: 'pre-line' }}>
                {clinic.opening_hours}
              </Paragraph>
            </Card>
          )}
        </Col>

        {/* Sağ Kolon - İstatistikler ve Stoklar */}
        <Col xs={24} lg={12}>
          {/* Stok Özeti */}
          <Card 
            title={
              <Space>
                <BarChartOutlined />
                Stok Özeti
              </Space>
            }
            style={{ marginBottom: 16 }}
          >
            {summaryLoading ? (
              <Skeleton active paragraph={{ rows: 3 }} />
            ) : summaryError ? (
              <Alert 
                message="Stok özeti yüklenemedi" 
                type="error" 
                showIcon 
              />
            ) : summary ? (
              <Row gutter={16}>
                <Col span={12}>
                  <Statistic
                    title="Toplam Ürün"
                    value={summary.total_products}
                    prefix={<InboxOutlined />}
                    styles={{ content: { color: '#1890ff' } }}
                  />
                </Col>
                <Col span={12}>
                  <Statistic
                    title="Toplam Değer"
                    value={summary.total_stock_value || 0}
                    prefix="₺"
                    precision={2}
                    styles={{ content: { color: '#52c41a' } }}
                  />
                </Col>
                <Col span={12} style={{ marginTop: 16 }}>
                  <Statistic
                    title="Düşük Stok"
                    value={summary.low_stock_products}
                    prefix={<ExclamationCircleOutlined />}
                    styles={{ content: { color: '#faad14' } }}
                  />
                </Col>
                <Col span={12} style={{ marginTop: 16 }}>
                  <Statistic
                    title="Stok Yok"
                    value={summary.out_of_stock_products}
                    prefix={<CloseCircleOutlined />}
                    styles={{ content: { color: '#ff4d4f' } }}
                  />
                </Col>
              </Row>
            ) : (
              <Empty description="Stok özeti bulunamadı" />
            )}
          </Card>

          {/* Kategori Dağılımı */}
          {summary?.categories && summary.categories.length > 0 && (
            <Card 
              title="Kategori Dağılımı"
              style={{ marginBottom: 16 }}
            >
              <Space direction="vertical" style={{ width: '100%' }}>
                {summary.categories.map((category, index) => (
                  <Row key={index} justify="space-between" align="middle">
                    <Col>
                      <Tag color="blue">{category.category_name}</Tag>
                    </Col>
                    <Col>
                      <Space>
                        <Text>{category.product_count || 0} ürün</Text>
                        <Text strong>₺{(category.stock_value || 0).toFixed(2)}</Text>
                      </Space>
                    </Col>
                  </Row>
                ))}
              </Space>
            </Card>
          )}
        </Col>
      </Row>

      {/* Stok Detayları Tablosu */}
      <Card 
        title={
          <Space>
            <InboxOutlined />
            Stok Detayları
          </Space>
        }
        style={{ marginTop: 16 }}
      >
        {stocksLoading ? (
          <Skeleton active paragraph={{ rows: 5 }} />
        ) : stocksError ? (
          <Alert 
            message="Stok verileri yüklenemedi" 
            type="error" 
            showIcon 
          />
        ) : stocks && stocks.length > 0 ? (
          <Table<LocalStock>
            columns={stockColumns}
            dataSource={stocks}
            rowKey="id"
            pagination={{
              pageSize: 10,
              showSizeChanger: true,
              showTotal: (total) => `Toplam ${total} ürün`
            }}
            size="small"
          />
        ) : (
          <Empty description="Bu klinikde henüz stok kaydı bulunmuyor" />
        )}
      </Card>
    </div>
  )
}