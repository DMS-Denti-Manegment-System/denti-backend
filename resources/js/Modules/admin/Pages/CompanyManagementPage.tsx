// src/modules/admin/Pages/CompanyManagementPage.tsx

import React, { useEffect, useState } from 'react';
import { 
  Table, Card, Button, Tag, Space, Typography, 
  Input, App, Badge, Popconfirm, Result, 
  Descriptions, Tooltip 
} from 'antd';
import { 
  PlusOutlined, SearchOutlined, EditOutlined, 
  DeleteOutlined, CopyOutlined, GlobalOutlined 
} from '@ant-design/icons';
import { Company, CompanyStoreResponse } from '../Types/company.types';
import { companyApi } from '../Services/companyApi';
import { CompanyFormModal } from '../Components/CompanyFormModal';

const { Title } = Typography;

export const CompanyManagementPage: React.FC = () => {
  const { message, modal } = App.useApp();
  const [loading, setLoading] = useState(false);
  const [companies, setCompanies] = useState<Company[]>([]);
  const [searchText, setSearchText] = useState('');
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [editingCompany, setEditingCompany] = useState<Company | null>(null);

  const fetchCompanies = async () => {
    setLoading(true);
    try {
      const response = await companyApi.getCompanies();
      setCompanies(response.data);
    } catch (error) {
      console.error('Fetch companies error:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCompanies();
  }, []);

  const handleDelete = async (id: number) => {
    try {
      await companyApi.deleteCompany(id);
      message.success('Şirket başarıyla silindi.');
      fetchCompanies();
    } catch (error) {}
  };

  const showSuccessResult = (data: CompanyStoreResponse['data']) => {
    modal.success({
      title: 'Şirket Başarıyla Kuruldu',
      width: 600,
      maskClosable: false,
      content: (
        <div style={{ marginTop: 24 }}>
          <Result
            status="success"
            title="Sistem Giriş Bilgileri Hazır"
            subTitle="Lütfen aşağıdaki bilgileri güvenli bir yere not edin. Şifre bir daha gösterilmeyecektir."
          />
          <Descriptions bordered column={1} size="small" style={{ marginTop: 16 }}>
            <Descriptions.Item label="Şirket Adı">{data.company.name}</Descriptions.Item>
            <Descriptions.Item label="Domain">{data.company.domain}.denti.com</Descriptions.Item>
            <Descriptions.Item label="Yönetici E-posta">{data.user.email}</Descriptions.Item>
            <Descriptions.Item label="Geçici Şifre">
              <Space>
                <code style={{ 
                  color: '#cf1322', 
                  fontWeight: 'bold', 
                  fontSize: 16,
                  background: '#fff1f0',
                  padding: '2px 8px',
                  borderRadius: 4
                }}>
                  {data.password}
                </code>
                <Button 
                  icon={<CopyOutlined />} 
                  size="small" 
                  onClick={() => {
                    navigator.clipboard.writeText(data.password);
                    message.success('Şifre kopyalandı!');
                  }}
                >
                  Kopyala
                </Button>
              </Space>
            </Descriptions.Item>
          </Descriptions>
        </div>
      ),
      okText: 'Bilgileri Not Ettim',
    });
  };

  const columns = [
    {
      title: 'Şirket / Domain',
      dataIndex: 'name',
      key: 'name',
      render: (text: string, record: Company) => (
        <Space direction="vertical" size={0}>
          <Typography.Text strong>{text}</Typography.Text>
          <Typography.Text type="secondary" style={{ fontSize: 12 }}>
            <GlobalOutlined style={{ marginRight: 4 }} />
            {record.domain}.denti.com
          </Typography.Text>
        </Space>
      ),
    },
    {
      title: 'Plan',
      dataIndex: 'subscription_plan',
      key: 'subscription_plan',
      render: (plan: string) => {
        const colors = { basic: 'blue', standard: 'orange', premium: 'purple' };
        return (
          <Tag color={colors[plan as keyof typeof colors] || 'default'}>
            {plan.toUpperCase()}
          </Tag>
        );
      },
    },
    {
      title: 'Kullanıcı Sınırı',
      dataIndex: 'max_users',
      key: 'max_users',
      align: 'center' as const,
      render: (val: number) => <Badge count={val} showZero color="#108ee9" />,
    },
    {
      title: 'Durum',
      dataIndex: 'status',
      key: 'status',
      render: (status: string) => (
        <Badge 
          status={status === 'active' ? 'success' : 'error'} 
          text={status === 'active' ? 'Aktif' : 'Pasif'} 
        />
      ),
    },
    {
      title: 'Kayıt Tarihi',
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date: string) => new Date(date).toLocaleDateString('tr-TR'),
    },
    {
      title: 'İşlemler',
      key: 'actions',
      render: (_: any, record: Company) => (
        <Space>
          <Tooltip title="Düzenle">
            <Button 
              type="text"
              icon={<EditOutlined />} 
              onClick={() => {
                setEditingCompany(record);
                setIsFormModalOpen(true);
              }} 
            />
          </Tooltip>
          <Popconfirm
            title="Şirketi Sil"
            description="Bu işlem geri alınamaz ve tüm şirket verileri silinecektir. Emin misiniz?"
            onConfirm={() => handleDelete(record.id)}
            okText="Evet, Sil"
            cancelText="Vazgeç"
            okButtonProps={{ danger: true }}
          >
            <Tooltip title="Sil">
              <Button type="text" danger icon={<DeleteOutlined />} />
            </Tooltip>
          </Popconfirm>
        </Space>
      ),
    },
  ];

  const filteredCompanies = companies.filter(c => 
    c.name.toLowerCase().includes(searchText.toLowerCase()) || 
    c.domain.toLowerCase().includes(searchText.toLowerCase())
  );

  return (
    <div style={{ padding: '24px' }}>
      <div style={{ 
        display: 'flex', 
        justifyContent: 'space-between', 
        alignItems: 'center', 
        marginBottom: 24 
      }}>
        <Title level={2} style={{ margin: 0 }}>Şirket Yönetimi</Title>
        <Button 
          type="primary" 
          icon={<PlusOutlined />} 
          size="large"
          onClick={() => {
            setEditingCompany(null);
            setIsFormModalOpen(true);
          }}
        >
          Yeni Şirket Kur
        </Button>
      </div>

      <Card variant="borderless" style={{ boxShadow: '0 1px 2px rgba(0,0,0,0.03)' }}>
        <Input
          placeholder="Şirket adı veya domain ile ara..."
          prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />}
          style={{ marginBottom: 20, width: 350 }}
          onChange={e => setSearchText(e.target.value)}
          allowClear
          size="large"
        />

        <Table 
          columns={columns} 
          dataSource={filteredCompanies} 
          rowKey="id" 
          loading={loading}
          pagination={{ 
            pageSize: 10,
            showTotal: (total) => `Toplam ${total} şirket`
          }}
        />
      </Card>

      <CompanyFormModal 
        open={isFormModalOpen}
        editingCompany={editingCompany}
        onCancel={() => setIsFormModalOpen(false)}
        onSuccess={(data) => {
          setIsFormModalOpen(false);
          fetchCompanies();
          if (data) {
            showSuccessResult(data);
          }
        }}
      />
    </div>
  );
};

export default CompanyManagementPage;
