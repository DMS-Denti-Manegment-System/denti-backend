// src/modules/users/Components/UserEditModal.tsx

import React, { useEffect } from 'react';
import { Modal, Form, Input, Select, Switch, Typography } from 'antd';
import { User, UpdateUserPayload } from '../Types/user.types';
import { useRoles } from '../../roles/Hooks/useRoles';
import { useClinics } from '@/Modules/clinics/Hooks/useClinics';

const { Text } = Typography;

interface UserEditModalProps {
  open: boolean;
  onCancel: () => void;
  onSubmit: (values: UpdateUserPayload) => void;
  initialValues: User | null;
  loading: boolean;
}

export const UserEditModal: React.FC<UserEditModalProps> = ({
  open,
  onCancel,
  onSubmit,
  initialValues,
  loading,
}) => {
  const [form] = Form.useForm();
  const { roles, isLoading: isRolesLoading } = useRoles();
  const { clinics, isLoading: isClinicsLoading } = useClinics();

  useEffect(() => {
    if (open && initialValues) {
      form.setFieldsValue({
        name: initialValues.name,
        is_active: initialValues.is_active,
        role_id: initialValues.roles[0]?.id, // İlk rolün ID'sini al
        clinic_id: initialValues.clinic_id,
      });
    } else {
      form.resetFields();
    }
  }, [open, initialValues, form]);

  return (
    <Modal
      title="Personel Düzenle"
      open={open}
      onCancel={onCancel}
      onOk={() => form.submit()}
      confirmLoading={loading}
      destroyOnHidden
    >
      <Form
        form={form}
        layout="vertical"
        onFinish={onSubmit}
      >
        <div style={{ marginBottom: '24px', padding: '12px', background: '#f5f5f5', borderRadius: '8px' }}>
          <Text type="secondary">Kullanıcı Adı:</Text>
          <div style={{ fontWeight: 500 }}>{initialValues?.username}</div>
        </div>

        <Form.Item
          label="Ad Soyad"
          name="name"
          rules={[{ required: true, message: 'Lütfen ad soyad giriniz.' }]}
        >
          <Input placeholder="Örn: Dr. Ahmet Yılmaz" />
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
