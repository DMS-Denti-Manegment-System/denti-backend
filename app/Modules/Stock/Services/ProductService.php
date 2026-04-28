<?php

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Repositories\ProductRepository;
use App\Modules\Stock\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function getAllProducts(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->productRepository->getAllWithFilters($filters, $perPage);
    }

    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    public function createProduct(array $data): Product
    {
        return \DB::transaction(function () use ($data) {
            $stockData = [
                'initial_stock' => $data['initial_stock'] ?? 0,
                'clinic_id' => $data['clinic_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_price' => $data['purchase_price'] ?? null,
                'currency' => $data['currency'] ?? 'TRY',
                'purchase_date' => $data['purchase_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'storage_location' => $data['storage_location'] ?? null,
                'min_stock_level' => $data['min_stock_level'] ?? 10,
                'critical_stock_level' => $data['critical_stock_level'] ?? 5,
                'has_sub_unit' => $data['has_sub_unit'] ?? false,
                'sub_unit_name' => $data['sub_unit_name'] ?? null,
                'sub_unit_multiplier' => $data['sub_unit_multiplier'] ?? null,
            ];

            // Product fields only
            $productFields = ['name', 'sku', 'description', 'unit', 'category', 'brand', 'min_stock_level', 'critical_stock_level', 'is_active', 'has_expiration_date'];
            $productData = array_intersect_key($data, array_flip($productFields));
            
            $product = $this->productRepository->create($productData);

            if ($stockData['clinic_id'] !== null) {
                app(StockService::class)->createStock([
                    'product_id' => $product->id,
                    'clinic_id' => $stockData['clinic_id'],
                    'supplier_id' => $stockData['supplier_id'],
                    'current_stock' => $stockData['initial_stock'],
                    'available_stock' => $stockData['initial_stock'],
                    'purchase_price' => $stockData['purchase_price'],
                    'currency' => $stockData['currency'],
                    'purchase_date' => $stockData['purchase_date'],
                    'expiry_date' => $stockData['expiry_date'],
                    'storage_location' => $stockData['storage_location'],
                    'min_stock_level' => $stockData['min_stock_level'],
                    'critical_stock_level' => $stockData['critical_stock_level'],
                    'company_id' => $product->company_id,
                    'track_expiry' => $product->has_expiration_date,
                    'has_sub_unit' => $stockData['has_sub_unit'],
                    'sub_unit_name' => $stockData['sub_unit_name'],
                    'sub_unit_multiplier' => $stockData['sub_unit_multiplier'],
                ]);
            }

            return $product;
        });
    }

    public function updateProduct(int $id, array $data): ?Product
    {
        return $this->productRepository->update($id, $data);
    }

    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    public function getProductTransactions(int $id)
    {
        return $this->productRepository->getTransactions($id);
    }
}
