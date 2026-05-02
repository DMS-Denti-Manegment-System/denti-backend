// src/modules/stockRequest/Components/StockRequestForm.tsx

import React, { useState, useEffect } from 'react'
import { 
  Card,
  Form, 
  Select, 
  InputNumber, 
  Input, 
  Button, 
  Row, 
  Col,
  Typography,
  Space,
  Alert,
  Tag
} from 'antd'
import { 
  PlusOutlined, 
  SwapOutlined,
  ShopOutlined,
  WarningOutlined
} from '@ant-design/icons'
import { useStockRequests } from '../Hooks/useStockRequests'
import { useClinics } from '@/Modules/clinics/Hooks/useClinics'
import { useStocks } from '@/Modules/stock/Hooks/useStocks'
import { useAuth } from '@/Modules/auth/Hooks/useAuth'
import { CreateStockRequestRequest } from '../Types/stockRequest.types'

const { Option } = Select
const { TextArea } = Input
const { Text } = Typography

interface StockRequestFormProps {
  onSuccess?: () => void
  defaultRequesterClinicId?: number
}

export const StockRequestForm: React.FC<StockRequestFormProps> = ({ 
  onSuccess,
  defaultRequesterClinicId
}) => {
  const { user } = useAuth()
  const [form] = Form.useForm()
  const { createStockRequest, isCreating } = useStockRequests()
  const { clinics: allClinics } = useClinics()
  const { stocks: allStocks } = useStocks()

  // Kullanıcının kliniğini varsayılan olarak ayarla
  const initialRequesterClinicId = defaultRequesterClinicId || user?.clinic_id

  const [selectedRequesterClinic, setSelectedRequesterClinic] = useState<number | undefined>(initialRequesterClinicId)
  const [selectedTargetClinic, setSelectedTargetClinic] = useState<number | undefined>()
  const [selectedStock, setSelectedStock] = useState<number | undefined>()
  
  // ... existing state ...
  const [availableStocks, setAvailableStocks] = useState<Array<any>>([])
  const [selectedStockInfo, setSelectedStockInfo] = useState<any | null>(null)

  // Hedef klinik seçildiğinde o klinikteki stokları filtrele
  useEffect(() => {
    if (selectedTargetClinic && allStocks) {
      const clinicStocks = allStocks.filter((stock: any) => 
        stock.clinic_id === selectedTargetClinic && 
        stock.current_stock > 0
      )
      setAvailableStocks(clinicStocks)
      setSelectedStock(undefined)
      setSelectedStockInfo(null)
      form.setFieldValue('stock_id', undefined)
      form.setFieldValue('requested_quantity', undefined)
    } else {
      setAvailableStocks([])
    }
  }, [selectedTargetClinic, allStocks, form])

  // Stok seçildiğinde bilgilerini göster
  useEffect(() => {
    if (selectedStock && availableStocks) {
      const stock = availableStocks.find(s => s.id === selectedStock)
      setSelectedStockInfo(stock || null)
      
      // Maksimum talep edilebilir miktarı hesapla (mevcut stokun %50'si)
      if (stock) {
        const maxRequestable = Math.floor(stock.current_stock * 0.5)
        form.setFieldValue('requested_quantity', Math.min(10, maxRequestable))
      }
    } else {
      setSelectedStockInfo(null)
    }
  }, [selectedStock, availableStocks, form])

  const onFinish = async (values: Omit<CreateStockRequestRequest, 'requested_by'>) => {
    try {
      await createStockRequest({
        ...values,
        requested_by: user?.name || 'Sistem Kullanıcısı'
      })
      form.resetFields()
      setSelectedRequesterClinic(initialRequesterClinicId)
      setSelectedTargetClinic(undefined)
      setSelectedStock(undefined)
      setSelectedStockInfo(null)
      onSuccess?.()
    } catch (error) {
      console.error('Form submission error:', error)
    }
  }

  const activeClinics = allClinics?.filter(clinic => clinic.is_active) || []

  const getStockLevelColor = (stock: typeof selectedStockInfo) => {
    if (!stock) return '#d9d9d9'
    if (stock.current_stock <= stock.critical_stock_level) return '#ff4d4f'
    if (stock.current_stock <= stock.min_stock_level) return '#faad14'
    return '#52c41a'
  }

  const getMaxRequestable = (stock: typeof selectedStockInfo) => {
    if (!stock) return 0
    // Mevcut stokun maksimum %50'si talep edilebilir
    return Math.floor(stock.current_stock * 0.5)
  }

  return (
    <Card title="Yeni Stok Talebi Oluştur" style={{ marginBottom: 24 }}>
      <Form
        form={form}
        layout="vertical"
        onFinish={onFinish}
        autoComplete="off"
        initialValues={{
          requester_clinic_id: initialRequesterClinicId
        }}
      >
        <Row gutter={16}>
          {/* Talep Eden Klinik */}
          <Col xs={24} md={12}>
            <Form.Item
              label="Talep Eden Klinik"
              name="requester_clinic_id"
              rules={[{ required: true, message: 'Talep eden klinik seçiniz!' }]}
            >
              <Select
                placeholder="Klinik seçin"
                showSearch
                optionFilterProp="children"
                onChange={setSelectedRequesterClinic}
                disabled={Boolean(user?.clinic_id && !user?.roles?.some((r: any) => r.name === 'Super Admin'))}
              >
                {activeClinics.map(clinic => (
                  <Option key={clinic.id} value={clinic.id}>
                    <Space>
                      <ShopOutlined />
                      <span>{clinic.name}</span>
                    </Space>
                  </Option>
                ))}
              </Select>
            </Form.Item>
          </Col>

          {/* Talep Edilen Klinik */}
          <Col xs={24} md={12}>
            <Form.Item
              label="Talep Edilen Klinik"
              name="requested_from_clinic_id"
              rules={[{ required: true, message: 'Talep edilen klinik seçiniz!' }]}
            >
              <Select
                placeholder="Klinik seçin"
                showSearch
                optionFilterProp="children"
                onChange={setSelectedTargetClinic}
                disabled={!selectedRequesterClinic}
              >
                {activeClinics
                  .filter(clinic => clinic.id !== selectedRequesterClinic)
                  .map(clinic => (
                  <Option key={clinic.id} value={clinic.id}>
                    <Space>
                      <ShopOutlined />
                      <span>{clinic.name}</span>
                    </Space>
                  </Option>
                ))}
              </Select>
            </Form.Item>
          </Col>
        </Row>

        {/* Transfer Direction Visual */}
        {selectedRequesterClinic && selectedTargetClinic && (
          <Row justify="center" style={{ margin: '16px 0' }}>
            <Col>
              <Space align="center" size="large">
                <Tag color="green" icon={<ShopOutlined />}>
                  {activeClinics.find((c: any) => c.id === selectedTargetClinic)?.name}
                </Tag>
                <SwapOutlined style={{ fontSize: '20px', color: '#1890ff' }} />
                <Tag color="blue" icon={<ShopOutlined />}>
                  {activeClinics.find((c: any) => c.id === selectedRequesterClinic)?.name}
                </Tag>
              </Space>
            </Col>
          </Row>
        )}

        {/* Stok Seçimi */}
        <Form.Item
          label="Stok Ürünü"
          name="stock_id"
          rules={[{ required: true, message: 'Stok ürünü seçiniz!' }]}
        >
          <Select
            placeholder="Önce hedef klinik seçin"
            showSearch
            optionFilterProp="children"
            onChange={setSelectedStock}
            disabled={!selectedTargetClinic}
            loading={Boolean(selectedTargetClinic && availableStocks.length === 0)}
            notFoundContent={
              selectedTargetClinic ? (
                availableStocks.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '20px' }}>
                    <WarningOutlined style={{ color: '#faad14', fontSize: '20px' }} />
                    <div>Bu klinikten talep edilebilir stok bulunamadı</div>
                  </div>
                ) : null
              ) : "Önce hedef klinik seçin"
            }
          >
            {availableStocks.map(stock => (
              <Option key={stock.id} value={stock.id}>
                <Row justify="space-between" align="middle">
                  <Col>
                    <Space direction="vertical" size={0}>
                      <Text strong>{stock.name}</Text>
                      <Space size="small">
                        <Tag color="purple">{stock.category}</Tag>
                        {stock.brand && <Tag color="cyan">{stock.brand}</Tag>}
                      </Space>
                    </Space>
                  </Col>
                  <Col>
                    <Tag color={getStockLevelColor(stock)}>
                      {stock.current_stock} {stock.unit}
                    </Tag>
                  </Col>
                </Row>
              </Option>
            ))}
          </Select>
        </Form.Item>

        {/* Seçilen Stok Bilgileri */}
        {selectedStockInfo && (
          <Alert
            style={{ marginBottom: 16 }}
            type="info"
            showIcon
            message="Stok Bilgileri"
            description={
              <Space direction="vertical" size={4}>
                <Text>
                  <strong>Mevcut Stok:</strong> {selectedStockInfo.current_stock} {selectedStockInfo.unit}
                </Text>
                <Text>
                  <strong>Minimum Seviye:</strong> {selectedStockInfo.min_stock_level} {selectedStockInfo.unit}
                </Text>
                <Text>
                  <strong>Maksimum Talep Edilebilir:</strong> {getMaxRequestable(selectedStockInfo)} {selectedStockInfo.unit}
                </Text>
                {selectedStockInfo.storage_location && (
                  <Text>
                    <strong>Konum:</strong> {selectedStockInfo.storage_location}
                  </Text>
                )}
              </Space>
            }
          />
        )}

        <Row gutter={16}>
          {/* Talep Miktarı */}
          <Col xs={24} md={12}>
            <Form.Item
              label="Talep Miktarı"
              name="requested_quantity"
              rules={[
                { required: true, message: 'Talep miktarı gereklidir!' },
                { type: 'number', min: 1, message: 'Miktar 0\'dan büyük olmalıdır!' },
                ...(selectedStockInfo ? [{
                  type: 'number' as const, 
                  max: getMaxRequestable(selectedStockInfo), 
                  message: `Maksimum ${getMaxRequestable(selectedStockInfo)} ${selectedStockInfo.unit} talep edilebilir!`
                }] : [])
              ]}
            >
              <InputNumber
                style={{ width: '100%' }}
                placeholder="Miktar girin"
                addonAfter={selectedStockInfo?.unit || 'adet'}
                disabled={!selectedStock}
              />
            </Form.Item>
          </Col>

          {/* Boş alan */}
          <Col xs={24} md={12} />
        </Row>

        {/* Talep Sebebi */}
        <Form.Item
          label="Talep Sebebi"
          name="request_reason"
          rules={[
            { required: true, message: 'Talep sebebi gereklidir!' },
            { min: 10, message: 'Talep sebebi en az 10 karakter olmalıdır!' }
          ]}
        >
          <TextArea
            rows={3}
            placeholder="Neden bu stok talebinde bulunuyorsunuz? Detaylı açıklama yazın..."
          />
        </Form.Item>

        {/* Submit Button */}
        <Form.Item style={{ marginBottom: 0, textAlign: 'right' }}>
          <Button 
            type="primary" 
            htmlType="submit" 
            icon={<PlusOutlined />}
            loading={isCreating}
            size="large"
          >
            Talep Oluştur
          </Button>
        </Form.Item>
      </Form>
    </Card>
  )
}