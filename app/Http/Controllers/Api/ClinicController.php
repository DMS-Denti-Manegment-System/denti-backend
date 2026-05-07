<?php

// ==============================================
// 3. ClinicController
// app/Modules/Stock/Controllers/ClinicController.php
// ==============================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicRequest;
use App\Http\Requests\UpdateClinicRequest;
use App\Models\Clinic;
use App\Services\ClinicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClinicController extends Controller
{
    protected $clinicService;

    public function __construct(ClinicService $clinicService)
    {
        $this->clinicService = $clinicService;
    }

    public function index(): JsonResponse
    {
        try {
            $clinics = $this->clinicService->getAllClinics();

            return $this->success($clinics);
        } catch (\Exception $e) {
            Log::error('Klinikler listelenirken hata oluştu: '.$e->getMessage());

            return $this->error('Klinikler listelenirken bir hata oluştu.', 500);
        }
    }

    public function store(StoreClinicRequest $request): JsonResponse
    {
        $this->authorize('create', Clinic::class);

        try {
            $validatedData = $request->validated();

            if (! auth()->user()->isSuperAdmin()) {
                $validatedData['company_id'] = auth()->user()->company_id;
            }

            if (empty($validatedData['company_id'])) {
                return $this->error('Klinik oluşturmak için bir şirkete bağlı olmalısınız.', 400);
            }

            $clinic = $this->clinicService->createClinic($validatedData);

            return $this->success($clinic, 'Klinik başarıyla oluşturuldu', 201);
        } catch (\Exception $e) {
            return $this->error('Klinik oluşturulamadı: '.$e->getMessage(), 400);
        }
    }

    public function show(Clinic $clinic): JsonResponse
    {
        $this->authorize('view', $clinic);

        return $this->success($clinic);
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic): JsonResponse
    {
        $this->authorize('update', $clinic);

        try {
            $clinic = $this->clinicService->updateClinic($clinic->id, $request->validated());

            return $this->success($clinic, 'Klinik başarıyla güncellendi');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Clinic $clinic): JsonResponse
    {
        $this->authorize('delete', $clinic);

        try {
            $deleted = $this->clinicService->deleteClinic($clinic->id);

            if (! $deleted) {
                return $this->error('Klinik silme işlemi başarısız.', 400);
            }

            return $this->success(null, 'Klinik başarıyla silindi');
        } catch (\Exception $e) {
            return $this->error('Silme hatası: '.$e->getMessage(), 400);
        }
    }

    public function getActive(): JsonResponse
    {
        try {
            $clinics = $this->clinicService->getActiveClinics();

            return $this->success($clinics);
        } catch (\Exception $e) {
            Log::error('Aktif klinikler getirilirken hata: '.$e->getMessage());

            return $this->error('Aktif klinikler getirilirken hata oluştu.', 500);
        }
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->clinicService->getClinicStats();

            return $this->success($stats);
        } catch (\Exception $e) {
            Log::error('Klinik istatistikleri getirilirken hata: '.$e->getMessage());

            return $this->error('İstatistikler getirilirken hata oluştu.', 500);
        }
    }

    public function getStocks($id): JsonResponse
    {
        try {
            $clinic = $this->clinicService->getClinicById($id);

            if (! $clinic) {
                return $this->error('Klinik bulunamadı', 404);
            }

            $clinic->loadMissing(['stocks.product', 'stocks.supplier']);

            return $this->success($clinic->stocks);
        } catch (\Exception $e) {
            Log::error('Klinik stokları getirilirken hata: '.$e->getMessage());

            return $this->error('Klinik stokları getirilirken hata oluştu.', 500);
        }
    }

    public function getSummary($id): JsonResponse
    {
        try {
            $summary = $this->clinicService->getClinicStockSummary($id);

            return $this->success($summary);
        } catch (\Exception $e) {
            Log::error('Klinik özeti getirilirken hata: '.$e->getMessage());

            return $this->error('Klinik özeti getirilirken hata oluştu.', 500);
        }
    }
}
