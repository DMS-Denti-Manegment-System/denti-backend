<?php

// ==============================================
// 3. ClinicRepositoryInterface
// app/Modules/Stock/Repositories/Interfaces/ClinicRepositoryInterface.php
// ==============================================

namespace App\Repositories\Interfaces;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Collection;

interface ClinicRepositoryInterface
{
    public function all(): Collection;

    public function getAllWithFilters(array $filters = []): Collection;

    public function find(int $id): ?Clinic;

    public function create(array $data): Clinic;

    public function update(int $id, array $data): ?Clinic;

    public function delete(int $id): bool;

    public function getActive(): Collection;

    public function getStockSummary(int $clinicId): array;

    public function getGlobalStats(): array;
}
