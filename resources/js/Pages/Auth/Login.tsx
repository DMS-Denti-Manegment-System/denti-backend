import { Form, Input, Button, Card, Typography } from 'antd';
import { UserOutlined, LockOutlined, BankOutlined } from '@ant-design/icons';
import { Head, useForm } from '@inertiajs/react';

const { Title, Text } = Typography;

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        company_code: '',
        username: '',
        password: '',
        remember: true,
    });

    const onFinish = () => {
        post('/login');
    };

    return (
        <div style={{ 
            height: '100vh', 
            display: 'flex', 
            justifyContent: 'center', 
            alignItems: 'center',
            background: 'linear-gradient(135deg, #f0f2f5 0%, #e6f7ff 100%)'
        }}>
            <Head title="Giriş Yap" />
            
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
                    initialValues={data}
                    onFinish={onFinish}
                    layout="vertical"
                    size="large"
                >
                    <Form.Item
                        validateStatus={errors.company_code ? 'error' : ''}
                        help={errors.company_code}
                    >
                        <Input 
                            prefix={<BankOutlined />} 
                            placeholder="Klinik Kodu" 
                            value={data.company_code}
                            onChange={e => setData('company_code', e.target.value)}
                        />
                    </Form.Item>

                    <Form.Item
                        validateStatus={errors.username ? 'error' : ''}
                        help={errors.username}
                    >
                        <Input 
                            prefix={<UserOutlined />} 
                            placeholder="Kullanıcı Adı" 
                            value={data.username}
                            onChange={e => setData('username', e.target.value)}
                        />
                    </Form.Item>

                    <Form.Item
                        validateStatus={errors.password ? 'error' : ''}
                        help={errors.password}
                    >
                        <Input.Password
                            prefix={<LockOutlined />}
                            placeholder="Şifre"
                            value={data.password}
                            onChange={e => setData('password', e.target.value)}
                        />
                    </Form.Item>

                    <Form.Item>
                        <Button 
                            type="primary" 
                            htmlType="submit" 
                            loading={processing} 
                            block 
                            style={{ height: 45, borderRadius: 8 }}
                        >
                            Giriş Yap
                        </Button>
                    </Form.Item>
                </Form>
            </Card>
        </div>
    );
}
