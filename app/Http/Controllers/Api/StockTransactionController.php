<?php

// ==============================================
// 5. StockTransactionController
// app/Modules/Stock/Controllers/StockTransactionController.php
// ==============================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockTransactionService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StockTransactionController extends Controller
{
    protected $stockTransactionService;

    public function __construct(StockTransactionService $stockTransactionService)
    {
        $this->stockTransactionService = $stockTransactionService;
    }

    public function index(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $clinicId = $request->query('clinic_id');
        $type = $request->query('type');
        $perPage = min((int)$request->query('per_page', 50), 100);

        $query = $this->stockTransactionService->getBaseQuery()->with(['stock', 'clinic', 'stock.product']);

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [Carbon::parse($startDate), Carbon::parse($endDate)]);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $transactions = $query->orderByDesc('transaction_date')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function show($id)
    {
        $transaction = $this->stockTransactionService->getTransactionById($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'İşlem bulunamadı'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    public function getByStock($stockId)
    {
        $transactions = $this->stockTransactionService->getTransactionsByStock($stockId);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function getByClinic($clinicId)
    {
        $transactions = $this->stockTransactionService->getTransactionsByClinic($clinicId);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function reverse($id)
    {
        $success = app(\App\Services\StockService::class)->reverseTransaction($id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'İşlem geri alınamadı'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'İşlem başarıyla geri alındı'
        ]);
    }
}