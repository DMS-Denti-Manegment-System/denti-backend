// src/modules/roles/Components/RoleFormModal.tsx

import React, { useEffect } from 'react';
import { Modal, Form, Input, Card, Checkbox, Row, Col, Divider, Button, Skeleton, Space } from 'antd';
import { Role, PermissionGroup, RoleStorePayload } from '../Types/role.types';

interface RoleFormModalProps {
  open: boolean;
  onCancel: () => void;
  onSubmit: (values: RoleStorePayload) => void;
  initialValues?: Role | null;
  permissionGroups: PermissionGroup[];
  isLoadingPermissions: boolean;
  isProcessing: boolean;
}

export const RoleFormModal: React.FC<RoleFormModalProps> = ({
  open,
  onCancel,
  onSubmit,
  initialValues,
  permissionGroups,
  isLoadingPermissions,
  isProcessing,
}) => {
  const [form] = Form.useForm();

  useEffect(() => {
    if (open && initialValues) {
      form.setFieldsValue({
        name: initialValues.name,
        permissions: initialValues.permissions.map(p => p.name),
      });
    } else {
      form.resetFields();
    }
  }, [open, initialValues, form]);

  const handleSelectAllGroup = (groupPermissions: string[], checked: boolean) => {
    const currentPermissions = form.getFieldValue('permissions') || [];
    let updatedPermissions: string[];

    if (checked) {
      // Gruba ait tüm izinleri ekle (Duplicate kontrolü yaparak)
      updatedPermissions = Array.from(new Set([...currentPermissions, ...groupPermissions]));
    } else {
      // Gruba ait tüm izinleri çıkar
      updatedPermissions = currentPermissions.filter((p: string) => !groupPermissions.includes(p));
    }

    form.setFieldValue('permissions', updatedPermissions);
  };

  const isGroupFullySelected = (groupPermissions: string[]) => {
    const currentPermissions = form.getFieldValue('permissions') || [];
    return groupPermissions.every(p => currentPermissions.includes(p));
  };

  const isGroupPartiallySelected = (groupPermissions: string[]) => {
    const currentPermissions = form.getFieldValue('permissions') || [];
    const intersection = groupPermissions.filter(p => currentPermissions.includes(p));
    return intersection.length > 0 && intersection.length < groupPermissions.length;
  };

  return (
    <Modal
      title={initialValues ? 'Rolü Düzenle' : 'Yeni Rol Oluştur'}
      open={open}
      onCancel={onCancel}
      onOk={() => form.submit()}
      confirmLoading={isProcessing}
      width={1000}
      destroyOnClose
    >
      <Form
        form={form}
        layout="vertical"
        onFinish={onSubmit}
        initialValues={{ permissions: [] }}
      >
        <Form.Item
          label="Rol Adı"
          name="name"
          rules={[{ required: true, message: 'Lütfen rol adını giriniz.' }]}
        >
          <Input placeholder="Örn: Klinik Yöneticisi" />
        </Form.Item>

        <Divider titlePlacement="left">İzinler (Modül Bazlı)</Divider>

        {isLoadingPermissions ? (
          <Skeleton active />
        ) : (
          <Form.Item name="permissions">
            <Checkbox.Group style={{ width: '100%' }}>
              <Row gutter={[16, 16]}>
                {Array.isArray(permissionGroups) && permissionGroups.map((group) => {
                  const groupPermNames = group.permissions.map(p => p.name);
                  
                  return (
                    <Col xs={24} md={12} key={group.module}>
                      <Card 
                        size="small" 
                        title={group.module.toUpperCase()}
                        extra={
                          <Checkbox
                            indeterminate={isGroupPartiallySelected(groupPermNames)}
                            onChange={(e) => handleSelectAllGroup(groupPermNames, e.target.checked)}
                            checked={isGroupFullySelected(groupPermNames)}
                          >
                            Hepsini Seç
                          </Checkbox>
                        }
                      >
                        <Row gutter={[8, 8]}>
                          {group.permissions.map((perm) => (
                            <Col span={24} key={perm.id}>
                              <Checkbox value={perm.name}>
                                <Space direction="vertical" size={0}>
                                  <span style={{ fontWeight: 500 }}>{perm.display_name}</span>
                                  <span style={{ fontSize: '11px', color: '#8c8c8c' }}>{perm.description || perm.name}</span>
                                </Space>
                              </Checkbox>
                            </Col>
                          ))}
                        </Row>
                      </Card>
                    </Col>
                  );
                })}
              </Row>
            </Checkbox.Group>
          </Form.Item>
        )}
      </Form>
    </Modal>
  );
};
