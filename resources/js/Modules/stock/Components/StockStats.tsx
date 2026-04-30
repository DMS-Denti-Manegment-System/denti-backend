// src/modules/stock/Components/StockStats.tsx

import React from 'react'
import { Card, Row, Col, Statistic } from 'antd'
import { 
  DatabaseOutlined,
  WarningOutlined,
  ExclamationCircleOutlined,
  DollarOutlined
} from '@ant-design/icons'

interface StockStatsData {
  total_items: number
  low_stock_items: number
  critical_stock_items: number
  expiring_items: number
  total_value: number
}

interface StockStatsProps {
  stats: StockStatsData | null
}

export const StockStats: React.FC<StockStatsProps> = ({ stats }) => {
  if (!stats) return null

  return (
    <Row gutter={16} style={{ marginBottom: 24 }}>
      <Col xs={12} md={6}>
        <Card styles={{ body: { padding: '16px' } }}>
          <Statistic 
            title="Ürün Adeti" 
            value={stats.total_items}
            prefix={<DatabaseOutlined />}
          />
        </Card>
      </Col>
      
      <Col xs={12} md={6}>
        <Card 
          styles={{ body: { padding: '16px' } }}
          style={stats.low_stock_items > 0 ? { 
            border: '1px solid #faad14', 
            boxShadow: '0 0 8px rgba(250, 173, 20, 0.2)' 
          } : {}}
        >
          <Statistic 
            title="Düşük Seviye" 
            value={stats.low_stock_items}
            valueStyle={{ color: '#faad14' }}
            prefix={<WarningOutlined />}
          />
        </Card>
      </Col>
      
      <Col xs={12} md={6}>
        <Card 
          styles={{ body: { padding: '16px' } }}
          style={stats.critical_stock_items > 0 ? { 
            border: '1px solid #ff4d4f', 
            boxShadow: '0 0 8px rgba(255, 77, 79, 0.2)' 
          } : {}}
        >
          <Statistic 
            title="Kritik Seviye" 
            value={stats.critical_stock_items}
            styles={{ content: { color: '#ff4d4f' } }}
            prefix={<ExclamationCircleOutlined />}
          />
        </Card>
      </Col>
      
      <Col xs={12} md={6}>
        <Card styles={{ body: { padding: '16px' } }}>
          <Statistic 
            title="Toplam Değer" 
            value={stats.total_value}
            precision={2}
            suffix="TL"
            prefix={<DollarOutlined />}
          />
        </Card>
      </Col>
    </Row>
  )
}