<?php

// ==============================================
// 5. StockTransactionController
// app/Modules/Stock/Controllers/StockTransactionController.php
// ==============================================

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\StockTransactionService;
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

        if ($startDate && $endDate) {
            $transactions = $this->stockTransactionService->getTransactionsByDateRange(
                Carbon::parse($startDate),
                Carbon::parse($endDate),
                $clinicId
            );
        } elseif ($type) {
            $transactions = $this->stockTransactionService->getTransactionsByType($type, $clinicId);
        } else {
            $transactions = $this->stockTransactionService->getAllTransactions();
        }

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
}