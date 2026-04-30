// src/modules/clinics/Components/ClinicForm.tsx

import React, { useEffect } from 'react'
import { 
  Form, 
  Input, 
  Button, 
  Switch, 
  Row, 
  Col, 
  Space,
  Divider,
  Upload
} from 'antd'
import { 
  PlusOutlined, 
  EditOutlined,
  ShopOutlined,
  PhoneOutlined,
  MailOutlined,
  EnvironmentOutlined,
  UserOutlined,
  GlobalOutlined,
  ClockCircleOutlined,
  UploadOutlined
} from '@ant-design/icons'
import { useClinics } from '../Hooks/useClinics'
import { CreateClinicRequest, Clinic } from '../Types/clinic.types'

const { TextArea } = Input

interface ClinicFormProps {
  clinic?: Clinic
  onSuccess?: () => void
  onCancel?: () => void
}

export const ClinicForm: React.FC<ClinicFormProps> = ({ 
  clinic, 
  onSuccess, 
  onCancel 
}) => {
  const [form] = Form.useForm()
  const { createClinic, updateClinic, isCreating, isUpdating } = useClinics()

  const isEditMode = !!clinic

  useEffect(() => {
    if (clinic) {
      // Form alanlarını mevcut klinik verileriyle doldur
      form.setFieldsValue({
        name: clinic.name,
        is_active: clinic.is_active
      })
    }
  }, [clinic, form])

  const onFinish = async (values: CreateClinicRequest) => {
    try {
      if (isEditMode && clinic) {
        await updateClinic({ id: clinic.id, data: values })
      } else {
        await createClinic(values)
        form.resetFields()
      }
      onSuccess?.()
    } catch (error) {
      console.error('Klinik işlemi başarısız:', error)
    }
  }

  const handleCancel = () => {
    form.resetFields()
    onCancel?.()
  }

  return (
    <Form
      form={form}
      layout="vertical"
      onFinish={onFinish}
      autoComplete="off"
      initialValues={{
        is_active: true
      }}
      requiredMark={false}
    >
      {/* Temel Bilgiler */}
      <Divider titlePlacement="left">
        <Space>
          <ShopOutlined />
          Temel Bilgiler
        </Space>
      </Divider>

      <Row gutter={16}>
        <Col span={24}>
          <Form.Item
            label="Klinik Adı"
            name="name"
            rules={[
              { required: true, message: 'Klinik adı gereklidir!' },
              { min: 2, message: 'Klinik adı en az 2 karakter olmalıdır!' },
              { max: 255, message: 'Klinik adı en fazla 255 karakter olabilir!' }
            ]}
          >
            <Input 
              prefix={<ShopOutlined />}
              placeholder="Klinik adını girin"
              size="large"
            />
          </Form.Item>
        </Col>
      </Row>

      <Form.Item
        label="Açıklama"
        name="description"
        rules={[
          { max: 1000, message: 'Açıklama en fazla 1000 karakter olabilir!' }
        ]}
      >
        <TextArea 
          placeholder="Klinik hakkında açıklama..."
          rows={3}
          showCount
          maxLength={1000}
        />
      </Form.Item>

      {/* İletişim Bilgileri */}
      <Divider titlePlacement="left">
        <Space>
          <PhoneOutlined />
          İletişim Bilgileri
        </Space>
      </Divider>

      <Row gutter={16}>
        <Col xs={24} sm={12}>
          <Form.Item
            label="Telefon"
            name="phone"
            rules={[
              { required: true, message: 'Telefon numarası gereklidir!' },
              { pattern: /^[\d\s\-+()]+$/, message: 'Geçerli bir telefon numarası girin!' }
            ]}
          >
            <Input 
              prefix={<PhoneOutlined />}
              placeholder="0 (555) 123 45 67"
              size="large"
            />
          </Form.Item>
        </Col>

        <Col xs={24} sm={12}>
          <Form.Item
            label="E-mail"
            name="email"
            rules={[
              { type: 'email', message: 'Geçerli bir e-mail adresi girin!' }
            ]}
          >
            <Input 
              prefix={<MailOutlined />}
              placeholder="klinik@example.com"
              size="large"
            />
          </Form.Item>
        </Col>
      </Row>

      <Row gutter={16}>
        <Col xs={24} sm={12}>
          <Form.Item
            label="Yönetici Adı"
            name="manager_name"
            rules={[
              { max: 255, message: 'Yönetici adı en fazla 255 karakter olabilir!' }
            ]}
          >
            <Input 
              prefix={<UserOutlined />}
              placeholder="Yönetici adı ve soyadı"
              size="large"
            />
          </Form.Item>
        </Col>

        <Col xs={24} sm={12}>
          <Form.Item
            label="Website"
            name="website"
            rules={[
              { type: 'url', message: 'Geçerli bir website adresi girin!' }
            ]}
          >
            <Input 
              prefix={<GlobalOutlined />}
              placeholder="https://www.klinik.com"
              size="large"
            />
          </Form.Item>
        </Col>
      </Row>

      {/* Adres Bilgileri */}
      <Divider titlePlacement="left">
        <Space>
          <EnvironmentOutlined />
          Adres Bilgileri
        </Space>
      </Divider>

      <Row gutter={16}>
        <Col xs={24} sm={8}>
          <Form.Item
            label="Şehir"
            name="city"
            rules={[
              { required: true, message: 'Şehir gereklidir!' }
            ]}
          >
            <Input 
              placeholder="İstanbul"
              size="large"
            />
          </Form.Item>
        </Col>

        <Col xs={24} sm={8}>
          <Form.Item
            label="İlçe"
            name="district"
            rules={[
              { required: true, message: 'İlçe gereklidir!' }
            ]}
          >
            <Input 
              placeholder="Kadıköy"
              size="large"
            />
          </Form.Item>
        </Col>

        <Col xs={24} sm={8}>
          <Form.Item
            label="Posta Kodu"
            name="postal_code"
          >
            <Input 
              placeholder="34000"
              size="large"
            />
          </Form.Item>
        </Col>
      </Row>

      <Form.Item
        label="Adres"
        name="address"
        rules={[
          { required: true, message: 'Adres gereklidir!' },
          { min: 10, message: 'Adres en az 10 karakter olmalıdır!' }
        ]}
      >
        <TextArea 
          placeholder="Tam adres bilgisi..."
          rows={3}
          showCount
        />
      </Form.Item>

      {/* Ek Bilgiler */}
      <Divider titlePlacement="left">
        <Space>
          <ClockCircleOutlined />
          Ek Bilgiler
        </Space>
      </Divider>

      <Row gutter={16}>
        <Col xs={24} sm={12}>
          <Form.Item
            label="Çalışma Saatleri"
            name="opening_hours"
          >
            <TextArea 
              placeholder="Pazartesi-Cuma: 09:00-18:00&#10;Cumartesi: 09:00-14:00"
              rows={3}
            />
          </Form.Item>
        </Col>

        <Col xs={24} sm={12}>
          <Form.Item
            label="Durum"
            name="is_active"
            valuePropName="checked"
            style={{ marginTop: 8 }}
          >
            <Switch 
              checkedChildren="Aktif" 
              unCheckedChildren="Pasif"
              size="default"
            />
          </Form.Item>
        </Col>
      </Row>

      {/* Logo Upload */}
      <Form.Item
        label="Klinik Logosu"
        name="logo"
        valuePropName="fileList"
        getValueFromEvent={(e) => {
          if (Array.isArray(e)) {
            return e
          }
          return e?.fileList
        }}
      >
        <Upload
          name="logo"
          listType="picture-card"
          maxCount={1}
          beforeUpload={() => false} // Prevent auto upload
          accept="image/*"
        >
          <div>
            <UploadOutlined />
            <div style={{ marginTop: 8 }}>Logo Yükle</div>
          </div>
        </Upload>
      </Form.Item>

      {/* Form Buttons */}
      <Divider />
      
      <Form.Item style={{ marginBottom: 0 }}>
        <Space>
          <Button 
            type="primary" 
            htmlType="submit" 
            icon={isEditMode ? <EditOutlined /> : <PlusOutlined />}
            loading={isCreating || isUpdating}
            size="large"
          >
            {isEditMode ? 'Kliniği Güncelle' : 'Klinik Oluştur'}
          </Button>
          
          <Button 
            onClick={handleCancel}
            disabled={isCreating || isUpdating}
            size="large"
          >
            İptal
          </Button>
        </Space>
      </Form.Item>
    </Form>
  )
}