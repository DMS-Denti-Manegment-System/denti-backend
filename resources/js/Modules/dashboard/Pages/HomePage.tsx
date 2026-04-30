// src/modules/dashboard/Pages/HomePage.tsx

import React from 'react'
import { Card, Row, Col, Statistic, Typography, Divider, Skeleton } from 'antd'
import { 
  UserOutlined, 
  MedicineBoxOutlined, 
  DatabaseOutlined, 
  HomeOutlined,
  SmileOutlined
} from '@ant-design/icons'
import { useDashboard } from '../Hooks/useDashboard'

const { Title, Text } = Typography

export const HomePage: React.FC = () => {
  const { data: stats, isLoading } = useDashboard()

  if (isLoading) {
    return <Skeleton active paragraph={{ rows: 10 }} />
  }

  return (
    <div style={{ padding: '24px', maxWidth: '1200px', margin: '0 auto' }}>
      <div style={{ marginBottom: '40px', textAlign: 'center' }}>
        <Title level={1} style={{ marginBottom: '8px' }}>
          Hoş Geldiniz, <span style={{ color: '#1890ff' }}>{stats?.company_name}</span>
        </Title>
        <Text type="secondary" style={{ fontSize: '18px' }}>
          Denti Klinik Yönetim Paneli ile her şey kontrolünüz altında.
        </Text>
      </div>

      <Divider titlePlacement="left">Genel İstatistikler</Divider>

      <Row gutter={[24, 24]}>
        <Col xs={24} sm={12} lg={6}>
          <Card variant="borderless" className="dashboard-card">
            <Statistic
              title="Toplam Doktor"
              value={stats?.total_doctors}
              prefix={<MedicineBoxOutlined style={{ color: '#1890ff' }} />}
              styles={{ content: { color: '#1890ff' } }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card variant="borderless" className="dashboard-card">
            <Statistic
              title="Toplam Çalışan"
              value={stats?.total_employees}
              prefix={<UserOutlined style={{ color: '#52c41a' }} />}
              styles={{ content: { color: '#52c41a' } }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card variant="borderless" className="dashboard-card">
            <Statistic
              title="Toplam Klinik"
              value={stats?.total_clinics}
              prefix={<HomeOutlined style={{ color: '#faad14' }} />}
              styles={{ content: { color: '#faad14' } }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card variant="borderless" className="dashboard-card">
            <Statistic
              title="Stok Kalemi"
              value={stats?.total_stock_items}
              prefix={<DatabaseOutlined style={{ color: '#eb2f96' }} />}
              styles={{ content: { color: '#eb2f96' } }}
            />
          </Card>
        </Col>
      </Row>

      <div style={{ marginTop: '60px', textAlign: 'center' }}>
        <Card variant="borderless" style={{ background: '#f0f2f5', borderRadius: '16px' }}>
          <SmileOutlined style={{ fontSize: '48px', color: '#1890ff', marginBottom: '16px' }} />
          <Title level={3}>Mutlu Gülüşler, Profesyonel Yönetim</Title>
          <Text type="secondary">
            {stats?.company_name} bünyesindeki tüm süreçlerinizi dijitalleştirerek hastalarınıza en iyi hizmeti sunmaya odaklanın.
          </Text>
        </Card>
      </div>

      <style>{`
        .dashboard-card {
          box-shadow: 0 4px 12px rgba(0,0,0,0.05);
          transition: transform 0.3s ease;
          border-radius: 12px;
        }
        .dashboard-card:hover {
          transform: translateY(-5px);
        }
      `}</style>
    </div>
  )
}
