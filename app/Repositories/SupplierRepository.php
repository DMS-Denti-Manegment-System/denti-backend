<?php

// ==============================================
// 2. SupplierRepository
// app/Modules/Stock/Repositories/SupplierRepository.php
// ==============================================

namespace App\Repositories;

use App\Models\Supplier;
use App\Repositories\Interfaces\SupplierRepositoryInterface;
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

    public function getAllWithFilters(array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('contact_person', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->latest()->paginate($perPage);
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
        if (! $supplier) {
            return false;
        }

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
        $search = '%'.$term.'%';

        return $this->model->where('name', 'like', $search)
            ->orWhere('contact_person', 'like', $search)
            ->orWhere('email', 'like', $search)
            ->orderBy('name')
            ->get();
    }

    public function getSupplierStats(): array
    {
        $stats = \Illuminate\Support\Facades\DB::table('suppliers')
            ->whereNull('deleted_at')
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when is_active = true then 1 end) as active')
            ->selectRaw('count(case when is_active = false then 1 end) as passive')
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'active' => (int) ($stats->active ?? 0),
            'passive' => (int) ($stats->passive ?? 0),
        ];
    }
}
