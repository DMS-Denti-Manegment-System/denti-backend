<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Traits\HandlesOperationsResponses;
use App\Services\AlertService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    use HandlesOperationsResponses;

    protected $service;

    public function __construct(AlertService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): View|JsonResponse
    {
        $alerts = $this->service->getDynamicAlerts($request->all(), $this->perPage($request));

        return $this->moduleResponse(
            $request,
            'operations.alerts.index',
            ['alerts' => $alerts],
            'operations.alerts.table.index'
        );
    }

    public function resolve(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Dinamik uyarılar manuel çözülemez, stok miktarını güncelleyin.']);
    }

    public function sync(): RedirectResponse
    {
        return redirect()->route('alerts.index')->with('status', 'Uyarılar her an günceldir (Sistem gerçek zamanlı çalışıyor).');
    }

    public function settings(): View
    {
        return view('operations.alerts.settings');
    }
}
