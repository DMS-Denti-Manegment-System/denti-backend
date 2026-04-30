// src/modules/profile/Pages/ProfilePage.tsx

import React, { useState } from 'react';
import { Card, Tabs, Form, Input, Button, App, Typography, Row, Col } from 'antd';
import { UserOutlined, LockOutlined, MailOutlined } from '@ant-design/icons';
import { useAuth } from '@/Modules/auth/Hooks/useAuth';
import { profileApi } from '../Services/profileApi';
import { UpdateProfileRequest, UpdatePasswordRequest } from '../Types/profile.types';

const { Title, Text } = Typography;

export const ProfilePage: React.FC = () => {
  const { user, updateUser } = useAuthStore();
  const { message } = App.useApp();
  const [infoForm] = Form.useForm();
  const [passwordForm] = Form.useForm();
  const [loading, setLoading] = useState(false);

  // Handle Profile Info Update
  const handleUpdateInfo = async (values: UpdateProfileRequest) => {
    try {
      setLoading(true);
      const response = await profileApi.updateInfo(values);
      if (response.success) {
        updateUser(response.data);
        message.success('Profil bilgileriniz başarıyla güncellendi.');
      }
    } catch (error: any) {
      // Errors handled by axios interceptor but showing fallback here
      console.error('Update Profile Error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Handle Password Update
  const handleUpdatePassword = async (values: UpdatePasswordRequest) => {
    try {
      setLoading(true);
      const response = await profileApi.updatePassword(values);
      if (response.success) {
        message.success('Şifreniz başarıyla değiştirildi.');
        passwordForm.resetFields();
      }
    } catch (error: any) {
      console.error('Update Password Error:', error);
    } finally {
      setLoading(false);
    }
  };

  const infoTab = (
    <Form
      form={infoForm}
      layout="vertical"
      initialValues={{ name: user?.name, email: user?.email }}
      onFinish={handleUpdateInfo}
      autoComplete="off"
    >
      <Row gutter={16}>
        <Col xs={24} md={12}>
          <Form.Item
            name="name"
            label="Ad Soyad"
            rules={[{ required: true, message: 'Lütfen adınızı giriniz!' }]}
          >
            <Input prefix={<UserOutlined />} placeholder="Ad Soyad" />
          </Form.Item>
        </Col>
        <Col xs={24} md={12}>
          <Form.Item
            name="email"
            label="E-posta Adresi"
            rules={[
              { required: true, message: 'Lütfen e-posta adresinizi giriniz!' },
              { type: 'email', message: 'Lütfen geçerli bir e-posta giriniz!' }
            ]}
          >
            <Input prefix={<MailOutlined />} placeholder="E-posta" />
          </Form.Item>
        </Col>
      </Row>
      <Form.Item>
        <Button type="primary" htmlType="submit" loading={loading}>
          Bilgileri Güncelle
        </Button>
      </Form.Item>
    </Form>
  );

  const securityTab = (
    <Form
      form={passwordForm}
      layout="vertical"
      onFinish={handleUpdatePassword}
    >
      <Form.Item
        name="current_password"
        label="Mevcut Şifre"
        rules={[{ required: true, message: 'Mevcut şifrenizi giriniz!' }]}
      >
        <Input.Password prefix={<LockOutlined />} placeholder="Mevcut Şifre" />
      </Form.Item>

      <Form.Item
        name="password"
        label="Yeni Şifre"
        rules={[
          { required: true, message: 'Yeni şifrenizi giriniz!' },
          { min: 8, message: 'Şifre en az 8 karakter olmalıdır!' }
        ]}
      >
        <Input.Password prefix={<LockOutlined />} placeholder="Yeni Şifre" />
      </Form.Item>

      <Form.Item
        name="password_confirmation"
        label="Yeni Şifre (Tekrar)"
        dependencies={['password']}
        rules={[
          { required: true, message: 'Lütfen şifrenizi onaylayın!' },
          ({ getFieldValue }) => ({
            validator(_, value) {
              if (!value || getFieldValue('password') === value) {
                return Promise.resolve();
              }
              return Promise.reject(new Error('Şifreler birbiriyle eşleşmiyor!'));
            },
          }),
        ]}
      >
        <Input.Password prefix={<LockOutlined />} placeholder="Yeni Şifreyi Onaylayın" />
      </Form.Item>

      <Form.Item>
        <Button type="primary" htmlType="submit" loading={loading} danger>
          Şifreyi Güncelle
        </Button>
      </Form.Item>
    </Form>
  );

  const items = [
    {
      key: 'info',
      label: (
        <span>
          <UserOutlined />
          Profil Bilgileri
        </span>
      ),
      children: infoTab,
    },
    {
      key: 'security',
      label: (
        <span>
          <LockOutlined />
          Güvenlik
        </span>
      ),
      children: securityTab,
    },
  ];

  return (
    <div style={{ maxWidth: 800, margin: '0 auto' }}>
      <div style={{ marginBottom: 24 }}>
        <Title level={2}>Profil ve Ayarlar</Title>
        <Text type="secondary">Kişisel bilgilerinizi ve hesap güvenliğinizi buradan yönetebilirsiniz.</Text>
      </div>

      <Card variant="borderless" className="shadow-sm">
        <Tabs 
          defaultActiveKey="info" 
          items={items} 
          size="large"
          animated={{ inkBar: true, tabPane: true }}
        />
      </Card>
    </div>
  );
};

export default ProfilePage;
