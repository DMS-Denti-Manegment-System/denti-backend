<?php
// ==============================================
// 1. StockRequestRepository.php - DÜZELTİLMİŞ
// app/Modules/Stock/Repositories/StockRequestRepository.php
// ==============================================

namespace App\Modules\Stock\Repositories;

use App\Modules\Stock\Models\StockRequest;
use App\Modules\Stock\Repositories\Interfaces\StockRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StockRequestRepository implements StockRequestRepositoryInterface
{
    protected $model;

    public function __construct(StockRequest $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->with(['requesterClinic', 'requestedFromClinic', 'stock'])
                          ->orderByDesc('requested_at')
                          ->get();
    }

    public function find(int $id): ?StockRequest
    {
        return $this->model->with(['requesterClinic', 'requestedFromClinic', 'stock'])->find($id);
    }

    public function create(array $data): StockRequest
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?StockRequest
    {
        $request = $this->find($id);
        if ($request) {
            $request->update($data);
            return $request->fresh(['requesterClinic', 'requestedFromClinic', 'stock']);
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $request = $this->find($id);
        return $request ? $request->delete() : false;
    }

    public function getPendingRequests(int $clinicId = null): Collection
    {
        $query = $this->model->pending()->with(['requesterClinic', 'requestedFromClinic', 'stock']);

        if ($clinicId) {
            $query->where('requested_from_clinic_id', $clinicId);
        }

        return $query->orderBy('requested_at')->get();
    }

    public function getRequestsByClinic(int $clinicId, string $type = 'all'): Collection
    {
        $query = $this->model->with(['requesterClinic', 'requestedFromClinic', 'stock']);

        switch ($type) {
            case 'sent':
                $query->where('requester_clinic_id', $clinicId);
                break;
            case 'received':
                $query->where('requested_from_clinic_id', $clinicId);
                break;
            default:
                $query->where(function ($q) use ($clinicId) {
                    $q->where('requester_clinic_id', $clinicId)
                      ->orWhere('requested_from_clinic_id', $clinicId);
                });
        }

        return $query->orderByDesc('requested_at')->get();
    }

    public function getRequestsByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
                          ->with(['requesterClinic', 'requestedFromClinic', 'stock'])
                          ->orderByDesc('requested_at')
                          ->get();
    }
}