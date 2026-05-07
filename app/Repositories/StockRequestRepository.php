<?php

// ==============================================
// 1. StockRequestRepository.php - DÜZELTİLMİŞ
// app/Modules/Stock/Repositories/StockRequestRepository.php
// ==============================================

namespace App\Repositories;

use App\Models\StockRequest;
use App\Repositories\Interfaces\StockRequestRepositoryInterface;
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

    public function getAllWithFilters(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->with([
            'requesterClinic:id,name,specialty_code',
            'requestedFromClinic:id,name,specialty_code',
            'stock:id,product_id,batch_number,unit,category,brand',
            'stock.product:id,name,sku',
        ]);

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', $search)
                    ->orWhereHas('stock.product', function ($sq) use ($search) {
                        $sq->where('name', 'like', $search)
                            ->orWhere('sku', 'like', $search);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $clinicId = $filters['clinic_id'] ?? null;
        $type = $filters['type'] ?? 'all';

        if ($clinicId) {
            if ($type === 'sent') {
                $query->where('requester_clinic_id', $clinicId);
            } elseif ($type === 'received') {
                $query->where('requested_from_clinic_id', $clinicId);
            } else {
                $query->where(function ($q) use ($clinicId) {
                    $q->where('requester_clinic_id', $clinicId)
                        ->orWhere('requested_from_clinic_id', $clinicId);
                });
            }
        } elseif ($type === 'sent' && ! empty($filters['requester_clinic_id'])) {
            $query->where('requester_clinic_id', $filters['requester_clinic_id']);
        } elseif ($type === 'received' && ! empty($filters['requested_from_clinic_id'])) {
            $query->where('requested_from_clinic_id', $filters['requested_from_clinic_id']);
        }

        return $query->orderByDesc('requested_at')->paginate($perPage);
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

    public function getPendingRequests(?int $clinicId = null): Collection
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

    public function getStats(): array
    {
        return [
            'total' => $this->model->count(),
            'pending' => $this->model->where('status', 'pending')->count(),
            'approved' => $this->model->where('status', 'approved')->count(),
            'in_transit' => $this->model->where('status', 'in_transit')->count(),
            'completed' => $this->model->where('status', 'completed')->count(),
            'rejected' => $this->model->where('status', 'rejected')->count(),
        ];
    }
}
