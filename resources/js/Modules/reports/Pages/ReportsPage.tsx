// src/modules/reports/Pages/ReportsPage.tsx

import React from 'react'
import { Card, Result } from 'antd'
import { LineChartOutlined } from '@ant-design/icons'

export const ReportsPage: React.FC = () => {
  return (
    <div style={{ padding: '24px' }}>
      <Card 
        variant="borderless" 
        className="premium-card" 
        style={{ 
          height: 'calc(100vh - 120px)', 
          display: 'flex', 
          alignItems: 'center', 
          justifyContent: 'center' 
        }}
      >
        <Result
          icon={<LineChartOutlined style={{ fontSize: '48px', color: '#1890ff' }} />}
          title="Raporlar Modülü Hazırlanıyor"
          subTitle="Buraya raporlar gelecek."
        />
      </Card>
    </div>
  )
}

export default ReportsPage