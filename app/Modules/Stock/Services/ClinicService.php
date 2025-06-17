<?php

// ==============================================
// 2. ClinicService.php
// app/Modules/Stock/Services/ClinicService.php
// ==============================================

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Repositories\Interfaces\ClinicRepositoryInterface;
use App\Modules\Stock\Models\Clinic;
use Illuminate\Database\Eloquent\Collection;

class ClinicService
{
    protected $clinicRepository;

    public function __construct(ClinicRepositoryInterface $clinicRepository)
    {
        $this->clinicRepository = $clinicRepository;
    }

    public function getAllClinics(): Collection
    {
        return $this->clinicRepository->all();
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
        // Klinik kodu benzersizlik kontrolü
        if (empty($data['code'])) {
            $data['code'] = $this->generateClinicCode($data['name']);
        }

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

    protected function generateClinicCode(string $name): string
    {
        // İlk 3 harfi al ve büyük harfe çevir
        $code = strtoupper(substr($name, 0, 3));

        // Eğer kod zaten varsa, sayı ekle
        $counter = 1;
        $originalCode = $code;

        while ($this->clinicRepository->findByCode($code)) {
            $code = $originalCode . $counter;
            $counter++;
        }

        return $code;
    }
}