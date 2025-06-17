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

class ClinicController extends Controller
{
    protected $clinicService;

    public function __construct(ClinicService $clinicService)
    {
        $this->clinicService = $clinicService;
    }

    public function index()
    {
        $clinics = $this->clinicService->getAllClinics();

        return response()->json([
            'success' => true,
            'data' => $clinics
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10|unique:clinics,code',
            'description' => 'nullable|string',
            'responsible_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $clinic = $this->clinicService->createClinic($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Klinik başarıyla oluşturuldu',
                'data' => $clinic
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        $clinic = $this->clinicService->getClinicById($id);

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Klinik bulunamadı'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $clinic
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:10|unique:clinics,code,' . $id,
            'description' => 'nullable|string',
            'responsible_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $clinic = $this->clinicService->updateClinic($id, $validator->validated());

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Klinik bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Klinik başarıyla güncellendi',
                'data' => $clinic
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = $this->clinicService->deleteClinic($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Klinik bulunamadı veya silme işlemi başarısız'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Klinik başarıyla silindi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getActive()
    {
        $clinics = $this->clinicService->getActiveClinics();

        return response()->json([
            'success' => true,
            'data' => $clinics
        ]);
    }

    public function getStocks($id)
    {
        $clinic = $this->clinicService->getClinicById($id);

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Klinik bulunamadı'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $clinic->stocks
        ]);
    }

    public function getSummary($id)
    {
        $summary = $this->clinicService->getClinicStockSummary($id);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}