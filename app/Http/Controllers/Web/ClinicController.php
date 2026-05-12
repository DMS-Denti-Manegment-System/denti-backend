<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Traits\HandlesOperationsResponses;
use App\Http\Requests\StoreClinicRequest;
use App\Http\Requests\UpdateClinicRequest;
use App\Models\Clinic;
use App\Repositories\ClinicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class ClinicController extends Controller
{
    use HandlesOperationsResponses;

    protected $repository;

    public function __construct(ClinicRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request): View|JsonResponse
    {
        $includeModalData = ! $request->ajax() || $request->query('modal') || $request->boolean('include_modal');
        $viewData = $this->getClinicsViewData($request, $includeModalData);

        return $this->moduleResponse(
            $request,
            'operations.clinics.index',
            $viewData,
            'operations.clinics.table.index',
            'operations.clinics.modal.form',
            [
                'statsHtml' => view('operations.clinics.components.stats', $viewData)->render(),
            ]
        );
    }

    protected function getClinicsViewData(Request $request, bool $includeModalData = false): array
    {
        $clinics = $this->repository->getAllWithFilters($request->all(), $this->perPage($request));

        $data = [
            'clinics' => $clinics,
            'clinicStats' => $this->repository->getClinicStats(),
        ];

        if ($includeModalData) {
            $data['modalMode'] = $request->query('modal');
            $data['editingClinic'] = $request->filled('edit') ? $this->repository->find($request->integer('edit')) : null;
        } else {
            $data['modalMode'] = null;
            $data['editingClinic'] = null;
        }

        return $data;
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('clinics.index', ['modal' => 'create']);
    }

    public function store(StoreClinicRequest $request): RedirectResponse|JsonResponse
    {
        $this->repository->create($request->validated());

        return $this->actionResponse($request, 'clinics.index', 'Klinik oluşturuldu.');
    }

    public function edit(Clinic $clinic): RedirectResponse
    {
        $this->authorize('update', $clinic);
        return redirect()->route('clinics.index', ['modal' => 'edit', 'edit' => $clinic->id]);
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $clinic);
        $this->repository->update($clinic->id, $request->validated());

        return $this->actionResponse($request, 'clinics.index', 'Klinik güncellendi.');
    }

    public function destroy(Request $request, Clinic $clinic): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $clinic);
        $success = $this->repository->delete($clinic->id);

        if (!$success) {
            return $this->actionErrorResponse($request, 'clinics.index', 'error', 'Klinik silinirken bir hata oluştu.');
        }

        return $this->actionResponse($request, 'clinics.index', 'Klinik başarıyla silindi.');
    }
}
