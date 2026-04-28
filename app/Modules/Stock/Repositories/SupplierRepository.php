<?php

// ==============================================
// 2. SupplierRepository
// app/Modules/Stock/Repositories/SupplierRepository.php
// ==============================================

namespace App\Modules\Stock\Repositories;

use App\Modules\Stock\Models\Supplier;
use App\Modules\Stock\Repositories\Interfaces\SupplierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository implements SupplierRepositoryInterface
{
    protected $model;

    public function __construct(Supplier $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->orderBy('name')->get();
    }

    public function getAllWithFilters(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($filters['search']) || !empty($filters['name'])) {
            $search = '%' . ($filters['search'] ?? $filters['name']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('contact_person', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') $query->where('is_active', true);
            if ($filters['status'] === 'inactive') $query->where('is_active', false);
        }

        return $query->orderBy('name')->get();
    }

    public function find(int $id): ?Supplier
    {
        return $this->model->with(['stocks'])->find($id);
    }

    public function create(array $data): Supplier
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Supplier
    {
        $supplier = $this->find($id);
        if ($supplier) {
            $supplier->update($data);
            return $supplier;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $supplier = $this->find($id);
        if (!$supplier) return false;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($supplier) {
            // Tedarikçi silindiğinde stoklarını da pasif/silinmiş yapalım
            $supplier->stocks()->delete(); 
            return $supplier->delete();
        });
    }

    public function getActive(): Collection
    {
        return $this->model->active()->orderBy('name')->get();
    }

    public function search(string $term): Collection
    {
        $search = '%' . $term . '%';
        return $this->model->where('name', 'like', $search)
                          ->orWhere('contact_person', 'like', $search)
                          ->orWhere('email', 'like', $search)
                          ->orderBy('name')
                          ->get();
    }
}