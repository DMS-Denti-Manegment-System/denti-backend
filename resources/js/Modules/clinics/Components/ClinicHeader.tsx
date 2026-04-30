// src/modules/clinics/Components/ClinicHeader.tsx

import React from 'react'
import { Row, Col, Typography, Button, Space, Statistic, Card, Skeleton } from 'antd'
import { PlusOutlined, ReloadOutlined, ShopOutlined } from '@ant-design/icons'

const { Title } = Typography

interface ClinicHeaderProps {
  onCreateClick: () => void
  onRefresh: () => void
  loading?: boolean
  totalClinics?: number
  activeClinics?: number
  statsLoading?: boolean
}

export const ClinicHeader: React.FC<ClinicHeaderProps> = ({
  onCreateClick,
  onRefresh,
  loading = false,
  totalClinics = 0,
  activeClinics = 0,
  statsLoading = false
}) => {
  return (
    <div style={{ marginBottom: 24 }}>
      {/* Ana Başlık ve Butonlar */}
      <Row justify="space-between" align="middle" style={{ marginBottom: 16 }}>
        <Col>
          <Space align="center">
            <ShopOutlined style={{ fontSize: '24px', color: '#1890ff' }} />
            <Title level={2} style={{ margin: 0 }}>
              Klinik Yönetimi
            </Title>
          </Space>
        </Col>
        <Col>
          <Space>
            <Button 
              icon={<ReloadOutlined />}
              onClick={onRefresh}
              loading={loading}
              disabled={loading}
            >
              Yenile
            </Button>
            <Button 
              type="primary" 
              icon={<PlusOutlined />}
              onClick={onCreateClick}
              size="large"
              disabled={loading}
            >
              Yeni Klinik Ekle
            </Button>
          </Space>
        </Col>
      </Row>

      {/* İstatistik Kartları */}
      <Row gutter={16}>
        <Col xs={24} sm={8} md={6}>
          <Card size="small">
            {statsLoading ? (
              <Skeleton active paragraph={{ rows: 1 }} />
            ) : (
              <Statistic
                title="Toplam Klinik"
                value={totalClinics}
                prefix={<ShopOutlined />}
                valueStyle={{ color: '#1890ff' }}
              />
            )}
          </Card>
        </Col>
        <Col xs={24} sm={8} md={6}>
          <Card size="small">
            {statsLoading ? (
              <Skeleton active paragraph={{ rows: 1 }} />
            ) : (
              <Statistic
                title="Aktif Klinik"
                value={activeClinics}
                prefix={<ShopOutlined />}
                valueStyle={{ color: '#52c41a' }}
              />
            )}
          </Card>
        </Col>
        <Col xs={24} sm={8} md={6}>
          <Card size="small">
            {statsLoading ? (
              <Skeleton active paragraph={{ rows: 1 }} />
            ) : (
              <Statistic
                title="Pasif Klinik"
                value={totalClinics - activeClinics}
                prefix={<ShopOutlined />}
                styles={{ content: { color: '#ff4d4f' } }}
              />
            )}
          </Card>
        </Col>
        <Col xs={24} sm={8} md={6}>
          <Card size="small">
            {statsLoading ? (
              <Skeleton active paragraph={{ rows: 1 }} />
            ) : (
              <Statistic
                title="Aktiflik Oranı"
                value={totalClinics > 0 ? Math.round((activeClinics / totalClinics) * 100) : 0}
                suffix="%"
                prefix={<ShopOutlined />}
                styles={{ 
                  content: {
                    color: totalClinics > 0 && (activeClinics / totalClinics) > 0.8 
                      ? '#52c41a' 
                      : totalClinics > 0 && (activeClinics / totalClinics) > 0.5 
                      ? '#faad14' 
                      : '#ff4d4f' 
                  }
                }}
              />
            )}
          </Card>
        </Col>
      </Row>
    </div>
  )
}