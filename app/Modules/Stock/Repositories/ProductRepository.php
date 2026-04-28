<?php

namespace App\Modules\Stock\Repositories;

use App\Modules\Stock\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function getAllWithFilters(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = Product::query()->with(['batches', 'clinic']);

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->latest()->paginate($perPage);
    }

    public function find(int $id): ?Product
    {
        return Product::with(['batches.supplier', 'batches.clinic', 'clinic'])->find($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(int $id, array $data): ?Product
    {
        $product = Product::find($id);
        if ($product) {
            $product->update($data);
            return $product;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $product = Product::find($id);
        if ($product) {
            return $product->delete();
        }
        return false;
    }

    public function getTransactions(int $id): Collection
    {
        return \App\Modules\Stock\Models\StockTransaction::with(['user', 'stock'])
            ->whereIn(
                'stock_id', 
                \App\Modules\Stock\Models\Stock::where('product_id', $id)->pluck('id')
            )
            ->latest('transaction_date')
            ->get();
    }
}
