<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Services\StockRequestService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockRequestController extends Controller
{
    public function __construct(protected StockRequestService $stockRequestService) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'requester_clinic_id', 'requested_from_clinic_id', 'type', 'clinic_id']);
        $perPage = min((int) $request->get('per_page', 15), 100);
        $requests = $this->stockRequestService->getAllWithFilters($filters, $perPage);

        return $this->success($requests, 'Success', 200, ['pagination' => true]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();

        if (is_null($user->clinic_id)) {
            return $this->error('Kullaniciya atanmis bir klinik bulunmuyor. Lutfen yoneticinize basvurun.', 403);
        }

        if (! isset($data['requester_clinic_id'])) {
            $data['requester_clinic_id'] = $user->clinic_id;
        }

        if (isset($data['requester_clinic_id']) && $data['requester_clinic_id'] != $user->clinic_id && ! $user->isSuperAdmin()) {
            return $this->error('Sadece kendi kliniginiz icin talep olusturabilirsiniz.', 403);
        }

        if (! isset($data['requested_by'])) {
            $data['requested_by'] = $user->name;
        }

        $clinicRule = Rule::exists('clinics', 'id');
        $stockRule = Rule::exists('stocks', 'id');
        if (! $user->isSuperAdmin()) {
            $clinicRule = $clinicRule->where('company_id', $user->company_id);
            $stockRule = $stockRule->where('company_id', $user->company_id);
        }
        $requestedFromClinicRule = Rule::exists('clinics', 'id');
        if (! $user->isSuperAdmin()) {
            $requestedFromClinicRule = $requestedFromClinicRule->where('company_id', $user->company_id);
        }

        $validated = validator($data, [
            'requester_clinic_id' => ['required', $clinicRule],
            'requested_from_clinic_id' => ['required', $requestedFromClinicRule, 'different:requester_clinic_id'],
            'stock_id' => ['required', $stockRule],
            'requested_quantity' => 'required|integer|min:1',
            'request_reason' => 'nullable|string|max:500',
            'requested_by' => 'required|string|max:255',
        ])->validate();

        $stock = Stock::whereKey($validated['stock_id'])->firstOrFail();
        if ((int) $stock->clinic_id !== (int) $validated['requested_from_clinic_id']) {
            return $this->error('Talep edilen stok, ürünü gönderecek klinikte bulunmuyor.', 422);
        }

        $stockRequest = $this->stockRequestService->createRequest($validated);

        return $this->success($stockRequest, 'Stok talebi basariyla olusturuldu', 201);
    }

    public function show($id)
    {
        $request = $this->stockRequestService->getRequestById((int) $id);

        if (! $request) {
            return $this->error('Talep bulunamadi', 404);
        }

        return $this->success($request);
    }

    public function approve(Request $request, $id)
    {
        $validated = $request->validate([
            'approved_quantity' => 'required|integer|min:1',
            'approved_by' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = $this->stockRequestService->approveRequest(
            (int) $id,
            $validated['approved_quantity'],
            $validated['approved_by'],
            $validated['notes'] ?? null
        );

        if (! $result) {
            return $this->error('Talep onaylanamadi. Yetersiz stok veya gecersiz talep.', 400);
        }

        return $this->success(null, 'Talep basariyla onaylandi');
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
            'rejected_by' => 'required|string|max:255',
        ]);

        $result = $this->stockRequestService->rejectRequest((int) $id, $validated['rejection_reason'], $validated['rejected_by']);

        if (! $result) {
            return $this->error('Talep reddedilemedi', 400);
        }

        return $this->success(null, 'Talep reddedildi');
    }

    public function ship(Request $request, $id)
    {
        $validated = $request->validate([
            'performed_by' => 'required|string|max:255',
        ]);

        $result = $this->stockRequestService->shipRequest((int) $id, $validated['performed_by']);

        if (! $result) {
            return $this->error('Transfer baslatilamadi', 400);
        }

        return $this->success(null, 'Transfer sureci baslatildi');
    }

    public function complete(Request $request, $id)
    {
        $validated = $request->validate([
            'performed_by' => 'required|string|max:255',
        ]);

        $result = $this->stockRequestService->completeRequest((int) $id, $validated['performed_by']);

        if (! $result) {
            return $this->error('Talep tamamlanamadi', 400);
        }

        return $this->success(null, 'Transfer basariyla tamamlandi');
    }

    public function getPendingRequests(Request $request)
    {
        $clinicId = $request->integer('clinic_id') ?: null;
        $requests = $this->stockRequestService->getPendingRequests($clinicId);

        return $this->success($requests);
    }

    public function getStats()
    {
        return $this->success($this->stockRequestService->getRequestStats());
    }
}
