import React, { useState } from 'react'
import { Layout, Menu, Button, Typography, Space, Avatar, Dropdown, Badge } from 'antd'
import { 
  MenuFoldOutlined, 
  MenuUnfoldOutlined,
  ShoppingCartOutlined,
  TeamOutlined,
  BankOutlined,
  SwapOutlined,
  BellOutlined,
  BarChartOutlined,
  UserOutlined,
  LogoutOutlined,
  SettingOutlined,
  SafetyCertificateOutlined,
  DashboardOutlined,
  CheckSquareOutlined,
  TagsOutlined,
} from '@ant-design/icons'
import { Link, router, usePage } from '@inertiajs/react'
import type { MenuProps } from 'antd'
import { usePendingAlertCount } from '@/Modules/alerts/Hooks/useAlerts'
import { useAuth } from '@/Modules/auth/Hooks/useAuth'
import { usePermissions } from '@/Hooks/usePermissions'

const { Header, Sider, Content } = Layout
const { Title } = Typography

interface Props {
    children: React.ReactNode;
}

export const AppLayout: React.FC<Props> = ({ children }) => {
  const [collapsed, setCollapsed] = useState(false)
  const { url } = usePage()
  const { user, logout } = useAuth()
  const { hasPermission, isAdmin } = usePermissions()
  
  // Bekleyen uyarı sayısını çek
  const { data: pendingAlertCount } = usePendingAlertCount()

  const handleUserMenuClick: MenuProps['onClick'] = ({ key }) => {
    if (key === 'logout') {
      logout().then(() => router.visit('/login'))
    } else if (key === 'profile') {
      router.visit('/profile')
    }
  }

  const menuItems: MenuProps['items'] = [
    {
      key: '/',
      icon: <DashboardOutlined />,
      label: <Link href="/">Ana Sayfa</Link>,
    },
    {
      key: '/stocks',
      icon: <ShoppingCartOutlined />,
      label: <Link href="/stocks">Stok Yönetimi</Link>,
    },
    {
      key: '/stock-categories',
      icon: <TagsOutlined />,
      label: <Link href="/stock-categories">Stok Kategorileri</Link>,
    },
    {
      key: '/suppliers',
      icon: <TeamOutlined />,
      label: <Link href="/suppliers">Tedarikçiler</Link>,
    },
    {
      key: '/clinics',
      icon: <BankOutlined />,
      label: <Link href="/clinics">Klinikler</Link>, 
    },
    {
      key: '/stock-requests',
      icon: <SwapOutlined />,
      label: <Link href="/stock-requests">Stok Talepleri</Link>,
    },
    {
      key: '/alerts',
      icon: <BellOutlined />,
      label: (
        <Link href="/alerts">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%', paddingRight: collapsed ? 0 : 8 }}>
            <span>Uyarılar</span>
            {pendingAlertCount !== undefined && pendingAlertCount > 0 && !collapsed && (
              <Badge count={pendingAlertCount} style={{ backgroundColor: '#ff4d4f' }} />
            )}
          </div>
        </Link>
      ),
    },
    {
      key: '/todos',
      icon: <CheckSquareOutlined />,
      label: <Link href="/todos">Yapılacaklar</Link>,
    },
    {
      key: '/reports',
      icon: <BarChartOutlined />,
      label: <Link href="/reports">Raporlar</Link>,
    },
    ...((isAdmin || hasPermission('manage-users')) ? [
      {
        key: 'management',
        icon: <SettingOutlined />,
        label: 'Yönetim',
        children: [
          {
            key: '/employees',
            icon: <TeamOutlined />,
            label: <Link href="/employees">Personel Yönetimi</Link>,
          },
          {
            key: '/roles',
            icon: <SafetyCertificateOutlined />,
            label: <Link href="/roles">Rol ve Yetkiler</Link>,
          }
        ]
      }
    ] : []),
  ]

  const userMenuItems: MenuProps['items'] = [
    {
      key: 'profile',
      icon: <UserOutlined />,
      label: 'Profil'
    },
    {
      key: 'settings',
      icon: <SettingOutlined />,
      label: 'Ayarlar'
    },
    {
      type: 'divider'
    },
    {
      key: 'logout',
      icon: <LogoutOutlined />,
      label: 'Çıkış Yap'
    }
  ]

  return (
    <Layout style={{ minHeight: '100vh' }}>
      <Sider 
        trigger={null} 
        collapsible 
        collapsed={collapsed}
        style={{
          background: '#fff',
          boxShadow: '2px 0 8px 0 rgba(29, 35, 41, 0.05)'
        }}
      >
        <div style={{ 
          height: 64, 
          padding: '16px', 
          display: 'flex', 
          alignItems: 'center',
          borderBottom: '1px solid #f0f0f0'
        }}>
          {!collapsed && (
            <Title level={4} style={{ margin: 0, color: '#1890ff' }}>
              🦷 Denti Management
            </Title>
          )}
          {collapsed && (
            <div style={{ fontSize: '24px' }}>🦷</div>
          )}
        </div>
        
        <Menu
          mode="inline"
          selectedKeys={[url]}
          style={{ border: 'none' }}
          items={menuItems}
        />
      </Sider>
      
      <Layout>
        <Header style={{ 
          padding: '0 24px', 
          background: '#fff',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          boxShadow: '0 2px 8px rgba(0, 0, 0, 0.06)'
        }}>
          <Button
            type="text"
            icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
            onClick={() => setCollapsed(!collapsed)}
            style={{
              fontSize: '16px',
              width: 64,
              height: 64,
            }}
          />
          
          <Space>
            <Dropdown menu={{ items: userMenuItems, onClick: handleUserMenuClick }} placement="bottomRight">
              <Space style={{ cursor: 'pointer' }}>
                <Avatar icon={<UserOutlined />} src={user?.avatar} />
                <span>{user?.name || 'Kullanıcı'}</span>
              </Space>
            </Dropdown>
          </Space>
        </Header>
        
        <Content style={{ 
          margin: '24px',
          padding: '24px',
          background: '#f5f5f5',
          minHeight: 'calc(100vh - 112px)',
          overflow: 'auto'
        }}>
          {children}
        </Content>
      </Layout>
    </Layout>
  )
}