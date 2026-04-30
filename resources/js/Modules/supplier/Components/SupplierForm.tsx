// src/modules/supplier/Components/SupplierForm.tsx

import React, { useEffect } from 'react'
import { 
  Form, 
  Input, 
  Switch, 
  Button, 
  Row, 
  Col,
  Card,
  Divider
} from 'antd'
import { 
  PlusOutlined, 
  EditOutlined, 
  SaveOutlined,
  UserOutlined,
  PhoneOutlined,
  MailOutlined,
  NumberOutlined
} from '@ant-design/icons'
import { useSuppliers } from '../Hooks/useSuppliers'
import { CreateSupplierRequest, Supplier } from '../Types/supplier.types'

const { TextArea } = Input

// SupplierForm için interface
interface SupplierFormProps {
  supplier?: Supplier
  onSuccess?: () => void
  onCancel?: () => void
}

// SupplierForm için özel form values interface
interface SupplierFormValues {
  name: string
  contact_person?: string
  phone?: string
  email?: string
  address?: string
  tax_number?: string
  is_active: boolean
  delivery_time?: string
  discount_rate?: string
  payment_terms?: string
}

export const SupplierForm: React.FC<SupplierFormProps> = ({ 
  supplier, 
  onSuccess, 
  onCancel 
}) => {
  const [form] = Form.useForm()
  
  // useSuppliers hook'unu kullan
  const suppliersData = useSuppliers({})
  const createSupplier = suppliersData.createSupplier
  const updateSupplier = suppliersData.updateSupplier
  const isCreating = suppliersData.isCreating
  const isUpdating = suppliersData.isUpdating

  useEffect(() => {
    if (supplier) {
      form.setFieldsValue({
        name: supplier.name,
        contact_person: supplier.contact_person,
        phone: supplier.phone,
        email: supplier.email,
        address: supplier.address,
        tax_number: supplier.tax_number,
        is_active: supplier.is_active,
        delivery_time: supplier.additional_info?.delivery_time,
        discount_rate: supplier.additional_info?.discount_rate,
        payment_terms: supplier.additional_info?.payment_terms
      })
    }
  }, [supplier, form])

  const onFinish = async (values: SupplierFormValues) => {
    try {
      const formData: CreateSupplierRequest = {
        name: values.name,
        contact_person: values.contact_person,
        phone: values.phone,
        email: values.email,
        address: values.address,
        tax_number: values.tax_number,
        is_active: values.is_active,
        additional_info: {
          delivery_time: values.delivery_time,
          discount_rate: values.discount_rate,
          payment_terms: values.payment_terms
        }
      }

      console.log('📝 Supplier Form Data to Send:', formData)

      if (supplier) {
        await updateSupplier({ id: supplier.id, data: formData })
      } else {
        await createSupplier(formData)
        form.resetFields()
      }
      onSuccess?.()
    } catch (error) {
      console.error('❌ Tedarikçi işlemi başarısız:', error)
    }
  }

  return (
    <Card 
      title={
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          {supplier ? <EditOutlined /> : <PlusOutlined />}
          {supplier ? 'Tedarikçi Düzenle' : 'Yeni Tedarikçi Ekle'}
        </div>
      }
    >
      <Form
        form={form}
        layout="vertical"
        onFinish={onFinish}
        initialValues={{
          is_active: true
        }}
      >
        <Divider titlePlacement="left">Temel Bilgiler</Divider>

        <Row gutter={16}>
          <Col span={12}>
            <Form.Item
              label="Firma Adı"
              name="name"
              rules={[
                { required: true, message: 'Firma adı gereklidir!' },
                { min: 2, message: 'Firma adı en az 2 karakter olmalıdır!' },
                { max: 255, message: 'Firma adı en fazla 255 karakter olabilir!' }
              ]}
            >
              <Input 
                prefix={<UserOutlined />}
                placeholder="Firma adını girin" 
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              label="İletişim Kişisi"
              name="contact_person"
            >
              <Input 
                prefix={<UserOutlined />}
                placeholder="İletişim kişisi adı" 
              />
            </Form.Item>
          </Col>
        </Row>

        <Row gutter={16}>
          <Col span={12}>
            <Form.Item
              label="Telefon"
              name="phone"
              rules={[
                { pattern: /^[0-9\s+\-()]+$/, message: 'Geçerli bir telefon numarası girin!' }
              ]}
            >
              <Input 
                prefix={<PhoneOutlined />}
                placeholder="0212 555 0000" 
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              label="E-mail"
              name="email"
              rules={[
                { type: 'email', message: 'Geçerli bir e-mail adresi girin!' }
              ]}
            >
              <Input 
                prefix={<MailOutlined />}
                placeholder="info@firma.com" 
              />
            </Form.Item>
          </Col>
        </Row>

        <Form.Item
          label="Adres"
          name="address"
        >
          <TextArea 
            rows={3} 
            placeholder="Firma adresi (opsiyonel)" 
          />
        </Form.Item>

        <Row gutter={16}>
          <Col span={12}>
            <Form.Item
              label="Vergi Numarası"
              name="tax_number"
              rules={[
                { pattern: /^[0-9]{10,11}$/, message: 'Vergi numarası 10-11 haneli sayı olmalıdır!' }
              ]}
            >
              <Input 
                prefix={<NumberOutlined />}
                placeholder="1234567890" 
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              label="Aktif Durumu"
              name="is_active"
              valuePropName="checked"
            >
              <Switch checkedChildren="Aktif" unCheckedChildren="Pasif" />
            </Form.Item>
          </Col>
        </Row>

        <Divider titlePlacement="left">Ticari Bilgiler</Divider>

        <Row gutter={16}>
          <Col span={8}>
            <Form.Item
              label="Teslimat Süresi"
              name="delivery_time"
            >
              <Input placeholder="Örn: 1-2 gün" />
            </Form.Item>
          </Col>

          <Col span={8}>
            <Form.Item
              label="İndirim Oranı"
              name="discount_rate"
            >
              <Input placeholder="Örn: %5" />
            </Form.Item>
          </Col>

          <Col span={8}>
            <Form.Item
              label="Ödeme Koşulları"
              name="payment_terms"
            >
              <Input placeholder="Örn: 30 gün vadeli" />
            </Form.Item>
          </Col>
        </Row>

        <Form.Item style={{ marginBottom: 0, textAlign: 'right' }}>
          <Button onClick={onCancel} style={{ marginRight: 8 }}>
            İptal
          </Button>
          <Button 
            type="primary" 
            htmlType="submit" 
            icon={supplier ? <SaveOutlined /> : <PlusOutlined />}
            loading={isCreating || isUpdating}
          >
            {supplier ? 'Güncelle' : 'Kaydet'}
          </Button>
        </Form.Item>
      </Form>
    </Card>
  )
}