import React, { useEffect, useState } from 'react'
import { Form, Input, InputNumber, Select, Button, Space, Switch, DatePicker, Row, Col, Tabs } from 'antd'
import { Product } from '../Types/stock.types'
import { useStockFormLogic } from '../Hooks/useStockFormLogic'
import { useClinics } from '../../clinics/Hooks/useClinics'
import { useSuppliers } from '../../supplier/Hooks/useSuppliers'
import { useCategories } from '../../category/Hooks/useCategories'
import dayjs from 'dayjs'

interface ProductFormProps {
  onSuccess: () => void
  onCancel: () => void
  initialValues?: Product | null
}

export const ProductForm: React.FC<ProductFormProps> = ({ onSuccess, onCancel, initialValues }) => {
  const [form] = Form.useForm()
  const [activeTab, setActiveTab] = useState('1')
  const { clinics, isLoading: isClinicsLoading } = useClinics()
  const { suppliers, isLoading: isSuppliersLoading } = useSuppliers()
  const { categories, isLoading: isCategoriesLoading } = useCategories()
  
  const { handleSubmit, isCreating, isUpdating } = useStockFormLogic({ 
    editingStock: initialValues, 
    onSuccess 
  })

  const hasExpiry = Form.useWatch('has_expiration_date', form)
  const hasSubUnit = Form.useWatch('has_sub_unit', form)

  useEffect(() => {
    if (initialValues) {
      const vals = initialValues as any;
      const values = {
        ...initialValues,
        expiry_date: vals.expiry_date ? dayjs(vals.expiry_date) : null,
        purchase_date: vals.purchase_date ? dayjs(vals.purchase_date) : null,
      };
      form.setFieldsValue(values);
    } else {
      form.resetFields();
    }
  }, [initialValues, form]);

  const onFinish = async (values: any) => {
    if (values.expiry_date && typeof values.expiry_date !== 'string') {
      values.expiry_date = values.expiry_date.format('YYYY-MM-DD')
    }
    if (values.purchase_date && typeof values.purchase_date !== 'string') {
      values.purchase_date = values.purchase_date.format('YYYY-MM-DD')
    }
    await handleSubmit(values)
  }

  const items = [
    {
      key: '1',
      label: 'Ürün Bilgileri',
      children: (
        <div style={{ paddingTop: 16 }}>
          <Row gutter={16}>
            <Col span={12}>
              <Form.Item name="name" label="Ürün Adı" rules={[{ required: true }]}>
                <Input placeholder="Örn: Latex Eldiven" />
              </Form.Item>
            </Col>
            <Col span={6}>
              <Form.Item name="sku" label="SKU / Barkod">
                <Input placeholder="ELD-001" />
              </Form.Item>
            </Col>
            <Col span={6}>
              <Form.Item name="brand" label="Marka">
                <Input placeholder="DentiMaster" />
              </Form.Item>
            </Col>
          </Row>
          <Row gutter={16}>
            <Col span={8}>
              <Form.Item name="unit" label="Birim" rules={[{ required: true }]}>
                <Select>
                  <Select.Option value="adet">Adet</Select.Option>
                  <Select.Option value="kutu">Kutu</Select.Option>
                  <Select.Option value="paket">Paket</Select.Option>
                  <Select.Option value="gram">Gram</Select.Option>
                </Select>
              </Form.Item>
            </Col>
            <Col span={8}>
              <Form.Item name="category" label="Kategori">
                <Select loading={isCategoriesLoading}>
                  {categories?.map((cat: any) => <Select.Option key={cat.id} value={cat.name}>{cat.name}</Select.Option>)}
                </Select>
              </Form.Item>
            </Col>
            <Col span={8}>
              <Form.Item name="has_expiration_date" label="SKT Takibi?" valuePropName="checked">
                <Switch checkedChildren="Evet" unCheckedChildren="Hayır" />
              </Form.Item>
            </Col>
          </Row>
          <Form.Item name="description" label="Açıklama">
            <Input.TextArea rows={2} placeholder="Ürün hakkında kısa bilgi..." />
          </Form.Item>
        </div>
      )
    },
    {
      key: '2',
      label: 'Stok & Birim Ayarları',
      children: (
        <div style={{ paddingTop: 16 }}>
          <Row gutter={16}>
            <Col span={8}>
              <Form.Item name="clinic_id" label="Klinik" rules={[{ required: true }]}>
                <Select loading={isClinicsLoading} placeholder="Seçiniz">
                  {clinics?.map(c => <Select.Option key={c.id} value={c.id}>{c.name}</Select.Option>)}
                </Select>
              </Form.Item>
            </Col>
            <Col span={8}>
              <Form.Item name="supplier_id" label="Tedarikçi" rules={[{ required: true }]}>
                <Select loading={isSuppliersLoading} placeholder="Seçiniz">
                  {suppliers?.map(s => <Select.Option key={s.id} value={s.id}>{s.name}</Select.Option>)}
                </Select>
              </Form.Item>
            </Col>
            <Col span={8}>
              <Form.Item name="storage_location" label="Depolama Konumu">
                <Input placeholder="Raf A-1" />
              </Form.Item>
            </Col>
          </Row>
          <Row gutter={16}>
            <Col span={6}>
              <Form.Item name="initial_stock" label="Miktar" rules={[{ required: true }]}>
                <InputNumber min={0} style={{ width: '100%' }} />
              </Form.Item>
            </Col>
            <Col span={6}>
              <Form.Item name="purchase_date" label="Alış Tarihi">
                <DatePicker style={{ width: '100%' }} format="DD/MM/YYYY" />
              </Form.Item>
            </Col>
            <Col span={6}>
              <Form.Item name="purchase_price" label="Alış Fiyatı">
                <InputNumber min={0} style={{ width: '100%' }} precision={2} />
              </Form.Item>
            </Col>
            <Col span={6}>
              <Form.Item name="currency" label="Döviz">
                <Select>
                  <Select.Option value="TRY">₺ (TL)</Select.Option>
                  <Select.Option value="USD">$ (USD)</Select.Option>
                  <Select.Option value="EUR">€ (EUR)</Select.Option>
                </Select>
              </Form.Item>
            </Col>
          </Row>
          <Row gutter={16} align="middle">
            <Col span={8}>
              <Form.Item name="has_sub_unit" label="Alt Birim (Kutu içi vb.)?" valuePropName="checked">
                <Switch checkedChildren="Var" unCheckedChildren="Yok" />
              </Form.Item>
            </Col>
            {hasSubUnit && (
              <>
                <Col span={8}>
                  <Form.Item name="sub_unit_name" label="Alt Birim Adı" rules={[{ required: true, message: 'Örn: Adet' }]}>
                    <Input placeholder="Örn: Adet" />
                  </Form.Item>
                </Col>
                <Col span={8}>
                  <Form.Item name="sub_unit_multiplier" label="Çarpan (1 Kutu = ? Adet)" rules={[{ required: true }]}>
                    <InputNumber min={1} style={{ width: '100%' }} />
                  </Form.Item>
                </Col>
              </>
            )}
          </Row>
          {hasExpiry && (
            <Row gutter={16}>
              <Col span={24}>
                <Form.Item name="expiry_date" label="Son Kullanma Tarihi" rules={[{ required: true }]}>
                  <DatePicker style={{ width: '100%' }} format="DD/MM/YYYY" />
                </Form.Item>
              </Col>
            </Row>
          )}
        </div>
      )
    },
    {
      key: '3',
      label: 'Uyarı Seviyeleri',
      children: (
        <div style={{ paddingTop: 16 }}>
          <Row gutter={16}>
            <Col span={12}>
              <Form.Item name="min_stock_level" label="Sarı Alarm (Düşük)" tooltip="Sarı uyarı seviyesi">
                <InputNumber style={{ width: '100%' }} min={0} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item name="critical_stock_level" label="Kırmızı Alarm (Kritik)" tooltip="Kırmızı uyarı seviyesi">
                <InputNumber style={{ width: '100%' }} min={0} />
              </Form.Item>
            </Col>
          </Row>
        </div>
      )
    }
  ]

  return (
    <Form
      form={form}
      layout="vertical"
      onFinish={onFinish}
      initialValues={{
        unit: 'adet',
        min_stock_level: 10,
        critical_stock_level: 5,
        is_active: true,
        has_expiration_date: false,
        has_sub_unit: false,
        initial_stock: 0,
        currency: 'TRY',
        purchase_date: dayjs()
      }}
    >
      <Tabs activeKey={activeTab} onChange={setActiveTab} items={items} />

      <Form.Item style={{ marginBottom: 0, textAlign: 'right', marginTop: 24 }}>
        <Space>
          <Button onClick={onCancel}>İptal</Button>
          <Button type="primary" htmlType="submit" loading={isCreating || isUpdating} size="large">
            {initialValues ? 'Güncelle' : 'Ürünü Kaydet'}
          </Button>
        </Space>
      </Form.Item>
    </Form>
  )
}
