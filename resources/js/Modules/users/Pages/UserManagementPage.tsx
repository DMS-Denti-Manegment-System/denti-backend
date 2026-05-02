// src/modules/users/Pages/UserManagementPage.tsx

import React, { useState } from 'react';
import { Card, Table, Tag, Badge, Button, Input, Space, Typography, Row, Col, Popconfirm, Avatar } from 'antd';
import { 
  UserOutlined, 
  EditOutlined, 
  DeleteOutlined, 
  UserAddOutlined, 
  SearchOutlined,
  TeamOutlined,
  ShopOutlined
} from '@ant-design/icons';
import { useUsers } from '../Hooks/useUsers';
import { User, UpdateUserPayload, InviteUserPayload } from '../Types/user.types';
import { UserEditModal } from '../Components/UserEditModal';
import { UserInviteModal } from '../Components/UserInviteModal';
import { UserCreateModal } from '../Components/UserCreateModal';
import { useDebounce } from '@/Hooks/useDebounce';
import type { ColumnsType } from 'antd/es/table';

const { Title, Text } = Typography;

export const UserManagementPage: React.FC = () => {
  const [searchText, setSearchText] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);
  
  const debouncedSearch = useDebounce(searchText, 500);

  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isInviteModalOpen, setIsInviteModalOpen] = useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);

  const {
    usersData,
    isLoading,
    inviteUser,
    createUser,
    updateUser,
    deleteUser,
    isInviting,
    isCreating,
    isUpdating,
  } = useUsers({
    page: currentPage,
    per_page: pageSize,
    search: debouncedSearch
  });

  // Safe data extraction
  const tableData = usersData?.data || [];
  const totalItems = usersData?.total || 0;

  const handleEdit = (user: User) => {
    setSelectedUser(user);
    setIsEditModalOpen(true);
  };

  const handleUpdate = async (values: UpdateUserPayload) => {
    if (!selectedUser) return;
    try {
      await updateUser({ id: selectedUser.id, data: values });
      setIsEditModalOpen(false);
    } catch (error) {
      console.error('Update error:', error);
    }
  };

  const handleInvite = async (values: InviteUserPayload) => {
    try {
      await inviteUser(values);
      setIsInviteModalOpen(false);
    } catch (error) {
      console.error('Invite error:', error);
    }
  };

  const handleCreate = async (values: any) => {
    try {
      await createUser(values);
      setIsCreateModalOpen(false);
    } catch (error) {
      console.error('Create error:', error);
    }
  };

  const columns: ColumnsType<User> = [
    {
      title: 'Personel',
      dataIndex: 'name',
      key: 'name',
      render: (text, record) => (
        <Space>
          <Avatar icon={<UserOutlined />} style={{ backgroundColor: record.is_active ? '#1890ff' : '#d9d9d9' }} />
          <div>
            <div style={{ fontWeight: 600 }}>{text}</div>
            <Text type="secondary" style={{ fontSize: '12px' }}>{record.username || record.email}</Text>
          </div>
        </Space>
      ),
    },
    {
      title: 'Klinik',
      dataIndex: 'clinic',
      key: 'clinic',
      render: (clinic: User['clinic']) => (
        <Space>
          <ShopOutlined style={{ color: '#1890ff' }} />
          <span>{clinic?.name || <Text type="secondary">Atanmamış</Text>}</span>
        </Space>
      ),
    },
    {
      title: 'Roller',
      dataIndex: 'roles',
      key: 'roles',
      render: (roles: User['roles']) => (
        <Space wrap>
          {roles.length > 0 ? roles.map(role => (
            <Tag color="geekblue" key={role.id}>{role.name}</Tag>
          )) : <Text type="secondary">Rol Atanmamış</Text>}
        </Space>
      ),
    },
    {
      title: 'Durum',
      dataIndex: 'is_active',
      key: 'is_active',
      render: (isActive: boolean) => (
        <Badge 
          status={isActive ? 'success' : 'error'} 
          text={isActive ? 'Aktif' : 'Pasif'} 
        />
      ),
    },
    {
      title: 'İşlemler',
      key: 'actions',
      width: 120,
      align: 'center',
      render: (_, record) => (
        <Space>
          <Button 
            type="text" 
            icon={<EditOutlined />} 
            onClick={() => handleEdit(record)}
          />
          <Popconfirm
            title="Personeli Kaldır"
            description={`${record.name} isimli personeli klinikten kaldırmak istediğinize emin misiniz? Bu işlem geri alınamaz.`}
            onConfirm={() => deleteUser(record.id)}
            okText="Evet, Kaldır"
            cancelText="İptal"
            okButtonProps={{ danger: true }}
          >
            <Button 
              type="text" 
              danger 
              icon={<DeleteOutlined />} 
            />
          </Popconfirm>
        </Space>
      ),
    },
  ];

  return (
    <div style={{ padding: '24px' }}>
      <Row justify="space-between" align="middle" style={{ marginBottom: '24px' }}>
        <Col>
          <Space align="center">
            <TeamOutlined style={{ fontSize: '28px', color: '#1890ff' }} />
            <div>
              <Title level={3} style={{ margin: 0 }}>Personel Yönetimi</Title>
              <Text type="secondary">Klinik bünyesindeki personelleri listeleyebilir, rollerini ve erişim durumlarını yönetebilirsiniz.</Text>
            </div>
          </Space>
        </Col>
        <Col>
          <Space>
            <Button 
              type="default" 
              icon={<UserAddOutlined />} 
              onClick={() => setIsInviteModalOpen(true)}
              size="large"
            >
              Davet Gönder
            </Button>
            <Button 
              type="primary" 
              icon={<UserAddOutlined />} 
              onClick={() => setIsCreateModalOpen(true)}
              size="large"
            >
              Yeni Kullanıcı Ekle
            </Button>
          </Space>
        </Col>
      </Row>

      <Card style={{ marginBottom: '24px' }}>
        <Row gutter={16}>
          <Col xs={24} md={8}>
            <Input
              placeholder="İsim veya e-posta ile ara..."
              prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />}
              value={searchText}
              onChange={e => setSearchText(e.target.value)}
              allowClear
            />
          </Col>
        </Row>
      </Card>

      <Card styles={{ body: { padding: 0 } }}>
        <Table 
          columns={columns} 
          dataSource={tableData} 
          rowKey="id" 
          loading={isLoading}
          pagination={{
            current: currentPage,
            pageSize: pageSize,
            total: totalItems,
            showSizeChanger: true,
            onChange: (page, size) => {
              setCurrentPage(page);
              setPageSize(size);
            },
            showTotal: (total) => `Toplam ${total} personel`,
          }}
        />
      </Card>

      <UserEditModal
        open={isEditModalOpen}
        onCancel={() => setIsEditModalOpen(false)}
        onSubmit={handleUpdate}
        initialValues={selectedUser}
        loading={isUpdating}
      />

      <UserInviteModal
        open={isInviteModalOpen}
        onCancel={() => setIsInviteModalOpen(false)}
        onSubmit={handleInvite}
        loading={isInviting}
      />

      <UserCreateModal
        open={isCreateModalOpen}
        onCancel={() => setIsCreateModalOpen(false)}
        onSubmit={handleCreate}
        loading={isCreating}
      />
    </div>
  );
};
