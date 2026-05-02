// src/modules/auth/Components/LoginForm.tsx

import React, { useState } from 'react';
import { Form, Input, Button, Card, Typography, Modal } from 'antd';
import { UserOutlined, LockOutlined, SafetyOutlined, BankOutlined } from '@ant-design/icons';
import { useAuth } from '../Hooks/useAuth';

const { Title, Text } = Typography;

export const LoginForm: React.FC = () => {
  const { login, verify2fa, loading } = useAuth();
  const [show2faModal, setShow2faModal] = useState(false);
  const [twoFactorForm] = Form.useForm();

  const onFinish = (values: any) => {
    login(values);
  };

  const on2faFinish = async (values: { code: string }) => {
    await verify2fa(values);
    setShow2faModal(false);
  };

  return (
    <>
      <Card 
        style={{ width: 400, boxShadow: '0 4px 12px rgba(0,0,0,0.1)', borderRadius: 12 }}
        styles={{ body: { padding: '32px' } }}
      >
        <div style={{ textAlign: 'center', marginBottom: 32 }}>
          <Title level={2} style={{ color: '#1890ff', marginBottom: 8 }}>🦷 Denti</Title>
          <Text type="secondary">Klinik Yönetim Paneli</Text>
        </div>

        <Form
          name="login"
          initialValues={{ remember: true }}
          onFinish={onFinish}
          layout="vertical"
          size="large"
        >
          <Form.Item
            name="company_code"
            rules={[{ required: true, message: 'Lütfen klinik kodunu girin!' }]}
          >
            <Input prefix={<BankOutlined />} placeholder="Klinik Kodu" />
          </Form.Item>

          <Form.Item
            name="username"
            rules={[{ required: true, message: 'Lütfen kullanıcı adınızı girin!' }]}
          >
            <Input prefix={<UserOutlined />} placeholder="Kullanıcı Adı" />
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
            <Button type="primary" htmlType="submit" loading={loading} block style={{ height: 45, borderRadius: 8 }}>
              Giriş Yap
            </Button>
          </Form.Item>
        </Form>
      </Card>

      {/* 2FA Verification Modal */}
      <Modal
        title="İki Faktörlü Doğrulama"
        open={show2faModal}
        onOk={() => twoFactorForm.submit()}
        onCancel={() => setShow2faModal(false)}
        confirmLoading={loading}
        okText="Doğrula"
        cancelText="İptal"
        destroyOnHidden
      >
        <div style={{ marginBottom: 20 }}>
          <Text>Hesabınızda 2FA aktif. Lütfen uygulamanızdaki 6 haneli kodu girin.</Text>
        </div>
        <Form
          form={twoFactorForm}
          onFinish={on2faFinish}
          layout="vertical"
        >
          <Form.Item
            name="code"
            rules={[
              { required: true, message: 'Lütfen 2FA kodunu girin!' },
              { len: 6, message: 'Kod 6 haneli olmalıdır!' },
              { pattern: /^\d+$/, message: 'Sadece rakam giriniz!' }
            ]}
          >
            <Input 
              prefix={<SafetyOutlined />} 
              placeholder="6 Haneli Kod" 
              maxLength={6} 
              autoFocus
              style={{ fontSize: '20px', textAlign: 'center', letterSpacing: '8px' }}
            />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
};
