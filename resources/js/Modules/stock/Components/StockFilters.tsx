// src/modules/stock/Components/StockFilters.tsx

import React, { useState } from "react";
import { Card, Row, Col, Input, Select, Button, Space } from "antd";
import { PlusOutlined, TagsOutlined, BarcodeOutlined } from "@ant-design/icons";
import { router } from "@inertiajs/react";
import { StockFilter } from "../Types/stock.types";
import { useCategories } from "@/Modules/category/Hooks/useCategories";
import { useClinics } from "@/Modules/clinics/Hooks/useClinics";

const { Search } = Input;
const { Option } = Select;

interface StockFiltersProps {
    onSearch: (value: string) => void;
    onFilterChange: (
        field: keyof StockFilter,
        value: string | number | undefined,
    ) => void;
    onAdd: () => void;
    onScannerOpen: () => void;
}

export const StockFilters: React.FC<StockFiltersProps> = ({
    onSearch,
    onFilterChange,
    onAdd,
    onScannerOpen,
}) => {
    const [shouldLoadCategories, setShouldLoadCategories] = useState(false);
    const [shouldLoadClinics, setShouldLoadClinics] = useState(false);
    const { categories, isLoading: isCategoriesLoading } = useCategories(shouldLoadCategories);
    const { clinics, isLoading: isClinicsLoading } = useClinics(undefined, shouldLoadClinics);

    const levelOptions = [
        { label: "Normal", value: "normal" },
        { label: "Düşük Stok (Sarı)", value: "low" },
        { label: "Kritik Stok (Kırmızı)", value: "critical" },
        { label: "Yaklaşan SKT (Sarı)", value: "near_expiry" },
        { label: "Kritik SKT (Kırmızı)", value: "critical_expiry" },
        { label: "Süresi Geçmiş", value: "expired" },
    ];

    return (
        <Card style={{ marginBottom: 24 }} className="premium-card">
            <Row gutter={[16, 16]} align="middle">
                <Col xs={24} md={6} lg={5}>
                    <Search
                        placeholder="Stok adı veya SKU..."
                        onSearch={onSearch}
                        onChange={(e) => onSearch(e.target.value)}
                        style={{ width: "100%" }}
                        allowClear
                    />
                </Col>

                <Col xs={12} md={6} lg={4}>
                    <Select
                        placeholder="Klinik Filtresi"
                        style={{ width: "100%" }}
                        allowClear
                        loading={isClinicsLoading}
                        popupMatchSelectWidth={false}
                        onOpenChange={(open) => {
                            if (open) setShouldLoadClinics(true);
                        }}
                        onFocus={() => setShouldLoadClinics(true)}
                        onChange={(value) => onFilterChange("clinic_id", value)}
                    >
                        {(clinics ?? []).map((clinic) => (
                            <Option key={clinic.id} value={clinic.id}>
                                {clinic.name}
                            </Option>
                        ))}
                    </Select>
                </Col>

                <Col xs={12} md={4} lg={3}>
                    <Select
                        placeholder="Kategori"
                        style={{ width: "100%" }}
                        allowClear
                        loading={isCategoriesLoading}
                        popupMatchSelectWidth={false}
                        onOpenChange={(open) => {
                            if (open) setShouldLoadCategories(true);
                        }}
                        onFocus={() => setShouldLoadCategories(true)}
                        onChange={(value) => onFilterChange("category", value)}
                    >
                        {(categories ?? []).map((option: any) => (
                            <Option key={option.id} value={option.name}>
                                {option.name}
                            </Option>
                        ))}
                    </Select>
                </Col>

                <Col xs={12} md={4} lg={3}>
                    <Select
                        placeholder="Seviye"
                        style={{ width: "100%" }}
                        allowClear
                        popupMatchSelectWidth={false}
                        onChange={(value) => onFilterChange("level", value)}
                    >
                        {levelOptions.map((option: any) => (
                            <Option key={option.value} value={option.value}>
                                {option.label}
                            </Option>
                        ))}
                    </Select>
                </Col>

                <Col xs={12} md={4} lg={3}>
                    <Select
                        placeholder="Durum"
                        style={{ width: "100%" }}
                        allowClear
                        popupMatchSelectWidth={false}
                        onChange={(value) => onFilterChange("status", value)}
                    >
                        <Option value="active">Aktif</Option>
                        <Option value="inactive">Pasif</Option>
                    </Select>
                </Col>

                <Col xs={24} md={24} lg={5} style={{ textAlign: "right" }}>
                    <Button
                        type="primary"
                        icon={<PlusOutlined />}
                        onClick={onAdd}
                        style={{ width: '100%', maxWidth: '160px' }}
                    >
                        Yeni Stok
                    </Button>
                </Col>
            </Row>
        </Card>
    );
};
