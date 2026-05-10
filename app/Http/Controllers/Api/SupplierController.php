<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function __construct(protected SupplierService $supplierService) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        return $this->success($this->supplierService->getAllWithFilters($filters));
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = $this->supplierService->createSupplier($request->validated());

        return $this->success($supplier, 'Tedarikci basariyla olusturuldu', 201);
    }

    public function show($id)
    {
        $supplier = $this->supplierService->getSupplierById($id);

        if (! $supplier) {
            return $this->error('Tedarikci bulunamadi', 404);
        }

        return $this->success($supplier);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers')->where(fn ($query) => $query->where('company_id', auth()->user()->company_id))->ignore($id),
            ],
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'additional_info' => 'nullable|array',
        ]);

        $supplier = $this->supplierService->updateSupplier($id, $validated);

        if (! $supplier) {
            return $this->error('Tedarikci bulunamadi', 404);
        }

        return $this->success($supplier, 'Tedarikci basariyla guncellendi');
    }

    public function destroy($id)
    {
        $supplier = $this->supplierService->getSupplierById($id);

        if (! $supplier) {
            return $this->error('Tedarikci bulunamadi', 404);
        }

        if (! auth()->user()->isSuperAdmin() && $supplier->company_id !== auth()->user()->company_id) {
            return $this->error('Bu islem icin yetkiniz yok.', 403);
        }

        $deleted = $this->supplierService->deleteSupplier($id);

        if (! $deleted) {
            return $this->error('Tedarikci silme islemi basarisiz', 400);
        }

        return $this->success(null, 'Tedarikci basariyla silindi');
    }

    public function getActive()
    {
        return $this->success($this->supplierService->getActiveSuppliers());
    }
}
