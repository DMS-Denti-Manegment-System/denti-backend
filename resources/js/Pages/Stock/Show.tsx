import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { 
    Card, 
    Typography, 
    Row, 
    Col, 
    Statistic, 
    Tag, 
    Button, 
    Space, 
    Tabs,
    Divider,
    Breadcrumb,
    Modal
} from 'antd';
import { 
    ArrowLeftOutlined, 
    PlusOutlined, 
    DatabaseOutlined, 
    HistoryOutlined,
    InfoCircleOutlined,
    LineChartOutlined
} from '@ant-design/icons';
import { Product, Stock as StockBatch } from '@/Modules/stock/Types/stock.types';
import { StockTable } from '@/Modules/stock/Components/StockTable';
import { useProductDetail, useStocks, useStockTransactions } from '@/Modules/stock/Hooks/useStocks';
import { BatchForm } from '@/Modules/stock/Components/BatchForm';
import { StockModals } from '@/Modules/stock/Components/StockModals';
import { TransactionHistoryTable } from '@/Modules/stock/Components/TransactionHistoryTable';
import { StockTrendChart } from '@/Modules/stock/Components/StockTrendChart';
import { Form } from 'antd';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

interface Props {
    product: Product;
}

const ProductShow = ({ product: initialProduct }: Props) => {
    const { product, isLoading, addBatch, isAddingBatch } = useProductDetail(initialProduct.id);
    const { transactions, isLoading: isHistoryLoading } = useStockTransactions(initialProduct.id);
    const { adjustStock, useStock: executeStockUsage, isAdjusting, isUsing } = useStocks();
    
    const [isAddBatchModalVisible, setIsAddBatchModalVisible] = useState(false);
    const [isAdjustModalVisible, setIsAdjustModalVisible] = useState(false);
    const [isUseModalVisible, setIsUseModalVisible] = useState(false);
    const [selectedBatch, setSelectedBatch] = useState<StockBatch | null>(null);
    
    const [adjustForm] = Form.useForm();
    const [useForm] = Form.useForm();

    // If hook is loading, use initial data from Inertia
    const data = product || initialProduct;

    const handleAddBatch = async (values: any) => {
        await addBatch(values);
        setIsAddBatchModalVisible(false);
    };

    const handleAdjust = (batch: StockBatch) => {
        setSelectedBatch(batch);
        setIsAdjustModalVisible(true);
    };

    const handleUse = (batch: StockBatch) => {
        setSelectedBatch(batch);
        setIsUseModalVisible(true);
    };

    return (
        <>
            <Head title={`${data.name} - Ürün Detayı`} />
            
            <div style={{ marginBottom: 16 }}>
                <Breadcrumb items={[
                    { title: <a href="/stocks">Stok Yönetimi</a> },
                    { title: data.name }
                ]} />
            </div>

            <Space orientation="vertical" size="large" style={{ width: '100%' }}>
                {/* Header Card */}
                <Card variant="borderless" className="premium-card">
                    <Row gutter={24} align="middle">
                        <Col flex="auto">
                            <Space align="center" size="middle">
                                <Button 
                                    icon={<ArrowLeftOutlined />} 
                                    onClick={() => window.location.href = '/stocks'} 
                                />
                                <div>
                                    <Title level={2} style={{ margin: 0 }}>{data.name}</Title>
                                    <Text type="secondary">{data.category} | SKU: {data.sku || 'Belirtilmedi'}</Text>
                                </div>
                                <Tag color={data.is_active ? 'green' : 'red'}>
                                    {data.is_active ? 'Aktif' : 'Pasif'}
                                </Tag>
                            </Space>
                        </Col>
                        <Col>
                            {data.has_expiration_date ? (
                                <Button type="primary" icon={<PlusOutlined />} size="large" onClick={() => setIsAddBatchModalVisible(true)}>
                                    Yeni Parti Ekle
                                </Button>
                            ) : (
                                <Button 
                                    type="primary" 
                                    icon={<PlusOutlined />} 
                                    size="large" 
                                    onClick={() => {
                                        if (data.batches && data.batches.length > 0) {
                                            handleAdjust(data.batches[0]);
                                        } else {
                                            setIsAddBatchModalVisible(true);
                                        }
                                    }}
                                >
                                    Stok Girişi Yap
                                </Button>
                            )}
                        </Col>
                    </Row>
                    
                    <Divider />
                    
                    <Row gutter={48}>
                        <Col span={6}>
                            <Statistic 
                                title="Toplam Stok" 
                                value={data.total_stock} 
                                suffix={data.unit} 
                                prefix={<DatabaseOutlined />}
                            />
                        </Col>
                        <Col span={6}>
                            <Statistic 
                                title="Parti Sayısı" 
                                value={data.batches?.length || 0} 
                            />
                        </Col>
                        <Col span={6}>
                            <Statistic 
                                title="Minimum Seviye" 
                                value={data.min_stock_level} 
                                styles={{ content: { color: '#faad14' } }}
                            />
                        </Col>
                        <Col span={6}>
                            <Statistic 
                                title="Kritik Seviye" 
                                value={data.critical_stock_level} 
                                styles={{ content: { color: '#ff4d4f' } }}
                            />
                        </Col>
                    </Row>
                </Card>

                {/* Content Tabs */}
                <Tabs
                    defaultActiveKey={data.has_expiration_date ? 'batches' : 'history'}
                    items={[
                        ...(data.has_expiration_date ? [{
                            key: 'batches',
                            label: <span><DatabaseOutlined /> Stok Partileri</span>,
                            children: (
                                <Card variant="borderless" className="premium-card">
                                    <StockTable 
                                        stocks={data.batches || []}
                                        loading={isLoading}
                                        isBatchMode={true}
                                        onEdit={() => {}}
                                        onDelete={() => {}}
                                        onSoftDelete={() => {}}
                                        onHardDelete={() => {}}
                                        onReactivate={() => {}}
                                        onAdjust={handleAdjust}
                                        onUse={handleUse}
                                        onViewHistory={() => {}}
                                    />
                                </Card>
                            )
                        }] : []),
                        {
                            key: 'history',
                            label: <span><HistoryOutlined /> İşlem Geçmişi</span>,
                            children: (
                                <Card variant="borderless" className="premium-card">
                                    <TransactionHistoryTable 
                                        transactions={transactions || []}
                                        loading={isHistoryLoading}
                                    />
                                </Card>
                            )
                        },
                        {
                            key: 'report',
                            label: <span><LineChartOutlined /> Grafik / Analiz</span>,
                            children: (
                                <Card variant="borderless" className="premium-card">
                                    <Title level={5}>Stok Değişim Trendi</Title>
                                    <StockTrendChart transactions={transactions || []} />
                                </Card>
                            )
                        },
                        {
                            key: 'info',
                            label: <span><InfoCircleOutlined /> Ürün Bilgileri</span>,
                            children: (
                                <Card variant="borderless" className="premium-card">
                                    <Row gutter={[32, 24]}>
                                        <Col span={12}>
                                            <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                                                <div>
                                                    <Text type="secondary">Ürün Adı</Text>
                                                    <Title level={5} style={{ margin: 0 }}>{data.name}</Title>
                                                </div>
                                                <div>
                                                    <Text type="secondary">Kategori</Text>
                                                    <div><Tag color="blue">{data.category || 'Belirtilmedi'}</Tag></div>
                                                </div>
                                                <div>
                                                    <Text type="secondary">SKU / Barkod</Text>
                                                    <div><Text strong>{data.sku || '-'}</Text></div>
                                                </div>
                                            </Space>
                                        </Col>
                                        <Col span={12}>
                                            <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                                                <div>
                                                    <Text type="secondary">Marka</Text>
                                                    <div><Text strong>{data.brand || '-'}</Text></div>
                                                </div>
                                                <div>
                                                    <Text type="secondary">Takip Tipi</Text>
                                                    <div>
                                                        <Tag color={data.has_expiration_date ? 'purple' : 'orange'}>
                                                            {data.has_expiration_date ? 'Parti / SKT Takibi' : 'Genel Stok Takibi'}
                                                        </Tag>
                                                    </div>
                                                </div>
                                                <div>
                                                    <Text type="secondary">Birim</Text>
                                                    <div><Text strong>{data.unit}</Text></div>
                                                </div>
                                            </Space>
                                        </Col>
                                        <Col span={24}>
                                            <Divider style={{ margin: '8px 0' }} />
                                            <Title level={5} style={{ marginBottom: 16 }}>Finansal Bilgiler</Title>
                                            <Row gutter={24}>
                                                <Col span={8}>
                                                    <Statistic 
                                                        title="Ağırlıklı Ortalama Alış Fiyatı" 
                                                        value={(() => {
                                                            const validBatches = (data.batches || []).filter(b => b.current_stock > 0);
                                                            if (validBatches.length === 0) return 0;
                                                            const totalValue = validBatches.reduce((sum, b) => sum + (Number(b.purchase_price) * b.current_stock), 0);
                                                            const totalQty = validBatches.reduce((sum, b) => sum + b.current_stock, 0);
                                                            return totalQty > 0 ? (totalValue / totalQty).toFixed(2) : 0;
                                                        })()} 
                                                        suffix={data.batches?.[0]?.currency || 'TRY'} 
                                                    />
                                                </Col>
                                                <Col span={8}>
                                                    <Statistic 
                                                        title="Mevcut Stok Değeri" 
                                                        value={(data.batches || []).reduce((sum, b) => sum + (Number(b.purchase_price) * b.current_stock), 0).toFixed(2) || 0} 
                                                        suffix={data.batches?.[0]?.currency || 'TRY'}
                                                        styles={{ content: { color: '#52c41a' } }}
                                                    />
                                                </Col>
                                                <Col span={8}>
                                                    <Statistic 
                                                        title="Son Alış Fiyatı" 
                                                        value={(() => {
                                                            if (!data.batches || data.batches.length === 0) return 0;
                                                            // Sort by purchase_date descending to find the latest
                                                            const sorted = [...data.batches].sort((a, b) => dayjs(b.purchase_date).unix() - dayjs(a.purchase_date).unix());
                                                            return sorted[0]?.purchase_price || 0;
                                                        })()} 
                                                        suffix={data.batches?.[0]?.currency || 'TRY'} 
                                                    />
                                                </Col>
                                            </Row>
                                        </Col>
                                        <Col span={24}>
                                            <Divider style={{ margin: '8px 0' }} />
                                            <Text type="secondary">Açıklama</Text>
                                            <div style={{ marginTop: 8 }}>
                                                <Text>{data.description || 'Açıklama belirtilmedi.'}</Text>
                                            </div>
                                        </Col>
                                    </Row>
                                </Card>
                            )
                        }
                    ]}
                />
            </Space>

            <Modal
                title={`${data.name} - Yeni Stok Girişi (Parti)`}
                open={isAddBatchModalVisible}
                onCancel={() => setIsAddBatchModalVisible(false)}
                footer={null}
                width={600}
                destroyOnHidden
            >
                <BatchForm 
                    productId={data.id} 
                    onSubmit={handleAddBatch}
                    onCancel={() => setIsAddBatchModalVisible(false)}
                    isSubmitting={isAddingBatch}
                    lockedClinicId={data.clinic_id}
                />
            </Modal>

            <StockModals 
                isFormModalVisible={false}
                editingStock={null}
                onFormModalClose={() => {}}
                onFormSuccess={() => {}}
                
                isAdjustModalVisible={isAdjustModalVisible}
                selectedStock={selectedBatch}
                adjustForm={adjustForm}
                onAdjustModalClose={() => setIsAdjustModalVisible(false)}
                onAdjustSubmit={async (values) => {
                    if (selectedBatch) {
                        await adjustStock({ id: selectedBatch.id, data: values });
                        setIsAdjustModalVisible(false);
                        adjustForm.resetFields();
                    }
                }}
                isAdjusting={isAdjusting}

                isUseModalVisible={isUseModalVisible}
                useForm={useForm}
                onUseModalClose={() => setIsUseModalVisible(false)}
                onUseSubmit={async (values) => {
                    if (selectedBatch) {
                        await executeStockUsage({ id: selectedBatch.id, data: values });
                        setIsUseModalVisible(false);
                        useForm.resetFields();
                    }
                }}
                isUsing={isUsing}
            />
        </>
    );
};

ProductShow.layout = (page: React.ReactNode) => <AppLayout children={page} />;

export default ProductShow;
