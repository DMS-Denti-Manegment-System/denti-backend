import React from 'react';
import { Table, Tag, Typography } from 'antd';
import dayjs from 'dayjs';

const { Text } = Typography;

interface TransactionHistoryTableProps {
    transactions: any[];
    loading: boolean;
}

export const TransactionHistoryTable: React.FC<TransactionHistoryTableProps> = ({ transactions, loading }) => {
    return (
        <Table 
            dataSource={transactions || []}
            loading={loading}
            size="small"
            rowKey="id"
            columns={[
                {
                    title: 'Tarih',
                    dataIndex: 'transaction_date',
                    render: (date) => dayjs(date).format('DD/MM/YYYY HH:mm')
                },
                {
                    title: 'İşlem',
                    dataIndex: 'type',
                    render: (type) => {
                        const types: any = {
                            'purchase': { label: 'Alım', color: 'green' },
                            'usage': { label: 'Kullanım', color: 'blue' },
                            'adjustment': { label: 'Düzeltme', color: 'orange' },
                            'adjustment_increase': { label: 'Stok Artışı', color: 'green' },
                            'adjustment_decrease': { label: 'Stok Azalışı', color: 'red' },
                            'transfer_in': { label: 'Transfer (Gelen)', color: 'cyan' },
                            'transfer_out': { label: 'Transfer (Giden)', color: 'magenta' }
                        };
                        const config = types[type] || { label: type, color: 'default' };
                        return <Tag color={config.color}>{config.label}</Tag>;
                    }
                },
                {
                    title: 'Miktar',
                    dataIndex: 'quantity',
                    render: (qty, record) => (
                        <Text strong style={{ color: record.type === 'purchase' || record.type === 'transfer_in' ? '#52c41a' : '#ff4d4f' }}>
                            {record.type === 'purchase' || record.type === 'transfer_in' ? '+' : '-'}{qty}
                        </Text>
                    )
                },
                {
                    title: 'Yeni Stok',
                    dataIndex: 'new_stock',
                    render: (val) => <Text strong>{val}</Text>
                },
                {
                    title: 'İşlemi Yapan',
                    dataIndex: 'performed_by'
                },
                {
                    title: 'Açıklama',
                    dataIndex: 'description',
                    ellipsis: true
                }
            ]}
        />
    );
};
