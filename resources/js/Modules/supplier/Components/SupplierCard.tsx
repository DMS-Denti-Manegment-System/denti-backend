// src/modules/supplier/Components/SupplierCard.tsx

import React, { useState } from 'react'
import { 
  Card, 
  Tag, 
  Button, 
  Space, 
  Tooltip, 
  Modal, 
  Typography, 
  Descriptions,
  Dropdown,
  Divider
} from 'antd'
import { 
  EditOutlined, 
  DeleteOutlined, 
  MoreOutlined,
  PhoneOutlined,
  MailOutlined,
  UserOutlined,
  HomeOutlined,
  InfoCircleOutlined
} from '@ant-design/icons'
import dayjs from 'dayjs'
import { Supplier } from '../Types/supplier.types'

const { Text, Title } = Typography

interface SupplierCardProps {
  supplier: Supplier
  onEdit?: (supplier: Supplier) => void
  onDelete?: (id: number) => void
}

export const SupplierCard: React.FC<SupplierCardProps> = ({
  supplier,
  onEdit,
  onDelete
}) => {
  const [detailModalVisible, setDetailModalVisible] = useState(false)

  const menuItems = [
    {
      key: 'edit',
      label: 'Düzenle',
      icon: <EditOutlined />,
      onClick: () => onEdit?.(supplier)
    },
    {
      key: 'delete',
      label: 'Sil',
      icon: <DeleteOutlined />,
      danger: true,
      onClick: () => onDelete?.(supplier.id)
    }
  ]

  return (
    <>
      <Card
        hoverable
        style={{ 
          width: '100%',
          borderLeft: `4px solid ${supplier.is_active ? '#52c41a' : '#ff4d4f'}`
        }}
        actions={[
          <Tooltip title="Detayları Görüntüle">
            <Button 
              type="text" 
              icon={<InfoCircleOutlined />}
              onClick={() => setDetailModalVisible(true)}
            />
          </Tooltip>,
          <Tooltip title="Düzenle">
            <Button 
              type="text" 
              icon={<EditOutlined />}
              onClick={() => onEdit?.(supplier)}
            />
          </Tooltip>,
          <Dropdown menu={{ items: menuItems }} placement="bottomRight">
            <Button type="text" icon={<MoreOutlined />} />
          </Dropdown>
        ]}
      >
        <div style={{ marginBottom: 12 }}>
          <Space direction="vertical" style={{ width: '100%' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
              <Title level={5} style={{ margin: 0, flex: 1 }}>
                {supplier.name}
              </Title>
              <Tag color={supplier.is_active ? 'green' : 'red'}>
                {supplier.is_active ? 'Aktif' : 'Pasif'}
              </Tag>
            </div>
          </Space>
        </div>

        <Space direction="vertical" style={{ width: '100%' }} size="small">
          {supplier.contact_person && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <UserOutlined style={{ color: '#1890ff' }} />
              <Text>{supplier.contact_person}</Text>
            </div>
          )}

          {supplier.phone && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <PhoneOutlined style={{ color: '#52c41a' }} />
              <Text>{supplier.phone}</Text>
            </div>
          )}

          {supplier.email && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <MailOutlined style={{ color: '#faad14' }} />
              <Text>{supplier.email}</Text>
            </div>
          )}

          {supplier.address && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <HomeOutlined style={{ color: '#722ed1' }} />
              <Text style={{ fontSize: '12px' }}>{supplier.address}</Text>
            </div>
          )}

          {supplier.additional_info?.delivery_time && (
            <div style={{ marginTop: 8 }}>
              <Tag color="blue">
                Teslimat: {supplier.additional_info.delivery_time}
              </Tag>
            </div>
          )}
        </Space>
      </Card>

      <Modal
        title="Tedarikçi Detayları"
        open={detailModalVisible}
        onCancel={() => setDetailModalVisible(false)}
        footer={[
          <Button key="close" onClick={() => setDetailModalVisible(false)}>
            Kapat
          </Button>,
          <Button key="edit" type="primary" onClick={() => onEdit?.(supplier)}>
            Düzenle
          </Button>
        ]}
        width={600}
      >
        <Descriptions column={2} bordered size="small">
          <Descriptions.Item label="Firma Adı" span={2}>{supplier.name}</Descriptions.Item>
          <Descriptions.Item label="İletişim Kişisi">{supplier.contact_person || '-'}</Descriptions.Item>
          <Descriptions.Item label="Telefon">{supplier.phone || '-'}</Descriptions.Item>
          <Descriptions.Item label="E-mail" span={2}>{supplier.email || '-'}</Descriptions.Item>
          <Descriptions.Item label="Adres" span={2}>{supplier.address || '-'}</Descriptions.Item>
          <Descriptions.Item label="Vergi No">{supplier.tax_number || '-'}</Descriptions.Item>
          <Descriptions.Item label="Durum">
            <Tag color={supplier.is_active ? 'green' : 'red'}>
              {supplier.is_active ? 'Aktif' : 'Pasif'}
            </Tag>
          </Descriptions.Item>
        </Descriptions>

        {supplier.additional_info && (
          <>
            <Divider titlePlacement="left">Ek Bilgiler</Divider>
            <Descriptions column={1} bordered size="small">
              {supplier.additional_info.delivery_time && (
                <Descriptions.Item label="Teslimat Süresi">
                  {supplier.additional_info.delivery_time}
                </Descriptions.Item>
              )}
              {supplier.additional_info.discount_rate && (
                <Descriptions.Item label="İndirim Oranı">
                  {supplier.additional_info.discount_rate}
                </Descriptions.Item>
              )}
              {supplier.additional_info.payment_terms && (
                <Descriptions.Item label="Ödeme Koşulları">
                  {supplier.additional_info.payment_terms}
                </Descriptions.Item>
              )}
            </Descriptions>
          </>
        )}

        <Divider titlePlacement="left">Sistem Bilgileri</Divider>
        <Descriptions column={2} bordered size="small">
          <Descriptions.Item label="Oluşturulma">
            {dayjs(supplier.created_at).format('DD/MM/YYYY HH:mm')}
          </Descriptions.Item>
          <Descriptions.Item label="Son Güncelleme">
            {dayjs(supplier.updated_at).format('DD/MM/YYYY HH:mm')}
          </Descriptions.Item>
        </Descriptions>
      </Modal>
    </>
  )
}