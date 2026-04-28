// src/modules/stock/Components/StockModals.tsx

import React from 'react'
import { Modal, Form, Input, InputNumber, Select, Alert, Button, Space, Radio, Checkbox } from 'antd'
import type { FormInstance } from 'antd'
import { useAuthStore } from '@/Stores/authStore'
import { Stock, StockAdjustmentRequest, StockUsageRequest } from '../Types/stock.types'
import { ProductForm } from './ProductForm'
import { formatStock } from '@/Utils/helpers'

const { Option } = Select

interface StockModalsProps {
  // Form Modal
  isFormModalVisible: boolean
  editingStock: any | null // Master Product
  onFormModalClose: () => void
  onFormSuccess: () => void

  // Adjust Modal
  isAdjustModalVisible: boolean
  selectedStock: Stock | null
  adjustForm: FormInstance
  onAdjustModalClose: () => void
  onAdjustSubmit: (values: StockAdjustmentRequest) => void
  isAdjusting: boolean

  // Use Modal
  isUseModalVisible: boolean
  useForm: FormInstance
  onUseModalClose: () => void
  onUseSubmit: (values: StockUsageRequest) => void
  isUsing: boolean
}

export const StockModals: React.FC<StockModalsProps> = ({
  // Form Modal props
  isFormModalVisible,
  editingStock,
  onFormModalClose,
  onFormSuccess,

  // Adjust Modal props
  isAdjustModalVisible,
  selectedStock,
  adjustForm,
  onAdjustModalClose,
  onAdjustSubmit,
  isAdjusting,

  // Use Modal props
  isUseModalVisible,
  useForm,
  onUseModalClose,
  onUseSubmit,
  isUsing,
}) => {
  const user = useAuthStore(state => state.user)

  return (
    <>
      {/* Form Modal */}
      <Modal
        title={editingStock ? 'Ürün Düzenle' : 'Yeni Ürün Ekle'}
        open={isFormModalVisible}
        onCancel={onFormModalClose}
        footer={null}
        width={800}
      >
        <ProductForm 
          initialValues={editingStock}
          onSuccess={onFormSuccess}
          onCancel={onFormModalClose}
        />
      </Modal>

      {/* Stok Ayarlama Modal */}
      <Modal
        title="Stok Miktarı Ayarla"
        open={isAdjustModalVisible}
        onCancel={onAdjustModalClose}
        footer={null}
        width={500}
        destroyOnHidden
      >
        <Form
          form={adjustForm}
          layout="vertical"
          onFinish={onAdjustSubmit}
          initialValues={{ 
            is_sub_unit: false,
            performed_by: user?.name || ''
          }}
        >
          <Alert
            message={`Mevcut Miktar: ${formatStock(
              selectedStock?.current_stock || 0,
              selectedStock?.unit || '',
              selectedStock?.has_sub_unit,
              selectedStock?.current_sub_stock,
              selectedStock?.sub_unit_name
            )}`}
            type="info"
            style={{ marginBottom: 16 }}
          />



          <Form.Item
            label="İşlem Tipi"
            name="type"
            rules={[{ required: true, message: 'İşlem tipi seçimi gereklidir!' }]}
          >
            <Select placeholder="İşlem tipi seçin">
              <Option value="increase">Artır</Option>
              <Option value="decrease">Azalt</Option>
            </Select>
          </Form.Item>

          {selectedStock?.has_sub_unit && (
            <Form.Item
              label="İşlem Birimi"
              name="is_sub_unit"
              rules={[{ required: true }]}
            >
              <Radio.Group>
                <Radio value={false}>{selectedStock.unit} (Ana Birim)</Radio>
                <Radio value={true}>{selectedStock.sub_unit_name} (Alt Birim)</Radio>
              </Radio.Group>
            </Form.Item>
          )}

          <Form.Item
            label="Miktar"
            name="quantity"
            rules={[{ required: true, message: 'Miktar gereklidir!' }]}
          >
            <InputNumber 
              min={1} 
              style={{ width: '100%' }}
              placeholder="Ayarlanacak miktar"
            />
          </Form.Item>

          <Form.Item
            label="Sebep"
            name="reason"
            rules={[{ required: true, message: 'Sebep gereklidir!' }]}
          >
            <Select placeholder="Sebep seçin">
              <Option value="purchase">Satın alma</Option>
              <Option value="return">İade</Option>
              <Option value="correction">Düzeltme</Option>
              <Option value="damage">Hasar</Option>
              <Option value="loss">Kayıp</Option>
              <Option value="other">Diğer</Option>
            </Select>
          </Form.Item>

          <Form.Item
            label="Notlar"
            name="notes"
          >
            <Input.TextArea rows={3} placeholder="Ek notlar (opsiyonel)" />
          </Form.Item>

          <Form.Item style={{ textAlign: 'right', marginBottom: 0 }}>
            <Space>
              <Button onClick={onAdjustModalClose}>
                İptal
              </Button>
              <Button type="primary" htmlType="submit" loading={isAdjusting}>
                Uygula
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>

      {/* Stok Kullanım Modal */}
      <Modal
        title="Stok Kullanımı"
        open={isUseModalVisible}
        onCancel={onUseModalClose}
        footer={null}
        width={500}
      >
        <Form
          form={useForm}
          layout="vertical"
          onFinish={onUseSubmit}
          initialValues={{
            performed_by: user?.name || '',
            reason: 'treatment'
          }}
        >
          <Alert
            message={`Mevcut Miktar: ${formatStock(
              selectedStock?.current_stock || 0,
              selectedStock?.unit || '',
              selectedStock?.has_sub_unit,
              selectedStock?.current_sub_stock,
              selectedStock?.sub_unit_name
            )}`}
            description={selectedStock?.has_sub_unit ? `Toplam: ${selectedStock.total_base_units} ${selectedStock.sub_unit_name}` : undefined}
            type="info"
            style={{ marginBottom: 16 }}
          />



          <Form.Item
            label={`Kullanılacak Miktar ${selectedStock?.has_sub_unit ? '(' + selectedStock?.sub_unit_name + ' / Doz)' : ''}`}
            name="quantity"
            rules={[
              { required: true, message: 'Miktar gereklidir!' },
              { 
                validator: (_, value) => {
                  const maxAmount = selectedStock?.has_sub_unit ? selectedStock.total_base_units : selectedStock?.current_stock;
                  if (value && selectedStock && maxAmount !== undefined && value > maxAmount) {
                    return Promise.reject('Mevcut stoktan fazla miktar kullanılamaz!')
                  }
                  return Promise.resolve()
                }
              }
            ]}
          >
            <InputNumber 
              min={1} 
              max={selectedStock?.has_sub_unit ? selectedStock.total_base_units : selectedStock?.current_stock}
              style={{ width: '100%' }}
              placeholder="Kullanılacak miktar"
            />
          </Form.Item>

          <Form.Item
            label="Kullanım Sebebi"
            name="reason"
            rules={[{ required: true, message: 'Kullanım sebebi gereklidir!' }]}
          >
            <Select placeholder="Sebep seçin">
              <Option value="treatment">Tedavi</Option>
              <Option value="surgery">Cerrahi</Option>
              <Option value="cleaning">Temizlik</Option>
              <Option value="maintenance">Bakım</Option>
              <Option value="other">Diğer</Option>
            </Select>
          </Form.Item>

          <Form.Item
            label="Kullanan Kişi"
            name="used_by"
          >
            <Input placeholder="Kullanan kişi adı (opsiyonel)" />
          </Form.Item>

          {selectedStock && selectedStock.reserved_stock > 0 && (
            <Form.Item
              name="is_from_reserved"
              valuePropName="checked"
            >
              <Checkbox>
                Rezerve stoktan kullan ({selectedStock.reserved_stock} {selectedStock.unit} rezerve)
              </Checkbox>
            </Form.Item>
          )}

          <Form.Item
            label="Notlar"
            name="notes"
          >
            <Input.TextArea rows={3} placeholder="Ek notlar (opsiyonel)" />
          </Form.Item>

          <Form.Item style={{ textAlign: 'right', marginBottom: 0 }}>
            <Space>
              <Button onClick={onUseModalClose}>
                İptal
              </Button>
              <Button type="primary" htmlType="submit" loading={isUsing}>
                Kullan
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>
    </>
  )
}