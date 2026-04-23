<?php

// ==============================================
// 4. StockRequestController
// app/Modules/Stock/Controllers/StockRequestController.php
// ==============================================

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\StockRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockRequestController extends Controller
{
    protected $stockRequestService;

    public function __construct(StockRequestService $stockRequestService)
    {
        $this->stockRequestService = $stockRequestService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'requester_clinic_id', 'requested_from_clinic_id', 'type']);
        
        $requests = $this->stockRequestService->getAllWithFilters($filters);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'requester_clinic_id' => 'required|exists:clinics,id',
            'requested_from_clinic_id' => 'required|exists:clinics,id|different:requester_clinic_id',
            'stock_id' => 'required|exists:stocks,id',
            'requested_quantity' => 'required|integer|min:1',
            'request_reason' => 'nullable|string|max:500',
            'requested_by' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stockRequest = $this->stockRequestService->createRequest($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Stok talebi başarıyla oluşturuldu',
                'data' => $stockRequest
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
        $request = $this->stockRequestService->getRequestById((int)$id);

        if (!$request) {
            return response()->json([
                'success' => false,
                'message' => 'Talep bulunamadı'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $request
        ]);
    }

    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'approved_quantity' => 'required|integer|min:1',
            'approved_by' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->stockRequestService->approveRequest(
                (int)$id,
                $validator->validated()['approved_quantity'],
                $validator->validated()['approved_by'],
                $validator->validated()['notes'] ?? null
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Talep onaylanamadı. Yetersiz stok veya geçersiz talep.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Talep başarıyla onaylandı'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
            'rejected_by' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->stockRequestService->rejectRequest(
                (int)$id,
                $validator->validated()['rejection_reason'],
                $validator->validated()['rejected_by']
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Talep reddedilemedi'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Talep reddedildi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function complete(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'performed_by' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->stockRequestService->completeRequest(
                (int)$id,
                $validator->validated()['performed_by']
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Talep tamamlanamadı'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfer başarıyla tamamlandı'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getPendingRequests(Request $request)
    {
        $clinicId = $request->query('clinic_id');
        $requests = $this->stockRequestService->getPendingRequests($clinicId);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function getStats()
    {
        $stats = $this->stockRequestService->getRequestStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}