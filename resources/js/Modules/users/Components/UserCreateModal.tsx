// src/modules/users/Components/UserCreateModal.tsx

import React from 'react';
import { Modal, Form, Input, Select, Switch } from 'antd';
import { UserOutlined, MailOutlined, LockOutlined } from '@ant-design/icons';
import { useRoles } from '../../roles/Hooks/useRoles';
import { useClinics } from '@/Modules/clinics/Hooks/useClinics';

interface UserCreateModalProps {
  open: boolean;
  onCancel: () => void;
  onSubmit: (values: any) => void;
  loading: boolean;
}

export const UserCreateModal: React.FC<UserCreateModalProps> = ({
  open,
  onCancel,
  onSubmit,
  loading,
}) => {
  const [form] = Form.useForm();
  const { roles, isLoading: isRolesLoading } = useRoles();
  const { clinics, isLoading: isClinicsLoading } = useClinics();

  return (
    <Modal
      title="Yeni Kullanıcı Ekle"
      open={open}
      onCancel={onCancel}
      onOk={() => form.submit()}
      confirmLoading={loading}
      destroyOnHidden
      okText="Kullanıcıyı Oluştur"
      cancelText="İptal"
    >
      <Form
        form={form}
        layout="vertical"
        onFinish={(values) => {
          onSubmit(values);
          form.resetFields();
        }}
      >
        <Form.Item
          label="Ad Soyad"
          name="name"
          rules={[{ required: true, message: 'Lütfen ad soyad giriniz.' }]}
        >
          <Input prefix={<UserOutlined />} placeholder="Örn: Ahmet Yılmaz" />
        </Form.Item>

        <Form.Item
          label="Kullanıcı Adı"
          name="username"
          rules={[{ required: true, message: 'Lütfen kullanıcı adı giriniz.' }]}
        >
          <Input prefix={<UserOutlined />} placeholder="Örn: ahmet123" />
        </Form.Item>

        <Form.Item
          label="E-posta Adresi (İsteğe Bağlı)"
          name="email"
          rules={[
            { type: 'email', message: 'Geçerli bir e-posta giriniz.' }
          ]}
        >
          <Input prefix={<MailOutlined />} placeholder="Örn: ahmet@klinik.com" />
        </Form.Item>

        <Form.Item
          label="Şifre"
          name="password"
          rules={[
            { required: true, message: 'Lütfen bir şifre belirleyiniz.' },
            { min: 8, message: 'Şifre en az 8 karakter olmalıdır.' }
          ]}
        >
          <Input.Password prefix={<LockOutlined />} placeholder="Şifre" />
        </Form.Item>

        <Form.Item
          label="Klinik"
          name="clinic_id"
          rules={[{ required: true, message: 'Lütfen bir klinik seçiniz.' }]}
        >
          <Select 
            placeholder="Klinik seçin" 
            loading={isClinicsLoading}
          >
            {clinics?.map((clinic: any) => (
              <Select.Option key={clinic.id} value={clinic.id}>
                {clinic.name}
              </Select.Option>
            ))}
          </Select>
        </Form.Item>

        <Form.Item
          label="Rol"
          name="role_id"
          rules={[{ required: true, message: 'Lütfen bir rol seçiniz.' }]}
        >
          <Select 
            placeholder="Rol seçin" 
            loading={isRolesLoading}
          >
            {roles.map((role: any) => (
              <Select.Option key={role.id} value={role.id}>
                {role.name}
              </Select.Option>
            ))}
          </Select>
        </Form.Item>

        <Form.Item
          label="Hesap Durumu"
          name="is_active"
          valuePropName="checked"
          initialValue={true}
        >
          <Switch 
            checkedChildren="Aktif" 
            unCheckedChildren="Pasif" 
          />
        </Form.Item>
      </Form>
    </Modal>
  );
};
