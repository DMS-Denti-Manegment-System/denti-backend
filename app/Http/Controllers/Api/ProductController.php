<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockTransactionResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'category', 'status', 'clinic_id', 'level']);
            $perPage = min((int) $request->query('per_page', 15), 100);

            $products = $this->productService->getAllProducts($filters, $perPage);

            return $this->success(ProductListResource::collection($products));
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById((int) $id);

            if (! $product) {
                return $this->error('Ürün bulunamadı', 404);
            }

            return $this->success(new ProductResource($product));
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Set defaults
            $data['company_id'] = auth()->user()->company_id;
            $data['is_active'] = $data['is_active'] ?? true;

            $product = $this->productService->createProduct($data);

            return $this->success(new ProductResource($product), 'Ürün başarıyla oluşturuldu', 201);
        } catch (\Exception $e) {
            Log::error('Product Store Error: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'sku' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'unit' => 'sometimes|required|string|max:20',
                'category' => 'nullable|string|max:50',
                'brand' => 'nullable|string|max:50',
                'min_stock_level' => 'nullable|integer',
                'critical_stock_level' => 'nullable|integer',
                'show_zero_stock_in_critical' => 'nullable|boolean',
            ]);

            $product = $this->productService->updateProduct((int) $id, $data);

            if (! $product) {
                return $this->error('Ürün bulunamadı', 404);
            }

            return $this->success(new ProductResource($product), 'Ürün başarıyla güncellendi');
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $deleted = $this->productService->deleteProduct((int) $id);

            if (! $deleted) {
                return $this->error('Ürün bulunamadı', 404);
            }

            return $this->success(null, 'Ürün başarıyla silindi');
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function transactions($id): JsonResponse
    {
        try {
            $transactions = $this->productService->getProductTransactions((int) $id);

            return $this->success(StockTransactionResource::collection($transactions));
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error(__('messages.server_error'), 500);
        }
    }
}
