<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Traits\HandlesOperationsResponses;
use App\Models\Stock;
use App\Models\Clinic;
use App\Models\StockRequest;
use App\Services\StockRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class StockRequestController extends Controller
{
    use HandlesOperationsResponses;

    protected $service;

    public function __construct(StockRequestService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): View|JsonResponse
    {
        $includeModalData = ! $request->ajax()
            || $request->boolean('include_modal')
            || in_array((string) $request->query('modal'), ['create', 'edit', 'detail'], true);

        $filters = $request->only(['search', 'status', 'clinic_id', 'type']);
        $requests = $this->service->getAllWithFilters($filters, $this->perPage($request));

        $clinicId = $filters['clinic_id'] ?? null;
        if (!$clinicId && !auth()->user()->hasRole('Admin')) {
            $clinicId = auth()->user()->clinic_id;
        }

        $stats = $this->service->getRequestStats($clinicId);

        $viewData = [
            'requests' => $requests,
            'clinics' => $includeModalData ? Clinic::query()->active()->orderBy('name')->get(['id', 'name']) : collect(),
            'modalMode' => $request->query('modal'),
            'stats' => $stats,
        ];

        return $this->moduleResponse(
            $request,
            'operations.stock-requests.index',
            $viewData,
            'operations.stock-requests.table.index',
            'operations.stock-requests.modal.form',
            [
                'statsHtml' => view('operations.stock-requests.components.stats', $viewData)->render(),
            ]
        );
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('stock-requests.index', ['modal' => 'create']);
    }

    public function show(Request $request, StockRequest $stockRequest): View|JsonResponse
    {
        $viewData = [
            'requestItem' => $stockRequest->load(['requesterClinic', 'requestedFromClinic', 'stock.product']),
            'modalMode' => 'detail',
        ];

        return $this->moduleResponse(
            $request,
            'operations.stock-requests.index',
            $viewData,
            'operations.stock-requests.table.index',
            'operations.stock-requests.modal.form',
        );
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'requester_clinic_id' => ['required', Rule::exists('clinics', 'id')],
            'requested_from_clinic_id' => ['required', Rule::exists('clinics', 'id'), 'different:requester_clinic_id'],
            'stock_id' => ['required', Rule::exists('stocks', 'id')],
            'requested_quantity' => 'required|integer|min:1',
            'request_reason' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        if (!$user->hasRole('Admin') && (int) $validated['requester_clinic_id'] !== (int) $user->clinic_id) {
            abort(403, 'Sadece kendi kliniğiniz adına talep oluşturabilirsiniz.');
        }

        $this->service->createRequest([
            ...$validated,
            'requested_by' => $user->name,
        ]);

        return $this->actionResponse($request, 'stock-requests.index', 'Stok talebi oluşturuldu.');
    }

    public function approve(Request $request, StockRequest $stockRequest): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'approved_quantity' => 'required|integer|min:1',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $this->service->approveRequest($stockRequest->id, $validated['approved_quantity'], auth()->user()->name, $validated['admin_notes'] ?? null);

        return $this->actionResponse($request, 'stock-requests.index', 'Talep onaylandı.');
    }

    public function reject(Request $request, StockRequest $stockRequest): RedirectResponse|JsonResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $this->service->rejectRequest($stockRequest->id, $request->rejection_reason, auth()->user()->name);

        return $this->actionResponse($request, 'stock-requests.index', 'Talep reddedildi.');
    }

    public function ship(Request $request, StockRequest $stockRequest): RedirectResponse|JsonResponse
    {
        $this->service->shipRequest($stockRequest->id, auth()->user()->name);
        return $this->actionResponse($request, 'stock-requests.index', 'Talep sevk sürecine alındı.');
    }

    public function complete(Request $request, StockRequest $stockRequest): RedirectResponse|JsonResponse
    {
        $this->service->completeRequest($stockRequest->id, auth()->user()->name);
        return $this->actionResponse($request, 'stock-requests.index', 'Talep tamamlandı.');
    }
}
