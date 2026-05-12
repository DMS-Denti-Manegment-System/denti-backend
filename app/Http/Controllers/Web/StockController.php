<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Traits\HandlesOperationsResponses;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UseStockRequest;
use App\Models\Product;
use App\Models\Stock;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
    use HandlesOperationsResponses;

    protected $productRepository;

    protected $productService;

    protected $stockService;

    public function __construct(
        ProductRepository $productRepository,
        ProductService $productService,
        StockService $stockService
    ) {
        $this->productRepository = $productRepository;
        $this->productService = $productService;
        $this->stockService = $stockService;
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'clinic_id', 'status']);

            $query = Stock::with(['product', 'clinic'])
                ->where('is_active', true)
                ->where('available_stock', '>', 0);

            if ($request->filled('clinic_id')) {
                $query->where('clinic_id', $request->clinic_id);
            }

            if ($request->filled('search')) {
                $search = '%'.$request->search.'%';
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', $search)
                        ->orWhere('sku', 'like', $search);
                });
            }

            $stocks = $query->latest('id')->limit(50)->get();

            $results = $stocks->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'text' => ($stock->product->name ?? 'Bilinmeyen')." [{$stock->clinic->name}]".($stock->batch_code ? " - {$stock->batch_code}" : ''),
                    'product_name' => $stock->product->name ?? 'Bilinmeyen',
                    'clinic_name' => $stock->clinic->name ?? 'Bilinmeyen',
                    'available_stock' => $stock->available_stock,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): View|JsonResponse
    {
        try {
            $viewData = $this->getStocksViewData($request);

            return $this->moduleResponse(
                $request,
                'operations.stocks.index',
                $viewData,
                'operations.stocks.table.index',
                'operations.stocks.modal.index',
                ['statsHtml' => view('operations.stocks.components.stats', $viewData)->render()]
            );
        } catch (\Throwable $e) {
            Log::error('web.stocks.index_failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->actionErrorResponse($request, 'dashboard', 'stock', 'Stok listesi yüklenirken bir hata oluştu.');
        }
    }

    public function show(int $id): View
    {
        $product = Product::with(['batches.supplier', 'batches.clinic', 'clinic'])
            ->withCount('batches')
            ->findOrFail($id);

        $transactions = $product->stockTransactions()
            ->with(['user', 'stock.product'])
            ->latest('transaction_date')
            ->paginate(15);

        // Stats
        $totalUsage = $product->stockTransactions()
            ->where('type', 'usage')
            ->sum('quantity');

        $totalValue = (float) $product->batches()
            ->where('is_active', 1)
            ->sum(\Illuminate\Support\Facades\DB::raw('purchase_price * current_stock'));

        $stockStats = [
            'total_usage' => abs($totalUsage),
            'batch_count' => $product->batches_count,
            'total_value' => $totalValue,
        ];

        // Chart Data (Last 15 days)
        $chartData = collect();
        $endDate = now();
        $startDate = now()->subDays(15);

        // Simple implementation: show current stock as last point,
        // and fill others with 0 or last known if we wanted to be precise.
        // For now, let's provide at least an empty array or basic data to avoid errors.
        for ($i = 15; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData->push([
                'date' => $date,
                'value' => (int) $product->total_stock, // Simplified
            ]);
        }

        $hasExpiryTracking = (bool) $product->has_expiration_date;
        $defaultUsageBatch = $product->batches()->where('is_active', 1)->where('current_stock', '>', 0)->first();

        $viewData = [
            'product' => $product,
            'transactions' => $transactions,
            'stockStats' => $stockStats,
            'chartData' => $chartData,
            'hasExpiryTracking' => $hasExpiryTracking,
            'defaultUsageBatch' => $defaultUsageBatch,
            'clinics' => \App\Models\Clinic::query()->active()->orderBy('name')->get(['id', 'name']),
            'suppliers' => \App\Models\Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'units' => ['Adet', 'Kutu', 'Paket', 'Sise', 'Ml', 'Lt', 'Kg', 'Gr', 'Set'],
            'currencies' => ['TRY' => '₺ (TL)', 'USD' => '$ (USD)', 'EUR' => '€ (EUR)'],
        ];

        return view('operations.stocks.show', $viewData);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('stocks.index', ['modal' => 'create']);
    }

    public function store(StoreProductRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $this->productService->createProduct($request->validated());

            return $this->actionResponse($request, 'stocks.index', 'Ürün başarıyla oluşturuldu.');
        } catch (\Throwable $e) {
            Log::error('web.stocks.store_failed', ['error' => $e->getMessage()]);

            return $this->actionErrorResponse($request, 'stocks.index', 'stock', 'Ürün oluşturulamadı.');
        }
    }

    public function edit(Product $product): RedirectResponse
    {
        return redirect()->route('stocks.index', ['modal' => 'edit', 'edit' => $product->id]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        try {
            $this->productService->updateProduct($product->id, $request->validated());

            return $this->actionResponse($request, 'stocks.index', 'Ürün güncellendi.');
        } catch (\Throwable $e) {
            Log::error('web.stocks.update_failed', ['error' => $e->getMessage()]);

            return $this->actionErrorResponse($request, 'stocks.index', 'stock', 'Güncelleme sırasında hata oluştu.');
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        $success = $this->productService->deleteProduct($product->id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Ürün başarıyla silindi.' : 'Silme sırasında hata oluştu.',
        ]);
    }

    public function adjust(AdjustStockRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        $batch = $product->batches()->latest('id')->first();
        if (! $batch) {
            return $this->actionErrorResponse($request, 'stocks.index', 'stock', 'Ayarlanabilir stok partisi bulunamadı.');
        }

        /** @var \App\Models\Stock $batch */
        $this->stockService->adjustStock(
            $batch->id,
            (int) $request->quantity,
            $request->reason.($request->notes ? ' - '.$request->notes : ''),
            auth()->user()->name,
            false,
            $request->operation_type,
            (int) $request->quantity
        );

        return $this->actionResponse($request, 'stocks.index', 'Stok hareketi kaydedildi.');
    }

    public function use(UseStockRequest $request, Stock $stock): JsonResponse|RedirectResponse
    {
        try {
            $this->stockService->useStock(
                stockId: $stock->id,
                quantity: (int) $request->quantity,
                performedBy: auth()->user()->name,
                userId: auth()->id(),
                notes: $request->notes ?: 'Manuel Kullanim',
                isFromReserved: false,
                isSubUnit: (bool) $request->is_sub_unit,
                showZeroStockInCritical: $request->has('show_zero_stock_in_critical') ? (bool) $request->show_zero_stock_in_critical : null
            );

            return $this->actionResponse($request, 'products.show', 'Stok kullanildi.', ['id' => $stock->product_id]);
        } catch (\Exception $e) {
            Log::error('web.stocks.use_failed', ['error' => $e->getMessage(), 'stock_id' => $stock->id]);

            return $this->actionErrorResponse($request, 'products.show', 'stock', $e->getMessage(), 422, ['id' => $stock->product_id]);
        }
    }

    public function storeBatch(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'clinic_id' => 'required|exists:clinics,id',
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'storage_location' => 'nullable|string|max:255',
            'has_sub_unit' => 'boolean',
            'sub_unit_name' => 'nullable|required_if:has_sub_unit,1|string|max:50',
            'sub_unit_multiplier' => 'nullable|required_if:has_sub_unit,1|integer|min:1',
        ]);

        try {
            $data = $validated;
            $data['product_id'] = $product->id;
            $data['current_stock'] = $validated['quantity'];
            $data['track_expiry'] = (bool) ($validated['expiry_date'] ?? false);

            $this->stockService->createStock($data);

            return $this->actionResponse($request, 'products.show', 'Stok girişi başarıyla yapıldı.', ['id' => $product->id]);
        } catch (\Throwable $e) {
            Log::error('web.stocks.store_batch_failed', ['error' => $e->getMessage(), 'product_id' => $product->id]);

            return $this->actionErrorResponse($request, 'products.show', 'stock', 'Stok girişi yapılamadı.', 422, ['id' => $product->id]);
        }
    }

    private function getStocksViewData(Request $request): array
    {
        $includeModalData = ! $request->ajax()
            || $request->boolean('include_modal')
            || in_array((string) $request->query('modal'), ['create', 'edit', 'detail'], true);

        $filters = $request->only(['search', 'clinic_id', 'category', 'status', 'level', 'per_page']);
        $products = $this->productRepository->getAllWithFilters($filters, $this->perPage($request));
        $selectedClinicId = $request->filled('clinic_id') ? $request->integer('clinic_id') : null;
        $stockStats = $this->stockService->getStockStats($selectedClinicId);

        $editingProduct = null;
        if ($includeModalData && $request->filled('edit')) {
            $editingProduct = Product::with(['batches' => fn ($query) => $query->with(['supplier', 'clinic'])->latest('id')])
                ->findOrFail($request->integer('edit'));
        }

        $selectedProduct = null;
        $selectedBatch = null;
        $selectedTransactions = null;
        $detailMeta = null;

        if ($request->filled('product')) {
            $selectedProduct = Product::with(['clinic'])->findOrFail($request->integer('product'));

            // 🚀 PERFORMANCE FIX: Use relationship query instead of collection sorting to avoid OOM
            $selectedBatch = $selectedProduct->batches()->with(['supplier', 'clinic'])->latest('id')->first();

            $selectedTransactions = $selectedProduct->stockTransactions()
                ->with(['clinic', 'stock'])
                ->latest('transaction_date')
                ->paginate(10, ['*'], 'transactions_page')
                ->withQueryString();

            // 🚀 PERFORMANCE FIX: Use database aggregation instead of collection sum
            $totalStockValue = (float) $selectedProduct->batches()->where('is_active', 1)->sum(\Illuminate\Support\Facades\DB::raw('purchase_price * current_stock'));
            $totalStockCount = (int) $selectedProduct->total_stock;
            $weightedAveragePrice = $totalStockCount > 0 ? $totalStockValue / $totalStockCount : 0;

            $detailMeta = [
                'total_stock_value' => $totalStockValue,
                'weighted_average_price' => $weightedAveragePrice,
                'last_purchase_price' => (float) ($selectedBatch->purchase_price ?? 0),
                'batch_count' => $selectedProduct->batches()->count(),
                'tracking_type' => $selectedProduct->has_expiration_date ? 'SKT Takipli' : 'Genel Stok Takibi',
            ];
        }

        return [
            'products' => $products,
            'stockStats' => $stockStats,
            'clinics' => \App\Models\Clinic::query()->active()->orderBy('name')->get(['id', 'name']),
            'suppliers' => \App\Models\Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'categories' => \App\Models\Category::query()->orderBy('name')->get(['id', 'name']),
            'units' => ['Adet', 'Kutu', 'Paket', 'Sise', 'Ml', 'Lt', 'Kg', 'Gr', 'Set'],
            'currencies' => ['TRY' => '₺ (TL)', 'USD' => '$ (USD)', 'EUR' => '€ (EUR)'],
            'modalMode' => $request->query('modal'),
            'editingProduct' => $editingProduct,
            'editingBatch' => $editingProduct?->batches?->first(),
            'selectedProduct' => $selectedProduct,
            'selectedBatch' => $selectedBatch,
            'selectedTransactions' => $selectedTransactions,
            'activeDetailTab' => $request->query('tab', 'history'),
            'detailMeta' => $detailMeta,
            'chartSeries' => collect(), // Can be implemented if needed
        ];
    }
}
