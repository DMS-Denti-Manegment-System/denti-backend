import React from 'react';
import { Typography } from 'antd';
import { 
    AreaChart, 
    Area, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip as ChartTooltip, 
    ResponsiveContainer 
} from 'recharts';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

interface StockTrendChartProps {
    transactions: any[];
}

export const StockTrendChart: React.FC<StockTrendChartProps> = ({ transactions }) => {
    if (!transactions || transactions.length === 0) {
        return (
            <div style={{ padding: '40px 0', textAlign: 'center' }}>
                <Text type="secondary">Bu ürün için henüz işlem geçmişi bulunmuyor.</Text>
            </div>
        );
    }

    const data = transactions.slice().reverse().map((t: any) => ({
        name: dayjs(t.transaction_date).format('DD/MM HH:mm'),
        stok: t.new_stock,
        miktar: t.quantity,
        tip: t.type_text
    }));

    return (
        <div style={{ height: 350, width: '100%', marginTop: 24 }}>
            <ResponsiveContainer width="100%" height="100%">
                <AreaChart
                    data={data}
                    margin={{ top: 10, right: 30, left: 0, bottom: 0 }}
                >
                    <defs>
                        <linearGradient id="colorStok" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="5%" stopColor="#1890ff" stopOpacity={0.1}/>
                            <stop offset="95%" stopColor="#1890ff" stopOpacity={0}/>
                        </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f0f0f0" />
                    <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#8c8c8c' }} />
                    <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#8c8c8c' }} />
                    <ChartTooltip 
                        contentStyle={{ borderRadius: 8, border: 'none', boxShadow: '0 4px 12px rgba(0,0,0,0.1)' }}
                    />
                    <Area 
                        type="monotone" 
                        dataKey="stok" 
                        stroke="#1890ff" 
                        strokeWidth={2}
                        fillOpacity={1} 
                        fill="url(#colorStok)" 
                        activeDot={{ r: 6, strokeWidth: 0 }}
                    />
                </AreaChart>
            </ResponsiveContainer>
        </div>
    );
};
