// src/modules/auth/Components/AdminLoginForm.tsx

import React from 'react';
import { Form, Input, Button, Card, Typography } from 'antd';
import { UserOutlined, LockOutlined } from '@ant-design/icons';
import { useAuth } from '../Hooks/useAuth';

const { Title, Text } = Typography;

export const AdminLoginForm: React.FC = () => {
  const { adminLogin, loading } = useAuth();

  const onFinish = (values: any) => {
    adminLogin(values);
  };

  return (
    <Card 
      style={{ width: 400, boxShadow: '0 4px 12px rgba(0,0,0,0.1)', borderRadius: 12 }}
      styles={{ body: { padding: '32px' } }}
    >
      <div style={{ textAlign: 'center', marginBottom: 32 }}>
        <Title level={2} style={{ color: '#ff4d4f', marginBottom: 8 }}>🔐 Denti Admin</Title>
        <Text type="secondary">Sistem Yönetim Paneli</Text>
      </div>

      <Form
        name="admin_login"
        onFinish={onFinish}
        layout="vertical"
        size="large"
      >
        <Form.Item
          name="username"
          rules={[{ required: true, message: 'Lütfen kullanıcı adınızı girin!' }]}
        >
          <Input prefix={<UserOutlined />} placeholder="Admin Kullanıcı Adı" />
        </Form.Item>

        <Form.Item
          name="password"
          rules={[{ required: true, message: 'Lütfen şifrenizi girin!' }]}
        >
          <Input.Password
            prefix={<LockOutlined />}
            placeholder="Şifre"
          />
        </Form.Item>

        <Form.Item>
          <Button type="primary" danger htmlType="submit" loading={loading} block style={{ height: 45, borderRadius: 8 }}>
            Sisteme Giriş Yap
          </Button>
        </Form.Item>
      </Form>
    </Card>
  );
};
