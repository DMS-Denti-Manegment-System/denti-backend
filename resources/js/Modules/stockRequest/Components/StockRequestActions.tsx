// src/modules/stockRequest/Components/StockRequestActions.tsx

import React, { useState } from 'react'
import { 
  Button, 
  Popconfirm, 
  Modal, 
  Form, 
  Input, 
  InputNumber, 
  Space,
  Typography,
  Divider
} from 'antd'
import { 
  CheckOutlined, 
  CloseOutlined, 
  DownloadOutlined,
  ExclamationCircleOutlined 
} from '@ant-design/icons'
import { useStockRequests } from '../Hooks/useStockRequests'
import { StockRequest } from '../Types/stockRequest.types'

const { TextArea } = Input
const { Text } = Typography

interface StockRequestActionsProps {
  request: StockRequest
  currentUser: string // Şu anki kullanıcı adı
  size?: 'small' | 'default'
  onSuccess?: () => void
}

export const StockRequestActions: React.FC<StockRequestActionsProps> = ({ 
  request, 
  currentUser,
  size = 'default',
  onSuccess
}) => {
  const { 
    approveStockRequest, 
    rejectStockRequest, 
    completeStockRequest,
    isApproving,
    isRejecting,
    isCompleting
  } = useStockRequests()

  const [approveModalVisible, setApproveModalVisible] = useState(false)
  const [rejectModalVisible, setRejectModalVisible] = useState(false)
  const [approveForm] = Form.useForm()
  const [rejectForm] = Form.useForm()

  const handleApprove = async (values: { approved_quantity: number; notes?: string }) => {
    try {
      await approveStockRequest({
        id: request.id,
        data: {
          approved_quantity: values.approved_quantity,
          approved_by: currentUser,
          notes: values.notes
        }
      })
      setApproveModalVisible(false)
      approveForm.resetFields()
      onSuccess?.()
    } catch (error) {
      console.error('Approve error:', error)
    }
  }

  const handleReject = async (values: { rejection_reason: string }) => {
    try {
      await rejectStockRequest({
        id: request.id,
        data: {
          rejection_reason: values.rejection_reason,
          rejected_by: currentUser
        }
      })
      setRejectModalVisible(false)
      rejectForm.resetFields()
      onSuccess?.()
    } catch (error) {
      console.error('Reject error:', error)
    }
  }

  const handleComplete = async () => {
    try {
      await completeStockRequest({
        id: request.id,
        data: {
          performed_by: currentUser
        }
      })
      onSuccess?.()
    } catch (error) {
      console.error('Complete error:', error)
    }
  }

  const buttonSize = size === 'small' ? 'small' : 'middle'

  // Sadece bekleyen talepler için onay/red butonları
  if (request.status === 'pending') {
    return (
      <>
        <Space size="small">
          <Button
            type="primary"
            icon={<CheckOutlined />}
            size={buttonSize}
            onClick={() => {
              approveForm.setFieldValue('approved_quantity', request.requested_quantity)
              setApproveModalVisible(true)
            }}
            loading={isApproving}
          >
            Onayla
          </Button>
          <Button
            danger
            icon={<CloseOutlined />}
            size={buttonSize}
            onClick={() => setRejectModalVisible(true)}
            loading={isRejecting}
          >
            Reddet
          </Button>
        </Space>

        {/* Onay Modal */}
        <Modal
          title="Talebi Onayla"
          open={approveModalVisible}
          onCancel={() => setApproveModalVisible(false)}
          footer={null}
          width={500}
        >
          <Form
            form={approveForm}
            layout="vertical"
            onFinish={handleApprove}
            initialValues={{
              approved_quantity: request.requested_quantity
            }}
          >
            <Text type="secondary">
              <strong>{request.requester_clinic?.name}</strong> klinikten 
              <strong> {request.stock?.name}</strong> talebi
            </Text>
            <Divider />
            
            <Form.Item
              label="Talep Edilen Miktar"
              name="requested_quantity"
              initialValue={request.requested_quantity}
            >
              <InputNumber
                disabled
                addonAfter={request.stock?.unit}
                style={{ width: '100%' }}
              />
            </Form.Item>

            <Form.Item
              label="Onaylanacak Miktar"
              name="approved_quantity"
              rules={[
                { required: true, message: 'Onaylanacak miktar gereklidir!' },
                { type: 'number', min: 1, message: 'Miktar 0\'dan büyük olmalıdır!' },
                { 
                  type: 'number', 
                  max: request.requested_quantity, 
                  message: 'Talep edilen miktardan fazla olamaz!' 
                }
              ]}
            >
              <InputNumber
                addonAfter={request.stock?.unit}
                style={{ width: '100%' }}
                placeholder="Onaylanacak miktar"
              />
            </Form.Item>

            <Form.Item
              label="Notlar (Opsiyonel)"
              name="notes"
            >
              <TextArea
                rows={3}
                placeholder="Ek notlar varsa yazın..."
              />
            </Form.Item>

            <Form.Item style={{ marginBottom: 0, textAlign: 'right' }}>
              <Space>
                <Button onClick={() => setApproveModalVisible(false)}>
                  İptal
                </Button>
                <Button type="primary" htmlType="submit" loading={isApproving}>
                  Onayla
                </Button>
              </Space>
            </Form.Item>
          </Form>
        </Modal>

        {/* Red Modal */}
        <Modal
          title="Talebi Reddet"
          open={rejectModalVisible}
          onCancel={() => setRejectModalVisible(false)}
          footer={null}
          width={500}
        >
          <Form
            form={rejectForm}
            layout="vertical"
            onFinish={handleReject}
          >
            <Text type="secondary">
              <strong>{request.requester_clinic?.name}</strong> klinikten 
              <strong> {request.stock?.name}</strong> talebi reddediliyor
            </Text>
            <Divider />

            <Form.Item
              label="Red Sebebi"
              name="rejection_reason"
              rules={[
                { required: true, message: 'Red sebebi gereklidir!' },
                { min: 10, message: 'Red sebebi en az 10 karakter olmalıdır!' }
              ]}
            >
              <TextArea
                rows={4}
                placeholder="Red sebebini detaylı olarak açıklayın..."
              />
            </Form.Item>

            <Form.Item style={{ marginBottom: 0, textAlign: 'right' }}>
              <Space>
                <Button onClick={() => setRejectModalVisible(false)}>
                  İptal
                </Button>
                <Button type="primary" danger htmlType="submit" loading={isRejecting}>
                  Reddet
                </Button>
              </Space>
            </Form.Item>
          </Form>
        </Modal>
      </>
    )
  }

  // Onaylanan talepler için tamamlama butonu
  if (request.status === 'approved') {
    return (
      <Popconfirm
        title="Transferi Tamamla"
        description={`${request.approved_quantity} ${request.stock?.unit} transfer edilsin mi?`}
        onConfirm={handleComplete}
        okText="Evet"
        cancelText="Hayır"
        icon={<ExclamationCircleOutlined style={{ color: 'orange' }} />}
      >
        <Button
          type="primary"
          icon={<DownloadOutlined />}
          size={buttonSize}
          loading={isCompleting}
        >
          Transferi Tamamla
        </Button>
      </Popconfirm>
    )
  }

  // Diğer durumlar için hiçbir aksiyon gösterme
  return null
}
