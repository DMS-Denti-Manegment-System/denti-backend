// src/modules/clinics/Components/ClinicFilters.tsx

import React from 'react'
import { Row, Col, Input, Select, Button, Card, Space } from 'antd'
import { SearchOutlined, ClearOutlined } from '@ant-design/icons'
import { ClinicFilter } from '../Types/clinic.types'

const { Search } = Input
const { Option } = Select

interface ClinicFiltersProps {
  filters: ClinicFilter
  onFiltersChange: (filters: ClinicFilter) => void
  onClearFilters: () => void
  cities?: string[]
  loading?: boolean
}

export const ClinicFilters: React.FC<ClinicFiltersProps> = ({
  filters,
  onFiltersChange,
  onClearFilters,
  cities = [],
  loading = false
}) => {
  const handleSearch = (value: string) => {
    onFiltersChange({ ...filters, search: value })
  }

  const handleCityChange = (city: string) => {
    onFiltersChange({ 
      ...filters, 
      city: city === 'all' ? undefined : city 
    })
  }

  const handleStatusChange = (status: string) => {
    onFiltersChange({ 
      ...filters, 
      is_active: status === 'all' ? undefined : status === 'active' 
    })
  }

  const handleSortChange = (sortBy: string) => {
    onFiltersChange({ 
      ...filters, 
      sort_by: sortBy === 'default' ? undefined : sortBy as 'name' | 'city' | 'created_at'
    })
  }

  const handleSortOrderChange = (sortOrder: string) => {
    onFiltersChange({ 
      ...filters, 
      sort_order: sortOrder as 'asc' | 'desc'
    })
  }

  const hasActiveFilters = !!(
    filters.search || 
    filters.city || 
    filters.is_active !== undefined ||
    filters.sort_by
  )

  return (
    <Card size="small" style={{ marginBottom: 16 }}>
      <Row gutter={[16, 16]} align="middle">
        <Col xs={24} sm={12} md={8} lg={6}>
          <Search
            placeholder="Klinik adı, kod veya şehir ara..."
            allowClear
            enterButton={<SearchOutlined />}
            onSearch={handleSearch}
            defaultValue={filters.search}
            disabled={loading}
          />
        </Col>

        <Col xs={24} sm={12} md={6} lg={4}>
          <Select
            placeholder="Şehir Filtrele"
            style={{ width: '100%' }}
            allowClear
            value={filters.city || 'all'}
            onChange={handleCityChange}
            disabled={loading}
          >
            <Option value="all">Tüm Şehirler</Option>
            {cities.map(city => (
              <Option key={city} value={city}>
                {city}
              </Option>
            ))}
          </Select>
        </Col>

        <Col xs={24} sm={12} md={6} lg={4}>
          <Select
            placeholder="Durum Filtrele"
            style={{ width: '100%' }}
            value={
              filters.is_active === undefined 
                ? 'all' 
                : filters.is_active 
                ? 'active' 
                : 'inactive'
            }
            onChange={handleStatusChange}
            disabled={loading}
          >
            <Option value="all">Tüm Durumlar</Option>
            <Option value="active">Aktif</Option>
            <Option value="inactive">Pasif</Option>
          </Select>
        </Col>

        <Col xs={24} sm={12} md={6} lg={4}>
          <Select
            placeholder="Sırala"
            style={{ width: '100%' }}
            value={filters.sort_by || 'default'}
            onChange={handleSortChange}
            disabled={loading}
          >
            <Option value="default">Varsayılan</Option>
            <Option value="name">İsme Göre</Option>
            <Option value="city">Şehre Göre</Option>
            <Option value="created_at">Oluşturma Tarihine Göre</Option>
          </Select>
        </Col>

        <Col xs={24} sm={12} md={6} lg={3}>
          <Select
            style={{ width: '100%' }}
            value={filters.sort_order || 'asc'}
            onChange={handleSortOrderChange}
            disabled={loading || !filters.sort_by}
          >
            <Option value="asc">A-Z / Eskiden Yeniye</Option>
            <Option value="desc">Z-A / Yeniden Eskiye</Option>
          </Select>
        </Col>

        <Col xs={24} sm={12} md={4} lg={3}>
          <Space>
            {hasActiveFilters && (
              <Button
                icon={<ClearOutlined />}
                onClick={onClearFilters}
                disabled={loading}
              >
                Temizle
              </Button>
            )}
          </Space>
        </Col>
      </Row>

      {/* Aktif filtreler göstergesi */}
      {hasActiveFilters && (
        <Row style={{ marginTop: 8 }}>
          <Col span={24}>
            <Space wrap size="small">
              <span style={{ fontSize: '12px', color: '#666' }}>
                Aktif Filtreler:
              </span>
              {filters.search && (
                <span style={{ 
                  fontSize: '12px', 
                  background: '#f0f0f0', 
                  padding: '2px 6px', 
                  borderRadius: '4px' 
                }}>
                  Arama: "{filters.search}"
                </span>
              )}
              {filters.city && (
                <span style={{ 
                  fontSize: '12px', 
                  background: '#f0f0f0', 
                  padding: '2px 6px', 
                  borderRadius: '4px' 
                }}>
                  Şehir: {filters.city}
                </span>
              )}
              {filters.is_active !== undefined && (
                <span style={{ 
                  fontSize: '12px', 
                  background: '#f0f0f0', 
                  padding: '2px 6px', 
                  borderRadius: '4px' 
                }}>
                  Durum: {filters.is_active ? 'Aktif' : 'Pasif'}
                </span>
              )}
              {filters.sort_by && (
                <span style={{ 
                  fontSize: '12px', 
                  background: '#f0f0f0', 
                  padding: '2px 6px', 
                  borderRadius: '4px' 
                }}>
                  Sıralama: {filters.sort_by} ({filters.sort_order === 'desc' ? 'Azalan' : 'Artan'})
                </span>
              )}
            </Space>
          </Col>
        </Row>
      )}
    </Card>
  )
}