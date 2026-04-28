<?php
// app/Modules/Stock/Controllers/SupplierController.php - DÜZELTİLMİŞ

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);
        return response()->json([
            'success' => true,
            'data' => $this->supplierService->getAllWithFilters($filters)
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'additional_info' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $supplier = $this->supplierService->createSupplier($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tedarikçi başarıyla oluşturuldu',
                'data' => $supplier
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        $supplier = $this->supplierService->getSupplierById($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Tedarikçi bulunamadı'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $supplier
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'additional_info' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $supplier = $this->supplierService->updateSupplier($id, $validator->validated());

            if (!$supplier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tedarikçi bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tedarikçi başarıyla güncellendi',
                'data' => $supplier
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $supplier = $this->supplierService->getSupplierById($id);
            if (!$supplier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tedarikçi bulunamadı'
                ], 404);
            }

            // Ownership check
            if (!auth()->user()->hasRole('Super Admin') && $supplier->company_id !== auth()->user()->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu işlem için yetkiniz yok.'
                ], 403);
            }

            $deleted = $this->supplierService->deleteSupplier($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tedarikçi silme işlemi başarısız'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tedarikçi başarıyla silindi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Silme hatası: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getActive()
    {
        $suppliers = $this->supplierService->getActiveSuppliers();

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }
}