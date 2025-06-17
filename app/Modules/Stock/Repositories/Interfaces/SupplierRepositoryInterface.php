<?php

// ==============================================
// 2. SupplierRepositoryInterface
// app/Modules/Stock/Repositories/Interfaces/SupplierRepositoryInterface.php
// ==============================================

namespace App\Modules\Stock\Repositories\Interfaces;

use App\Modules\Stock\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;

interface SupplierRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Supplier;
    public function create(array $data): Supplier;
    public function update(int $id, array $data): ?Supplier;
    public function delete(int $id): bool;
    public function getActive(): Collection;
    public function search(string $term): Collection;
}