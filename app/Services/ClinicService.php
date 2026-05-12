<?php

// ==============================================
// 2. ClinicService.php
// app/Modules/Stock/Services/ClinicService.php
// ==============================================

namespace App\Services;

use App\Models\Clinic;
use App\Repositories\Interfaces\ClinicRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ClinicService
{
    protected $clinicRepository;

    public function __construct(ClinicRepositoryInterface $clinicRepository)
    {
        $this->clinicRepository = $clinicRepository;
    }

    public function getAllClinics()
    {
        return Cache::remember('all_clinics', 300, function () {
            return $this->clinicRepository->all();
        });
    }

    public function getAllWithFilters(array $filters)
    {
        return $this->clinicRepository->getAllWithFilters($filters);
    }

    public function getActiveClinics(): Collection
    {
        return $this->clinicRepository->getActive();
    }

    public function getClinicById(int $id): ?Clinic
    {
        return $this->clinicRepository->find($id);
    }

    public function createClinic(array $data): Clinic
    {
        return $this->clinicRepository->create($data);
    }

    public function updateClinic(int $id, array $data): ?Clinic
    {
        return $this->clinicRepository->update($id, $data);
    }

    public function deleteClinic(int $id): bool
    {
        return $this->clinicRepository->delete($id);
    }

    public function getClinicStockSummary(int $clinicId): array
    {
        return $this->clinicRepository->getStockSummary($clinicId);
    }

    public function getClinicStats(): array
    {
        return $this->clinicRepository->getGlobalStats();
    }
}
