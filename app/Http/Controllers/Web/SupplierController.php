<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Traits\HandlesOperationsResponses;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use HandlesOperationsResponses;

    protected $repository;

    public function __construct(SupplierRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request): View|JsonResponse
    {
        $includeModalData = ! $request->ajax() || $request->query('modal') || $request->boolean('include_modal');
        $viewData = $this->getSuppliersViewData($request, $includeModalData);

        return $this->moduleResponse(
            $request,
            'operations.suppliers.index',
            $viewData,
            'operations.suppliers.table.index',
            'operations.suppliers.modal.form',
            [
                'statsHtml' => view('operations.suppliers.components.stats', $viewData)->render(),
            ]
        );
    }

    protected function getSuppliersViewData(Request $request, bool $includeModalData = false): array
    {
        $suppliers = $this->repository->getAllWithFilters($request->all(), $this->perPage($request));

        $data = [
            'suppliers' => $suppliers,
            'supplierStats' => $this->repository->getSupplierStats(),
        ];

        if ($includeModalData) {
            $data['modalMode'] = $request->query('modal');
            $data['editingSupplier'] = $request->filled('edit') ? $this->repository->find($request->integer('edit')) : null;
        } else {
            $data['modalMode'] = null;
            $data['editingSupplier'] = null;
        }

        return $data;
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('suppliers.index', ['modal' => 'create']);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse|JsonResponse
    {
        $this->repository->create($request->validated());

        return $this->actionResponse($request, 'suppliers.index', 'Tedarikçi oluşturuldu.');
    }

    public function edit(Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        return redirect()->route('suppliers.index', ['modal' => 'edit', 'edit' => $supplier->id]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $supplier);
        $this->repository->update($supplier->id, $request->validated());

        return $this->actionResponse($request, 'suppliers.index', 'Tedarikçi güncellendi.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $supplier);
        $success = $this->repository->delete($supplier->id);

        if (! $success) {
            return $this->actionErrorResponse($request, 'suppliers.index', 'error', 'Tedarikçi silinirken bir hata oluştu.');
        }

        return $this->actionResponse($request, 'suppliers.index', 'Tedarikçi başarıyla silindi.');
    }
}
