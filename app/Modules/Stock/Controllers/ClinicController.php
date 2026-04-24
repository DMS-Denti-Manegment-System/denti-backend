<?php

// ==============================================
// 3. ClinicController
// app/Modules/Stock/Controllers/ClinicController.php
// ==============================================

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\ClinicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClinicController extends Controller
{
    use JsonResponseTrait;
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
            Log::error('Klinikler listelenirken hata oluştu: ' . $e->getMessage());
            return $this->error('Klinikler listelenirken bir hata oluştu.', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10|unique:clinics,code',
            'description' => 'nullable|string',
            'responsible_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'manager_name' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->error('Validasyon hatası', 422, $validator->errors()->toArray());
        }

        try {
            $clinic = $this->clinicService->createClinic($validator->validated());
            return $this->success($clinic, 'Klinik başarıyla oluşturuldu', 201);
        } catch (\Exception $e) {
            Log::error('Klinik oluşturulurken hata: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $clinic = $this->clinicService->getClinicById($id);

            if (!$clinic) {
                return $this->error('Klinik bulunamadı', 404);
            }

            return $this->success($clinic);
        } catch (\Exception $e) {
            Log::error('Klinik getirilirken hata: ' . $e->getMessage());
            return $this->error('Klinik getirilirken bir hata oluştu.', 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:10|unique:clinics,code,' . $id,
            'description' => 'nullable|string',
            'responsible_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'manager_name' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->error('Validasyon hatası', 422, $validator->errors()->toArray());
        }

        try {
            $clinic = $this->clinicService->updateClinic($id, $validator->validated());

            if (!$clinic) {
                return $this->error('Klinik bulunamadı', 404);
            }

            return $this->success($clinic, 'Klinik başarıyla güncellendi');
        } catch (\Exception $e) {
            Log::error('Klinik güncellenirken hata: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $deleted = $this->clinicService->deleteClinic($id);

            if (!$deleted) {
                return $this->error('Klinik bulunamadı veya silme işlemi başarısız', 404);
            }

            return $this->success(null, 'Klinik başarıyla silindi');
        } catch (\Exception $e) {
            Log::error('Klinik silinirken hata: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getActive(): JsonResponse
    {
        try {
            $clinics = $this->clinicService->getActiveClinics();
            return $this->success($clinics);
        } catch (\Exception $e) {
            Log::error('Aktif klinikler getirilirken hata: ' . $e->getMessage());
            return $this->error('Aktif klinikler getirilirken hata oluştu.', 500);
        }
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->clinicService->getClinicStats();
            return $this->success($stats);
        } catch (\Exception $e) {
            Log::error('Klinik istatistikleri getirilirken hata: ' . $e->getMessage());
            return $this->error('İstatistikler getirilirken hata oluştu.', 500);
        }
    }

    public function getStocks($id): JsonResponse
    {
        try {
            $clinic = $this->clinicService->getClinicById($id);

            if (!$clinic) {
                return $this->error('Klinik bulunamadı', 404);
            }

            return $this->success($clinic->stocks);
        } catch (\Exception $e) {
            Log::error('Klinik stokları getirilirken hata: ' . $e->getMessage());
            return $this->error('Klinik stokları getirilirken hata oluştu.', 500);
        }
    }

    public function getSummary($id): JsonResponse
    {
        try {
            $summary = $this->clinicService->getClinicStockSummary($id);
            return $this->success($summary);
        } catch (\Exception $e) {
            Log::error('Klinik özeti getirilirken hata: ' . $e->getMessage());
            return $this->error('Klinik özeti getirilirken hata oluştu.', 500);
        }
    }
}