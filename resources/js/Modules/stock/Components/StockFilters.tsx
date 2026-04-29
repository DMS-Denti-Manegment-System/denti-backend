// src/modules/stock/Components/StockFilters.tsx

import React from 'react'
import { Card, Row, Col, Input, Select, Button, Space } from 'antd'
import { PlusOutlined, TagsOutlined, BarcodeOutlined } from '@ant-design/icons'
import { router } from '@inertiajs/react'
import { StockFilter } from '../Types/stock.types'
import { useCategories } from '@/Modules/category/Hooks/useCategories'
import { useClinics } from '@/Modules/clinics/Hooks/useClinics'

const { Search } = Input
const { Option } = Select

interface StockFiltersProps {
  onSearch: (value: string) => void
  onFilterChange: (field: keyof StockFilter, value: string | number | undefined) => void
  onAdd: () => void
  onScannerOpen: () => void
}

export const StockFilters: React.FC<StockFiltersProps> = ({
  onSearch,
  onFilterChange,
  onAdd,
  onScannerOpen,
}) => {
  
  const { categories, isLoading: isCategoriesLoading } = useCategories()
  const { clinics, isLoading: isClinicsLoading } = useClinics()

  const levelOptions = [
    { label: 'Normal', value: 'normal' },
    { label: 'Düşük Stok (Sarı)', value: 'low' },
    { label: 'Kritik Stok (Kırmızı)', value: 'critical' },
    { label: 'Yaklaşan SKT (Sarı)', value: 'near_expiry' },
    { label: 'Kritik SKT (Kırmızı)', value: 'critical_expiry' },
    { label: 'Süresi Geçmiş', value: 'expired' }
  ]

  return (
    <Card style={{ marginBottom: 24 }}>
      <Row gutter={[16, 16]} align="middle">
        <Col xs={24} md={5}>
          <Search
            placeholder="Stok adı ile ara..."
            onSearch={onSearch}
            style={{ width: '100%' }}
            allowClear
          />
        </Col>

        <Col xs={12} md={4}>
          <Select
            placeholder="Klinik Seçin"
            style={{ width: '100%' }}
            allowClear
            loading={isClinicsLoading}
            onChange={(value) => onFilterChange('clinic_id', value)}
          >
            {(clinics ?? []).map(clinic => (
              <Option key={clinic.id} value={clinic.id}>
                {clinic.name}
              </Option>
            ))}
          </Select>
        </Col>
        
        <Col xs={12} md={3}>
          <Select
            placeholder="Kategori"
            style={{ width: '100%' }}
            allowClear
            loading={isCategoriesLoading}
            onChange={(value) => onFilterChange('category', value)}
          >
            {(categories ?? []).map(option => (
              <Option key={option.id} value={option.name}>
                {option.name}
              </Option>
            ))}
          </Select>
        </Col>
        
        <Col xs={12} md={3}>
          <Select
            placeholder="Seviye"
            style={{ width: '100%' }}
            allowClear
            onChange={(value) => onFilterChange('level', value)}
          >
            {levelOptions.map(option => (
              <Option key={option.value} value={option.value}>
                {option.label}
              </Option>
            ))}
          </Select>
        </Col>
        
        <Col xs={12} md={3}>
          <Select
            placeholder="Durum"
            style={{ width: '100%' }}
            allowClear
            onChange={(value) => onFilterChange('status', value)}
          >
            <Option value="active">Aktif</Option>
            <Option value="inactive">Pasif</Option>
            <Option value="expired">Süresi Geçmiş</Option>
          </Select>
        </Col>
        
        <Col xs={24} md={6} style={{ textAlign: 'right' }}>
          <Space>
            <Button 
              icon={<TagsOutlined />} 
              onClick={() => router.visit('/stock-categories')}
            >
              Kategoriler
            </Button>
            <Button 
              icon={<BarcodeOutlined />} 
              onClick={onScannerOpen}
              style={{ backgroundColor: '#f0f5ff', color: '#1d39c4', borderColor: '#adc6ff' }}
            >
              Barkodla Kullanım
            </Button>
            <Button 
              type="primary" 
              icon={<PlusOutlined />} 
              onClick={onAdd}
            >
              Yeni Stok
            </Button>
          </Space>
        </Col>
      </Row>
    </Card>
  )
}