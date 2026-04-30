// src/modules/alerts/Components/AlertDashboard.tsx

import React from 'react'
import { 
  Row, 
  Col, 
  Card, 
  Statistic, 
  Progress,
  Space,
  Typography,
  Alert as AntAlert,
  Spin,
  Tooltip
} from 'antd'
import { 
  BellOutlined,
  FireOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  ExclamationCircleOutlined,
  WarningOutlined,
  InfoCircleOutlined
} from '@ant-design/icons'
import { useAlertStats, useActiveAlerts } from '../Hooks/useAlerts'
import { AlertSeverityBadge } from './AlertSeverityBadge'
import { AlertTypeBadge } from './AlertTypeBadge'
import { AlertType } from '../Types/alert.types' // ✅ AlertType import eklendi

const { Text } = Typography

interface AlertDashboardProps {
  clinicId?: number
}

export const AlertDashboard: React.FC<AlertDashboardProps> = ({ clinicId }) => {
  const { data: stats, isLoading: statsLoading } = useAlertStats(clinicId)
  const { data: activeAlerts, isLoading: alertsLoading } = useActiveAlerts(clinicId)

  if (statsLoading || alertsLoading) {
    return (
      <Card>
        <div style={{ textAlign: 'center', padding: '40px' }}>
          <Spin size="large" />
          <div style={{ marginTop: 16 }}>Uyarı verileri yükleniyor...</div>
        </div>
      </Card>
    )
  }

  const criticalAlerts = activeAlerts?.filter(alert => alert.severity === 'critical') || []
  const highAlerts = activeAlerts?.filter(alert => alert.severity === 'high') || []

  return (
    <div>
      {/* Kompakt Bildirim Hapları */}
      <div style={{ marginBottom: 16 }}>
        <Space size={[8, 8]} wrap>
          {criticalAlerts.length > 0 && (
            <Tooltip title="Hemen müdahale edilmesi gereken kritik uyarılar!">
              <AntAlert
                message={`${criticalAlerts.length} Kritik Uyarı`}
                type="error"
                showIcon
                icon={<FireOutlined />}
                style={{ padding: '4px 12px', borderRadius: '20px' }}
              />
            </Tooltip>
          )}
          
          {highAlerts.length > 0 && (
            <Tooltip title="Dikkat edilmesi gereken yüksek öncelikli uyarılar.">
              <AntAlert
                message={`${highAlerts.length} Yüksek Öncelik`}
                type="warning"
                showIcon
                icon={<WarningOutlined />}
                style={{ padding: '4px 12px', borderRadius: '20px' }}
              />
            </Tooltip>
          )}
        </Space>
      </div>

      {/* İstatistik Kartları */}
      <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
        <Col xs={24} sm={8}>
          <Card styles={{ body: { padding: '16px' } }}>
            <Statistic
              title="Toplam Aktif Uyarı"
              value={stats?.total_active || 0}
              prefix={<ExclamationCircleOutlined style={{ color: '#fa8c16' }} />}
              styles={{ content: { color: '#fa8c16' } }}
            />
          </Card>
        </Col>
        
        <Col xs={12} sm={8}>
          <Card 
            styles={{ body: { padding: '16px' } }}
            style={(stats?.low_stock || 0) > 0 ? { 
              border: '1px solid #1890ff', 
              boxShadow: '0 0 8px rgba(24, 144, 255, 0.2)' 
            } : {}}
          >
            <Statistic
              title="Düşük Stok"
              value={stats?.low_stock || 0}
              prefix={<InfoCircleOutlined style={{ color: '#1890ff' }} />}
              styles={{ content: { color: '#1890ff' } }}
            />
          </Card>
        </Col>

        <Col xs={12} sm={8}>
          <Card 
            styles={{ body: { padding: '16px' } }}
            style={(stats?.critical_stock || 0) > 0 ? { 
              border: '1px solid #ff4d4f', 
              boxShadow: '0 0 8px rgba(255, 77, 79, 0.2)' 
            } : {}}
          >
            <Statistic
              title="Kritik Stok"
              value={stats?.critical_stock || 0}
              prefix={<FireOutlined style={{ color: '#ff4d4f' }} />}
              styles={{ content: { color: '#ff4d4f' } }}
            />
          </Card>
        </Col>
      </Row>

      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        <Col xs={12} sm={12}>
          <Card 
            styles={{ body: { padding: '16px' } }}
            style={(stats?.near_expiry || 0) > 0 ? { 
              border: '1px solid #faad14', 
              boxShadow: '0 0 8px rgba(250, 173, 20, 0.2)' 
            } : {}}
          >
            <Statistic
              title="Son Kullanması Yaklaşan"
              value={stats?.near_expiry || 0}
              prefix={<WarningOutlined style={{ color: '#faad14' }} />}
              styles={{ content: { color: '#faad14' } }}
            />
          </Card>
        </Col>
        
        <Col xs={12} sm={12}>
          <Card 
            styles={{ body: { padding: '16px' } }}
            style={(stats?.expired || 0) > 0 ? { 
              border: '1px solid #595959', 
              boxShadow: '0 0 8px rgba(89, 89, 89, 0.2)' 
            } : {}}
          >
            <Statistic
              title="Süresi Geçmiş"
              value={stats?.expired || 0}
              prefix={<CloseCircleOutlined style={{ color: '#595959' }} />}
              styles={{ content: { color: '#595959' } }}
            />
          </Card>
        </Col>
      </Row>
    </div>
  )
}