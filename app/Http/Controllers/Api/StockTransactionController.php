<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
use App\Services\StockService;
use App\Services\StockTransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StockTransactionController extends Controller
{
    public function __construct(protected StockTransactionService $stockTransactionService) {}

    public function index(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $clinicId = $request->query('clinic_id');
        $type = $request->query('type');
        $perPage = min((int) $request->query('per_page', 50), 100);

        $query = StockTransaction::query()->with(['stock', 'clinic', 'stock.product']);

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [Carbon::parse($startDate), Carbon::parse($endDate)]);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $this->success(
            $query->orderByDesc('transaction_date')->paginate($perPage),
            'Success',
            200,
            ['pagination' => true]
        );
    }

    public function show($id)
    {
        $transaction = $this->stockTransactionService->getTransactionById($id);

        if (! $transaction) {
            return $this->error('Islem bulunamadi', 404);
        }

        return $this->success($transaction);
    }

    public function getByStock($stockId)
    {
        return $this->success($this->stockTransactionService->getTransactionsByStock($stockId));
    }

    public function getByClinic($clinicId)
    {
        return $this->success($this->stockTransactionService->getTransactionsByClinic($clinicId));
    }

    public function reverse($id)
    {
        $success = app(StockService::class)->reverseTransaction($id);

        if (! $success) {
            return $this->error('Islem geri alinamadi', 400);
        }

        return $this->success(null, 'Islem basariyla geri alindi');
    }
}
