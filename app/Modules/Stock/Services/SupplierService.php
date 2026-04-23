<?php
// ==============================================
// 1. SupplierService.php
// app/Modules/Stock/Services/SupplierService.php
// ==============================================

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Repositories\Interfaces\SupplierRepositoryInterface;
use App\Modules\Stock\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;

class SupplierService
{
    protected $supplierRepository;

    public function __construct(SupplierRepositoryInterface $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    public function getAllSuppliers()
    {
        return $this->supplierRepository->all();
    }

    public function getAllWithFilters(array $filters)
    {
        return $this->supplierRepository->getAllWithFilters($filters);
    }

    public function getActiveSuppliers(): Collection
    {
        return $this->supplierRepository->getActive();
    }

    public function getSupplierById(int $id): ?Supplier
    {
        return $this->supplierRepository->find($id);
    }

    public function createSupplier(array $data): Supplier
    {
        return $this->supplierRepository->create($data);
    }

    public function updateSupplier(int $id, array $data): ?Supplier
    {
        return $this->supplierRepository->update($id, $data);
    }

    public function deleteSupplier(int $id): bool
    {
        return $this->supplierRepository->delete($id);
    }

    public function searchSuppliers(string $term): Collection
    {
        return $this->supplierRepository->search($term);
    }
}