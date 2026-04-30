import React from 'react'
import { 
  Form, 
  Input, 
  InputNumber, 
  Select, 
  DatePicker, 
  Switch, 
  Button, 
  Row, 
  Col,
  Divider,
  Space
} from 'antd'
import { 
  PlusOutlined
} from '@ant-design/icons'
import dayjs from 'dayjs'
import { useAuthStore } from '@/Stores/authStore'
import { useSuppliers } from '../../supplier/Hooks/useSuppliers'
import { useClinics } from '../../clinics/Hooks/useClinics'
import { UNIT_OPTIONS, CURRENCY_OPTIONS } from '../constants/stockConstants'

const { Option } = Select

interface BatchFormProps {
  productId: number
  onSuccess?: () => void
  onCancel?: () => void
  isSubmitting?: boolean
  onSubmit: (values: any) => void
  lockedClinicId?: number | null
}

export const BatchForm: React.FC<BatchFormProps> = ({ 
  productId, 
  onSuccess, 
  onCancel,
  isSubmitting,
  onSubmit,
  lockedClinicId
}) => {
  const [form] = Form.useForm()
  const { suppliers, isLoading: isSuppliersLoading } = useSuppliers()
  const { clinics, isLoading: isClinicsLoading } = useClinics()
  const user = useAuthStore(state => state.user)

  const handleFinish = (values: any) => {
    onSubmit({
      ...values,
      product_id: productId,
      purchase_date: values.purchase_date?.format('YYYY-MM-DD'),
      expiry_date: values.expiry_date?.format('YYYY-MM-DD'),
    })
  }

  return (
    <Form
      form={form}
      layout="vertical"
      onFinish={handleFinish}
      initialValues={{
        purchase_date: dayjs(),
        currency: 'TRY',
        is_active: true,
        track_expiry: true,
        expiry_yellow_days: 30,
        expiry_red_days: 10,
        clinic_id: lockedClinicId || user?.clinic_id
      }}
    >
      <Row gutter={16}>
        <Col span={12}>
          <Form.Item
            label="Tedarikçi"
            name="supplier_id"
            rules={[{ required: true, message: 'Tedarikçi seçimi gereklidir' }]}
          >
            <Select placeholder="Tedarikçi seçin" loading={isSuppliersLoading}>
              {(suppliers ?? []).map(s => <Option key={s.id} value={s.id}>{s.name}</Option>)}
            </Select>
          </Form.Item>
        </Col>
        <Col span={12}>
          <Form.Item
            label="Klinik"
            name="clinic_id"
            rules={[{ required: true, message: 'Klinik seçimi gereklidir' }]}
          >
            <Select 
              placeholder="Klinik seçin" 
              loading={isClinicsLoading}
              disabled={!!lockedClinicId}
            >
              {(clinics ?? []).map(c => <Option key={c.id} value={c.id}>{c.name}</Option>)}
            </Select>
          </Form.Item>
        </Col>
      </Row>

      <Row gutter={16}>
        <Col span={8}>
          <Form.Item
            label="Miktar"
            name="current_stock"
            rules={[{ required: true }]}
          >
            <InputNumber style={{ width: '100%' }} min={1} />
          </Form.Item>
        </Col>
        <Col span={8}>
          <Form.Item
            label="Birim Fiyat"
            name="purchase_price"
            rules={[{ required: true }]}
          >
            <InputNumber style={{ width: '100%' }} min={0} precision={2} />
          </Form.Item>
        </Col>
        <Col span={8}>
          <Form.Item
            label="Para Birimi"
            name="currency"
            rules={[{ required: true }]}
          >
            <Select>
              {CURRENCY_OPTIONS.map(o => <Option key={o.value} value={o.value}>{o.label}</Option>)}
            </Select>
          </Form.Item>
        </Col>
      </Row>

      <Row gutter={16}>
        <Col span={12}>
          <Form.Item
            label="Alış Tarihi"
            name="purchase_date"
            rules={[{ required: true }]}
          >
            <DatePicker style={{ width: '100%' }} format="DD/MM/YYYY" />
          </Form.Item>
        </Col>
        <Col span={12}>
          <Form.Item
            label="S.K.T"
            name="expiry_date"
          >
            <DatePicker style={{ width: '100%' }} format="DD/MM/YYYY" />
          </Form.Item>
        </Col>
      </Row>

      <Row gutter={16}>
        <Col span={12}>
          <Form.Item
            label="SKT Sarı Alarm (Gün)"
            name="expiry_yellow_days"
            tooltip="Son kullanma tarihine kaç gün kala sarı uyarı verilsin?"
          >
            <InputNumber style={{ width: '100%' }} min={1} placeholder="Örn: 30" />
          </Form.Item>
        </Col>
        <Col span={12}>
          <Form.Item
            label="SKT Kritik Alarm (Gün)"
            name="expiry_red_days"
            tooltip="Son kullanma tarihine kaç gün kala kırmızı uyarı verilsin?"
          >
            <InputNumber style={{ width: '100%' }} min={1} placeholder="Örn: 10" />
          </Form.Item>
        </Col>
      </Row>

      <Form.Item
        label="Depolama Konumu"
        name="storage_location"
      >
        <Input placeholder="Örn: Raf A-1" />
      </Form.Item>

      <Divider titlePlacement="left" style={{ margin: '8px 0' }}>Birim & Alt Birim Ayarları</Divider>
      
      <Row gutter={16}>
        <Col span={24}>
          <Form.Item name="has_sub_unit" valuePropName="checked">
            <Switch 
              checkedChildren="Alt Birim Var" 
              unCheckedChildren="Alt Birim Yok" 
              onChange={(checked) => {
                if(!checked) {
                  form.setFieldsValue({ sub_unit_name: undefined, sub_unit_multiplier: undefined })
                }
              }}
            />
          </Form.Item>
        </Col>
      </Row>

      <Form.Item noStyle shouldUpdate={(prevValues, currentValues) => prevValues.has_sub_unit !== currentValues.has_sub_unit}>
        {({ getFieldValue }) => getFieldValue('has_sub_unit') ? (
          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                label="Alt Birim Adı"
                name="sub_unit_name"
                rules={[{ required: true, message: 'Örn: Adet, Doz' }]}
              >
                <Input placeholder="Örn: Adet" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                label="Alt Birim Çarpanı"
                name="sub_unit_multiplier"
                rules={[{ required: true, message: '1 kutuda kaç adet var?' }]}
                tooltip="Örneğin 1 kutu eldivende 100 adet varsa buraya 100 yazın."
              >
                <InputNumber style={{ width: '100%' }} min={1} />
              </Form.Item>
            </Col>
          </Row>
        ) : null}
      </Form.Item>

      <Form.Item style={{ marginBottom: 0, textAlign: 'right' }}>
        <Space>
          <Button onClick={onCancel}>İptal</Button>
          <Button type="primary" htmlType="submit" loading={isSubmitting}>
            Stok Girişi Yap
          </Button>
        </Space>
      </Form.Item>
    </Form>
  )
}
