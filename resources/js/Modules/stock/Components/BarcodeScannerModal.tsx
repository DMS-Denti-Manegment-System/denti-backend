// src/modules/stock/Components/BarcodeScannerModal.tsx

import React, { useState, useRef, useEffect } from 'react'
import { Modal, Input, Typography, Space, Alert, Button, Spin } from 'antd'
import { BarcodeOutlined, ScanOutlined } from '@ant-design/icons'
import { useStocks, useProducts } from '../Hooks/useStocks'
import { useAuth } from '@/Modules/auth/Hooks/useAuth'

const { Text, Title } = Typography

interface BarcodeScannerModalProps {
  visible: boolean
  onClose: () => void
}

export const BarcodeScannerModal: React.FC<BarcodeScannerModalProps> = ({ visible, onClose }) => {
  const [barcode, setBarcode] = useState('')
  const [lastProcessed, setLastProcessed] = useState<any>(null)
  const [error, setError] = useState<string | null>(null)
  const [isProcessing, setIsProcessing] = useState(false)
  const inputRef = useRef<any>(null)
  
  const { user } = useAuth()
  const { products } = useProducts()
  const { useStock } = useStocks()

  useEffect(() => {
    if (visible) {
      setTimeout(() => inputRef.current?.focus(), 300)
    }
  }, [visible])

  const handleScan = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!barcode) return

    setIsProcessing(true)
    setError(null)

    try {
      // Find product by SKU
      const product = products?.find((p: any) => p.sku === barcode || p.barcode === barcode)
      
      if (!product) {
        setError(`Ürün bulunamadı: ${barcode}`)
        setBarcode('')
        return
      }

      // Find first available batch (not expired, has stock)
      const batch = product.stocks?.find((b: any) => 
        b.current_stock > 0 && 
        (!b.expiry_date || new Date(b.expiry_date) > new Date())
      )

      if (!batch) {
        setError(`Kullanılabilir stok bulunamadı (Tüm partiler boş veya SKT geçmiş): ${product.name}`)
        setBarcode('')
        return
      }

      // Use 1 unit
      await useStock({
        id: batch.id,
        data: {
          quantity: 1,
          reason: 'treatment',
          notes: 'Barkod ile hızlı tüketim',
          performed_by: user?.name || 'Sistem'
        }
      })

      setLastProcessed({
        productName: product.name,
        batchId: batch.id,
        time: new Date().toLocaleTimeString()
      })
      setBarcode('')
      inputRef.current?.focus()
    } catch (err: any) {
      setError(err.response?.data?.message || 'İşlem sırasında bir hata oluştu')
    } finally {
      setIsProcessing(false)
    }
  }

  return (
    <Modal
      title={
        <Space>
          <BarcodeOutlined />
          <span>Barkod ile Hızlı Stok Düşümü</span>
        </Space>
      }
      open={visible}
      onCancel={onClose}
      footer={[
        <Button key="close" onClick={onClose}>Kapat</Button>
      ]}
      width={600}
      destroyOnHidden
    >
      <div style={{ textAlign: 'center', padding: '20px 0' }}>
        <Title level={4}>Barkodu Okutun</Title>
        <Text type="secondary">Barkod okuyucuyu bilgisayara bağlayın ve ürünü okutun.</Text>
        
        <form onSubmit={handleScan} style={{ marginTop: 24 }}>
          <Input
            ref={inputRef}
            prefix={<ScanOutlined style={{ color: '#1890ff' }} />}
            placeholder="Okutulan barkod burada görünecek..."
            size="large"
            value={barcode}
            onChange={(e) => setBarcode(e.target.value)}
            autoFocus
            disabled={isProcessing}
            style={{ width: '80%', height: 50, fontSize: 18 }}
          />
        </form>

        {isProcessing && (
          <div style={{ marginTop: 16 }}>
            <Spin tip="İşleniyor..." />
          </div>
        )}

        {error && (
          <Alert
            message={error}
            type="error"
            showIcon
            closable
            onClose={() => setError(null)}
            style={{ marginTop: 24, textAlign: 'left' }}
          />
        )}

        {lastProcessed && (
          <div style={{ marginTop: 24, textAlign: 'left', background: '#f6ffed', padding: 16, borderRadius: 8, border: '1px solid #b7eb8f' }}>
            <Title level={5} style={{ color: '#52c41a', marginTop: 0 }}>
              ✅ Başarıyla Düştü
            </Title>
            <Space direction="vertical" style={{ width: '100%' }}>
              <Text><strong>Ürün:</strong> {lastProcessed.productName}</Text>
              <Text><strong>Miktar:</strong> 1 Adet</Text>
              <Text type="secondary" style={{ fontSize: 12 }}>Saat: {lastProcessed.time}</Text>
            </Space>
          </div>
        )}
      </div>

      <div style={{ marginTop: 32 }}>
        <Text strong>İpucu:</Text>
        <ul style={{ paddingLeft: 20, color: '#666', marginTop: 8 }}>
          <li>Barkod okuyucunun sonuna 'Enter' karakteri eklediğinden emin olun.</li>
          <li>Sistem, SKT'si en yakın olan ve stoğu bulunan partiyi otomatik seçer.</li>
          <li>Eğer ürünün SKT'si geçmişse işlem otomatik olarak reddedilir.</li>
        </ul>
      </div>
    </Modal>
  )
}
