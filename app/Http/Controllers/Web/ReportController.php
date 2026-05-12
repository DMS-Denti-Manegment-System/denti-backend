<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Product;
use App\Models\StockRequest;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\Todo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $clinicId = $request->integer('clinic_id');
        $dateFrom = $request->string('date_from')->value();
        $dateTo = $request->string('date_to')->value();

        $summary = [
            'products' => Product::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'clinics' => Clinic::query()->count(),
            'alerts' => app(\App\Services\AlertService::class)->getDynamicAlerts()->total(),
            'pending_requests' => StockRequest::query()->where('status', 'pending')->count(),
            'open_todos' => Todo::query()->where('completed', false)->count(),
        ];

        $movements = StockTransaction::query()
            ->with(['clinic', 'stock.product', 'user'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->when($dateFrom, fn ($q) => $q->whereDate('transaction_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('transaction_date', '<=', $dateTo))
            ->latest('transaction_date')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        $clinics = Clinic::query()->active()->get(['id', 'name']);

        return view('operations.reports.index', compact('summary', 'movements', 'clinics'));
    }
}
