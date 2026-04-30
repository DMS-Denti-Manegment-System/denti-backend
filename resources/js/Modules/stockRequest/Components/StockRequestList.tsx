// src/modules/stockRequest/Components/StockRequestList.tsx

import React, { useState, useMemo } from 'react'
import { 
  Card,
  Row, 
  Col, 
  Input, 
  Select, 
  Button,
  Space,
  Empty,
  Spin,
  Typography,
  DatePicker,
  Statistic,
  Divider,
  Badge
} from 'antd'
import { 
  FilterOutlined,
  ReloadOutlined,
  PlusOutlined,
  SwapOutlined,
  ClockCircleOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  DownloadOutlined
} from '@ant-design/icons'
import dayjs from 'dayjs'
import { useStockRequests, useStockRequestStats } from '../Hooks/useStockRequests'
import { useClinics } from '@/Modules/clinics/Hooks/useClinics'
import { StockRequestTable } from './StockRequestTable'
import { StockRequestForm } from './StockRequestForm'
import { StockRequestFilters, StockRequestStatus } from '../Types/stockRequest.types'

const { Search } = Input
const { Option } = Select
const { RangePicker } = DatePicker
const { Title } = Typography

interface StockRequestListProps {
  defaultClinicId?: number
  showCreateForm?: boolean
  currentUser: string
}

export const StockRequestList: React.FC<StockRequestListProps> = ({ 
  defaultClinicId,
  showCreateForm = true,
  currentUser 
}) => {
  const [filters, setFilters] = useState<StockRequestFilters>({
    clinic_id: defaultClinicId
  })
  const [showForm, setShowForm] = useState(false)

  const { stockRequests, isLoading, refetch } = useStockRequests(filters)
  const { data: stats } = useStockRequestStats(defaultClinicId)
  const { clinics } = useClinics()

  const activeClinics = useMemo(() => 
    clinics?.filter(clinic => clinic.is_active) || [], 
    [clinics]
  )

  const handleFilterChange = (key: keyof StockRequestFilters, value: string | number | undefined) => {
    setFilters(prev => ({
      ...prev,
      [key]: value
    }))
  }

  const handleDateRangeChange = (dates: [dayjs.Dayjs | null, dayjs.Dayjs | null] | null) => {
    if (dates && dates.length === 2 && dates[0] && dates[1]) {
      setFilters(prev => ({
        ...prev,
        start_date: dates[0] ? dates[0].format('YYYY-MM-DD') : undefined,
        end_date: dates[1] ? dates[1].format('YYYY-MM-DD') : undefined
      }))
    } else {
      setFilters(prev => ({
        ...prev,
        start_date: undefined,
        end_date: undefined
      }))
    }
  }

  const clearFilters = () => {
    setFilters({
      clinic_id: defaultClinicId
    })
  }

  const getFilteredRequestCount = () => {
    if (!stockRequests) return 0
    return stockRequests.length
  }

  const statsCards = [
    {
      title: 'Bekleyen',
      value: stats?.pending || 0,
      icon: <ClockCircleOutlined />,
      color: '#faad14'
    },
    {
      title: 'Onaylanan',
      value: stats?.approved || 0,
      icon: <CheckCircleOutlined />,
      color: '#1890ff'
    },
    {
      title: 'Reddedilen',
      value: stats?.rejected || 0,
      icon: <CloseCircleOutlined />,
      color: '#ff4d4f'
    },
    {
      title: 'Tamamlanan',
      value: stats?.completed || 0,
      icon: <DownloadOutlined />,
      color: '#52c41a'
    }
  ]

  return (
    <div>
      {/* İstatistik Kartları */}
      {stats && (
        <Row gutter={16} style={{ marginBottom: 24 }}>
          {statsCards.map((stat, index) => (
            <Col xs={12} sm={6} key={index}>
              <Card size="small">
                <Statistic
                  title={stat.title}
                  value={stat.value}
                  prefix={<span style={{ color: stat.color }}>{stat.icon}</span>}
                  styles={{ content: { color: stat.color } }}
                />
              </Card>
            </Col>
          ))}
        </Row>
      )}

      {/* Filtreler */}
      <Card size="small" style={{ marginBottom: 16 }}>
        <Row gutter={[16, 16]} align="middle">
          <Col xs={24} sm={8} md={6}>
            <Search
              placeholder="Stok adı veya talep eden ara..."
              allowClear
              value={filters.search}
              onChange={(e) => handleFilterChange('search', e.target.value)}
              onSearch={(value) => handleFilterChange('search', value)}
            />
          </Col>

          <Col xs={12} sm={8} md={4}>
            <Select
              placeholder="Klinik"
              allowClear
              style={{ width: '100%' }}
              value={filters.clinic_id}
              onChange={(value) => handleFilterChange('clinic_id', value)}
            >
              {activeClinics.map(clinic => (
                <Option key={clinic.id} value={clinic.id}>
                  {clinic.name}
                </Option>
              ))}
            </Select>
          </Col>

          <Col xs={12} sm={8} md={4}>
            <Select
              placeholder="Tip"
              allowClear
              style={{ width: '100%' }}
              value={filters.type}
              onChange={(value) => handleFilterChange('type', value)}
            >
              <Option value="sent">Gönderilen</Option>
              <Option value="received">Alınan</Option>
            </Select>
          </Col>

          <Col xs={12} sm={6} md={4}>
            <Select
              placeholder="Durum"
              allowClear
              style={{ width: '100%' }}
              value={filters.status}
              onChange={(value) => handleFilterChange('status', value as StockRequestStatus)}
            >
              <Option value="pending">
                <Badge status="warning" text="Bekleyen" />
              </Option>
              <Option value="approved">
                <Badge status="processing" text="Onaylanan" />
              </Option>
              <Option value="rejected">
                <Badge status="error" text="Reddedilen" />
              </Option>
              <Option value="completed">
                <Badge status="success" text="Tamamlanan" />
              </Option>
            </Select>
          </Col>

          <Col xs={12} sm={10} md={4}>
            <RangePicker
              style={{ width: '100%' }}
              format="DD/MM/YYYY"
              placeholder={['Başlangıç', 'Bitiş']}
              onChange={handleDateRangeChange}
            />
          </Col>

          <Col xs={24} sm={8} md={2}>
            <Space>
              <Button
                icon={<ReloadOutlined />}
                onClick={() => refetch()}
                loading={isLoading}
              />
              <Button
                icon={<FilterOutlined />}
                onClick={clearFilters}
                title="Filtreleri Temizle"
              />
            </Space>
          </Col>
        </Row>
      </Card>

      {/* Başlık ve Ekle Butonu */}
      <Row justify="space-between" align="middle" style={{ marginBottom: 16 }}>
        <Col>
          <Title level={4} style={{ margin: 0 }}>
            <SwapOutlined style={{ marginRight: 8, color: '#1890ff' }} />
            Stok Talepleri
            {getFilteredRequestCount() > 0 && (
              <span style={{ color: '#666', fontWeight: 'normal', fontSize: '14px' }}>
                {' '}({getFilteredRequestCount()} sonuç)
              </span>
            )}
          </Title>
        </Col>
        <Col>
          {showCreateForm && (
            <Button
              type="primary"
              icon={<PlusOutlined />}
              onClick={() => setShowForm(!showForm)}
            >
              {showForm ? 'Formu Gizle' : 'Yeni Talep'}
            </Button>
          )}
        </Col>
      </Row>

      {/* Yeni Talep Formu */}
      {showForm && showCreateForm && (
        <StockRequestForm
          defaultRequesterClinicId={defaultClinicId}
          onSuccess={() => {
            setShowForm(false)
            refetch()
          }}
        />
      )}

      {/* Talep Listesi */}
      <div>
        {isLoading ? (
          <Card>
            <div style={{ textAlign: 'center', padding: '40px' }}>
              <Spin size="large" />
              <div style={{ marginTop: 16 }}>Talepler yükleniyor...</div>
            </div>
          </Card>
        ) : !stockRequests || stockRequests.length === 0 ? (
          <Card>
            <Empty
              image={Empty.PRESENTED_IMAGE_SIMPLE}
              description={
                Object.keys(filters).length > 1 || filters.search || filters.status ? 
                "Filtrelere uygun talep bulunamadı" : 
                "Henüz stok talebi bulunmuyor"
              }
              style={{ padding: '40px' }}
            >
              {showCreateForm && (
                <Button 
                  type="primary" 
                  icon={<PlusOutlined />}
                  onClick={() => setShowForm(true)}
                >
                  İlk Talebi Oluştur
                </Button>
              )}
            </Empty>
          </Card>
        ) : (
          <Card styles={{ body: { padding: 0 } }}>
            <StockRequestTable
              requests={stockRequests}
              loading={isLoading}
              currentUser={currentUser}
              onRefresh={refetch}
            />
          </Card>
        )}
      </div>

      {/* Sayfa Alt Bilgisi */}
      {stockRequests && stockRequests.length > 0 && (
        <Card size="small" style={{ marginTop: 16, textAlign: 'center' }}>
          <Space>
            <span>Toplam {stockRequests.length} talep gösteriliyor</span>
            <Divider type="vertical" />
            <Button
              type="link"
              size="small"
              icon={<ReloadOutlined />}
              onClick={() => refetch()}
            >
              Yenile
            </Button>
          </Space>
        </Card>
      )}
    </div>
  )
}