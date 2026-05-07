<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Clinic;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAlert;
use App\Models\StockRequest;
use App\Models\Supplier;
use App\Models\Todo;
use App\Models\User;
use App\Services\ProductService;
use App\Services\StockAlertService;
use App\Services\StockRequestService;
use App\Services\StockService;
use App\Services\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class OperationsPageController extends Controller
{
    public function stocks(Request $request): View|JsonResponse
    {
        try {
            $viewData = $this->getStocksViewData($request);

            if ($request->ajax()) {
                return response()->json([
                    'statsHtml' => view('operations.stocks.components.stats', $viewData)->render(),
                    'tableHtml' => view('operations.stocks.table.index', $viewData)->render(),
                    'modalHtml' => view('operations.stocks.modal.index', $viewData)->render(),
                ]);
            }

            return view('operations.stocks.index', $viewData);
        } catch (\Throwable $e) {
            Log::error('web.stocks.index_failed', [
                'user_id' => auth()->id(),
                'company_id' => auth()->user()?->company_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok listesi yüklenirken bir hata oluştu.',
                    'data' => null,
                    'errors' => null,
                    'meta' => null,
                ], 500);
            }

            return redirect()->route('dashboard')->withErrors([
                'stocks' => 'Stok yönetimi ekranı yüklenemedi. Lütfen tekrar deneyin.',
            ]);
        }
    }

    public function stockShow(Request $request, int $id): View
    {
        $product = Product::with(['batches.supplier', 'batches.clinic', 'clinic', 'company'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $batchIds = $product->batches->pluck('id');

        $transactions = \App\Models\StockTransaction::whereIn('stock_id', $batchIds)
            ->with(['user', 'clinic', 'stock'])
            ->orderByDesc('transaction_date')
            ->paginate(10, ['*'], 'page', $request->integer('page', 1))
            ->withQueryString();

        $hasExpiryTracking = $product->has_expiration_date || $product->batches->contains(fn ($batch) => $batch->track_expiry);
        $defaultUsageBatch = $product->batches
            ->first(fn ($batch) => $batch->is_active && $batch->current_stock > 0);

        // Generate chart data (last 15 days) using aggregated query output.
        $chartData = [];
        $currentTotal = $product->total_stock;
        $dailyTransactionTotals = \App\Models\StockTransaction::whereIn('stock_id', $batchIds)
            ->selectRaw('DATE(transaction_date) as tx_date')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN type IN ('purchase', 'adjustment_increase', 'transfer_in', 'return_in')
                        THEN quantity
                        ELSE -quantity
                    END
                ) as net_change
            ")
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->get();
        $dailyNetByDate = $dailyTransactionTotals->pluck('net_change', 'tx_date');

        for ($i = 0; $i < 15; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = [
                'date' => $date,
                'value' => $currentTotal,
            ];

            // Move one day backwards by subtracting that day's net change.
            $currentTotal -= (int) ($dailyNetByDate[$date] ?? 0);
        }
        $chartData = array_reverse($chartData);

        return view('operations.stocks.show', [
            'product' => $product,
            'transactions' => $transactions,
            'hasExpiryTracking' => $hasExpiryTracking,
            'defaultUsageBatch' => $defaultUsageBatch,
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'clinics' => Clinic::query()->active()->where('company_id', auth()->user()->company_id)->orderBy('name')->get(['id', 'name']),
            'units' => ['Adet', 'Kutu', 'Paket', 'Sise', 'Ml', 'Lt', 'Kg', 'Gr', 'Set'],
            'currencies' => ['TRY' => '₺ (TL)', 'USD' => '$ (USD)', 'EUR' => '€ (EUR)'],
            'chartData' => $chartData,
            'stockStats' => [
                'total_usage' => $product->batches->sum('internal_usage_count'),
                'total_value' => $product->batches->sum(fn ($b) => $b->current_stock * $b->purchase_price),
                'batch_count' => $product->batches->count(),
            ],
        ]);
    }

    public function stockBatchStore(Request $request, Product $product): RedirectResponse
    {
        abort_if($product->company_id !== auth()->user()->company_id, 403);

        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'clinic_id' => ['required', Rule::exists('clinics', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'required|date',
            'storage_location' => 'nullable|string|max:100',
            'expiry_yellow_days' => 'nullable|integer|min:0',
            'expiry_red_days' => 'nullable|integer|min:0',
            'unit' => 'required|string|max:20',
            'has_sub_unit' => 'nullable|boolean',
            'sub_unit_name' => 'nullable|required_if:has_sub_unit,1|string|max:50',
            'sub_unit_multiplier' => 'nullable|required_if:has_sub_unit,1|integer|min:1',
        ]);

        // Update product settings
        $product->update([
            'unit' => $validated['unit'],
            'clinic_id' => $validated['clinic_id'],
            'has_sub_unit' => $request->boolean('has_sub_unit'),
            'sub_unit_name' => $request->boolean('has_sub_unit') ? $validated['sub_unit_name'] : null,
            'sub_unit_multiplier' => $request->boolean('has_sub_unit') ? $validated['sub_unit_multiplier'] : null,
        ]);

        app(StockService::class)->createStock([
            'product_id' => $product->id,
            'clinic_id' => $validated['clinic_id'],
            'supplier_id' => $validated['supplier_id'],
            'current_stock' => $validated['quantity'],
            'available_stock' => $validated['quantity'],
            'purchase_price' => $validated['purchase_price'] ?? null,
            'currency' => $validated['currency'] ?? 'TRY',
            'purchase_date' => $validated['purchase_date'] ?? now()->toDateString(),
            'expiry_date' => $validated['expiry_date'],
            'storage_location' => $validated['storage_location'] ?? null,
            'expiry_yellow_days' => $validated['expiry_yellow_days'] ?? 30,
            'expiry_red_days' => $validated['expiry_red_days'] ?? 15,
            'company_id' => $companyId,
            'track_expiry' => true,
            'is_active' => true,
            'batch_code' => null,
        ]);

        return redirect()->route('products.show', $product->id)->with('status', 'Yeni parti eklendi.');
    }

    public function stockUse(Request $request, Stock $stock): RedirectResponse
    {
        abort_if($stock->company_id !== auth()->user()->company_id, 403);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
        ]);

        $productId = $stock->product_id;

        try {
            app(StockService::class)->useStock(
                $stock->id,
                (int) $validated['quantity'],
                auth()->user()->name,
                auth()->id(),
                trim(($validated['reason'] ?? 'Web panel kullanımı').(! empty($validated['notes']) ? ' - '.$validated['notes'] : ''))
            );

            return redirect()->route('products.show', $productId)->with('status', 'Stok kullanımı kaydedildi.');
        } catch (\Throwable $exception) {
            return redirect()->route('products.show', $productId)->withErrors([
                'stock_use' => $exception->getMessage(),
            ]);
        }
    }

    public function categories(Request $request): View|JsonResponse
    {
        $categories = Category::query()
            ->withCount('todos')
            ->when($request->filled('search'), fn (Builder $query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        $editingCategory = null;
        if ($request->filled('edit')) {
            $editingCategory = Category::findOrFail($request->integer('edit'));
        }

        $viewData = [
            'categories' => $categories,
            'modalMode' => $request->query('modal'),
            'editingCategory' => $editingCategory,
        ];

        return $this->moduleResponse(
            $request,
            'operations.categories.index',
            $viewData,
            'operations.categories.table.index',
            'operations.categories.modal.form',
        );
    }

    public function categoryCreate(): RedirectResponse
    {
        return redirect()->route('categories.index', ['modal' => 'create']);
    }

    public function categoryStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        Category::create([
            ...$validated,
            'color' => $validated['color'] ?: '#6c757d',
            'is_active' => $request->boolean('is_active', true),
            'company_id' => auth()->user()->company_id,
        ]);

        return $this->actionResponse($request, 'categories.index', 'Kategori olusturuldu.');
    }

    public function categoryEdit(Category $category): RedirectResponse
    {
        return redirect()->route('categories.index', ['modal' => 'edit', 'edit' => $category->id]);
    }

    public function categoryUpdate(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            ...$validated,
            'color' => $validated['color'] ?: '#6c757d',
            'is_active' => $request->boolean('is_active', false),
        ]);

        return $this->actionResponse($request, 'categories.index', 'Kategori guncellendi.');
    }

    public function suppliers(Request $request): View|JsonResponse
    {
        $suppliers = Supplier::query()
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search');
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->string('status') === 'active'))
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        $editingSupplier = null;
        if ($request->filled('edit')) {
            $editingSupplier = Supplier::findOrFail($request->integer('edit'));
        }

        $viewData = [
            'suppliers' => $suppliers,
            'modalMode' => $request->query('modal'),
            'editingSupplier' => $editingSupplier,
        ];

        return $this->moduleResponse(
            $request,
            'operations.suppliers.index',
            $viewData,
            'operations.suppliers.table.index',
            'operations.suppliers.modal.form',
        );
    }

    public function supplierCreate(): RedirectResponse
    {
        return redirect()->route('suppliers.index', ['modal' => 'create']);
    }

    public function supplierStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        Supplier::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
            'company_id' => auth()->user()->company_id,
        ]);

        return $this->actionResponse($request, 'suppliers.index', 'Tedarikci olusturuldu.');
    }

    public function supplierEdit(Supplier $supplier): RedirectResponse
    {
        return redirect()->route('suppliers.index', ['modal' => 'edit', 'edit' => $supplier->id]);
    }

    public function supplierUpdate(Request $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $supplier->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return $this->actionResponse($request, 'suppliers.index', 'Tedarikci guncellendi.');
    }

    public function clinics(Request $request): View|JsonResponse
    {
        $clinics = Clinic::query()
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search');
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('responsible_person', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->string('status') === 'active'))
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        $editingClinic = null;
        if ($request->filled('edit')) {
            $editingClinic = Clinic::findOrFail($request->integer('edit'));
        }

        $viewData = [
            'clinics' => $clinics,
            'modalMode' => $request->query('modal'),
            'editingClinic' => $editingClinic,
        ];

        return $this->moduleResponse(
            $request,
            'operations.clinics.index',
            $viewData,
            'operations.clinics.table.index',
            'operations.clinics.modal.form',
        );
    }

    public function clinicCreate(): RedirectResponse
    {
        return redirect()->route('clinics.index', ['modal' => 'create']);
    }

    public function clinicStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'responsible_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        Clinic::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
            'company_id' => auth()->user()->company_id,
        ]);

        return $this->actionResponse($request, 'clinics.index', 'Klinik olusturuldu.');
    }

    public function clinicEdit(Clinic $clinic): RedirectResponse
    {
        return redirect()->route('clinics.index', ['modal' => 'edit', 'edit' => $clinic->id]);
    }

    public function clinicUpdate(Request $request, Clinic $clinic): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'responsible_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $clinic->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return $this->actionResponse($request, 'clinics.index', 'Klinik guncellendi.');
    }

    public function stockRequests(Request $request): View|JsonResponse
    {
        $requests = StockRequest::query()
            ->with(['requesterClinic', 'requestedFromClinic', 'stock.product'])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search');
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('request_reason', 'like', "%{$search}%")
                        ->orWhere('requested_by', 'like', "%{$search}%")
                        ->orWhere('request_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->latest('requested_at')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        $stats = [
            'pending' => StockRequest::where('status', 'pending')->count(),
            'approved' => StockRequest::where('status', 'approved')->count(),
            'in_transit' => StockRequest::where('status', 'in_transit')->count(),
            'rejected' => StockRequest::where('status', 'rejected')->count(),
            'completed' => StockRequest::where('status', 'completed')->count(),
        ];

        $viewData = [
            'requests' => $requests,
            'stocks' => Stock::query()->with(['product', 'clinic'])->active()->orderBy('id', 'desc')->get(),
            'clinics' => Clinic::query()->active()->orderBy('name')->get(['id', 'name']),
            'modalMode' => $request->query('modal'),
            'stats' => $stats,
        ];

        return $this->moduleResponse(
            $request,
            'operations.stock-requests.index',
            $viewData,
            'operations.stock-requests.table.index',
            'operations.stock-requests.modal.form',
        );
    }

    public function stockRequestCreate(): RedirectResponse
    {
        return redirect()->route('stock-requests.index', ['modal' => 'create']);
    }

    public function stockRequestStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'requester_clinic_id' => ['required', Rule::exists('clinics', 'id')],
            'requested_from_clinic_id' => ['required', Rule::exists('clinics', 'id'), 'different:requester_clinic_id'],
            'stock_id' => ['required', Rule::exists('stocks', 'id')],
            'requested_quantity' => 'required|integer|min:1',
            'request_reason' => 'nullable|string|max:500',
        ]);

        app(StockRequestService::class)->createRequest([
            ...$validated,
            'requested_by' => auth()->user()->name,
            'company_id' => auth()->user()->company_id,
        ]);

        return $this->actionResponse($request, 'stock-requests.index', 'Stok talebi olusturuldu.');
    }

    public function stockRequestApprove(Request $request, StockRequest $stockRequest): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'approved_quantity' => 'required|integer|min:1',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        app(StockRequestService::class)->approveRequest(
            $stockRequest->id,
            $validated['approved_quantity'],
            auth()->user()->name,
            $validated['admin_notes'] ?? null
        );

        return $this->actionResponse($request, 'stock-requests.index', 'Talep onaylandi.');
    }

    public function stockRequestReject(Request $request, StockRequest $stockRequest): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        app(StockRequestService::class)->rejectRequest(
            $stockRequest->id,
            $validated['rejection_reason'],
            auth()->user()->name
        );

        return $this->actionResponse($request, 'stock-requests.index', 'Talep reddedildi.');
    }

    public function stockRequestShip(Request $request, StockRequest $stockRequest): RedirectResponse|JsonResponse
    {
        app(StockRequestService::class)->shipRequest($stockRequest->id, auth()->user()->name);

        return $this->actionResponse($request, 'stock-requests.index', 'Talep sevk surecine alindi.');
    }

    public function stockRequestComplete(Request $request, StockRequest $stockRequest): RedirectResponse|JsonResponse
    {
        app(StockRequestService::class)->completeRequest($stockRequest->id, auth()->user()->name);

        return $this->actionResponse($request, 'stock-requests.index', 'Talep tamamlandi.');
    }

    public function alerts(Request $request): View|JsonResponse
    {
        $alerts = StockAlert::query()
            ->with(['clinic', 'product'])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search');
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn (Builder $query) => $query->where('type', $request->string('type')))
            ->when($request->filled('resolved'), fn (Builder $query) => $query->where('is_resolved', $request->string('resolved') === '1'))
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        $viewData = compact('alerts');

        return $this->moduleResponse(
            $request,
            'operations.alerts.index',
            $viewData,
            'operations.alerts.table.index',
        );
    }

    public function alertResolve(Request $request, StockAlert $stockAlert): RedirectResponse|JsonResponse
    {
        app(StockAlertService::class)->resolveAlert($stockAlert->id, auth()->user()->name);

        return $this->actionResponse($request, 'alerts.index', 'Uyari cozuldu.');
    }

    public function alertDismiss(Request $request, StockAlert $stockAlert): RedirectResponse|JsonResponse
    {
        app(StockAlertService::class)->dismissAlert($stockAlert->id);

        return $this->actionResponse($request, 'alerts.index', 'Uyari kapatildi.');
    }

    public function todos(Request $request): View|JsonResponse
    {
        $todos = Todo::query()
            ->with('category')
            ->when($request->filled('search'), fn (Builder $query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('completed', $request->string('status') === 'completed'))
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        $editingTodo = null;
        if ($request->filled('edit')) {
            $editingTodo = Todo::findOrFail($request->integer('edit'));
        }

        $viewData = [
            'todos' => $todos,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'modalMode' => $request->query('modal'),
            'editingTodo' => $editingTodo,
        ];

        return $this->moduleResponse(
            $request,
            'operations.todos.index',
            $viewData,
            'operations.todos.table.index',
            'operations.todos.modal.form',
        );
    }

    public function todoCreate(): RedirectResponse
    {
        return redirect()->route('todos.index', ['modal' => 'create']);
    }

    public function todoStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        Todo::create([
            ...$validated,
            'completed' => false,
            'company_id' => auth()->user()->company_id,
        ]);

        return $this->actionResponse($request, 'todos.index', 'Todo olusturuldu.');
    }

    public function todoEdit(Todo $todo): RedirectResponse
    {
        return redirect()->route('todos.index', ['modal' => 'edit', 'edit' => $todo->id]);
    }

    public function todoUpdate(Request $request, Todo $todo): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:categories,id',
            'completed' => 'nullable|boolean',
        ]);

        $completed = $request->boolean('completed');

        $todo->update([
            ...$validated,
            'completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ]);

        return $this->actionResponse($request, 'todos.index', 'Todo guncellendi.');
    }

    public function todoToggle(Request $request, Todo $todo): RedirectResponse|JsonResponse
    {
        $completed = ! $todo->completed;
        $todo->update([
            'completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ]);

        return $this->actionResponse($request, 'todos.index', $completed ? 'Todo tamamlandi.' : 'Todo tekrar acildi.');
    }

    public function todoDestroy(Request $request, Todo $todo): RedirectResponse|JsonResponse
    {
        if ($todo->completed) {
            return $this->actionErrorResponse($request, 'todos.index', 'todo', 'Tamamlanmis todo silinemez.');
        }

        $todo->delete();

        return $this->actionResponse($request, 'todos.index', 'Todo silindi.');
    }

    public function employees(Request $request): View|JsonResponse
    {
        $users = User::query()
            ->with(['clinic', 'roles'])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search');
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        $editingEmployee = null;
        if ($request->filled('edit')) {
            $editingEmployee = User::with(['roles', 'permissions'])->findOrFail($request->integer('edit'));
        }

        $viewData = [
            'users' => $users,
            'permissions' => Permission::query()->orderBy('name')->get(),
            'clinics' => Clinic::query()->active()->orderBy('name')->get(['id', 'name']),
            'modalMode' => $request->query('modal'),
            'editingEmployee' => $editingEmployee,
        ];

        return $this->moduleResponse(
            $request,
            'operations.employees.index',
            $viewData,
            'operations.employees.table.index',
            'operations.employees.modal.form',
        );
    }

    public function employeeCreate(): RedirectResponse
    {
        return redirect()->route('employees.index', ['modal' => 'create']);
    }

    public function employeeStore(Request $request): RedirectResponse|JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->where(fn ($query) => $query->where('company_id', $companyId))],
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'clinic_id' => ['nullable', Rule::exists('clinics', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'permission_names' => 'nullable|array',
            'permission_names.*' => 'string|exists:permissions,name',
        ]);

        $employee = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'clinic_id' => $validated['clinic_id'] ?? null,
            'company_id' => $companyId,
            'is_active' => true,
        ]);

        if ($request->has('permission_names')) {
            $employee->syncPermissions($validated['permission_names'] ?? []);
        }

        return $this->actionResponse($request, 'employees.index', 'Personel olusturuldu.');
    }

    public function employeeEdit(User $user): RedirectResponse
    {
        return redirect()->route('employees.index', ['modal' => 'edit', 'edit' => $user->id]);
    }

    public function employeeUpdate(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'clinic_id' => ['nullable', Rule::exists('clinics', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'is_active' => 'nullable|boolean',
            'permission_names' => 'nullable|array',
            'permission_names.*' => 'string|exists:permissions,name',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'clinic_id' => $validated['clinic_id'] ?? null,
            'is_active' => $request->boolean('is_active', false),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->syncPermissions($validated['permission_names'] ?? []);

        return $this->actionResponse($request, 'employees.index', 'Personel guncellendi.');
    }

    public function employeeDestroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ($user->id === auth()->id()) {
            return $this->actionErrorResponse($request, 'employees.index', 'employee', 'Kendi hesabinizi silemezsiniz.');
        }

        if ($user->hasRole(User::ROLE_OWNER)) {
            return $this->actionErrorResponse($request, 'employees.index', 'employee', 'Sirket sahibi silinemez.');
        }

        $user->delete();

        return $this->actionResponse($request, 'employees.index', 'Personel silindi.');
    }

    public function stockCreate(): RedirectResponse
    {
        return redirect()->route('stocks.index', ['modal' => 'create']);
    }

    public function stockStore(Request $request): RedirectResponse|JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products')->where(fn ($query) => $query->where('company_id', $companyId))],
            'description' => 'nullable|string',
            'unit' => 'required|string|max:20',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'yellow_alert_level' => 'nullable|integer|min:0',
            'red_alert_level' => 'nullable|integer|min:0',
            'clinic_id' => ['required', Rule::exists('clinics', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'quantity' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|required_if:has_expiration_date,1',
            'expiry_yellow_days' => 'nullable|integer|min:0',
            'expiry_red_days' => 'nullable|integer|min:0',
            'storage_location' => 'nullable|string|max:100',
            'has_sub_unit' => 'nullable|boolean',
            'sub_unit_name' => 'nullable|required_if:has_sub_unit,1|string|max:50',
            'sub_unit_multiplier' => 'nullable|required_if:has_sub_unit,1|integer|min:1',
            'has_expiration_date' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            app(\App\Services\ProductService::class)->createProduct([
                ...$validated,
                'initial_stock' => (int) ($validated['quantity'] ?? 0),
                'company_id' => $companyId,
                'is_active' => $request->boolean('is_active', true),
                'has_expiration_date' => $request->boolean('has_expiration_date', false),
                'expiry_yellow_days' => $validated['expiry_yellow_days'] ?? 30,
                'expiry_red_days' => $validated['expiry_red_days'] ?? 15,
                'has_sub_unit' => $request->boolean('has_sub_unit', false),
                'sub_unit_name' => $request->boolean('has_sub_unit', false) ? ($validated['sub_unit_name'] ?? null) : null,
                'sub_unit_multiplier' => $request->boolean('has_sub_unit', false) ? ($validated['sub_unit_multiplier'] ?? null) : null,
                'yellow_alert_level' => $validated['yellow_alert_level'] ?? 10,
                'red_alert_level' => $validated['red_alert_level'] ?? 5,
                'min_stock_level' => $validated['yellow_alert_level'] ?? 10,
                'critical_stock_level' => $validated['red_alert_level'] ?? 5,
                'currency' => $validated['currency'] ?? 'TRY',
                'expiry_date' => $request->boolean('has_expiration_date', false) ? ($validated['expiry_date'] ?? null) : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('web.stocks.store_failed', [
                'user_id' => auth()->id(),
                'company_id' => $companyId,
                'payload' => $request->except(['_token']),
                'error' => $e->getMessage(),
            ]);

            return $this->actionErrorResponse(
                $request,
                'stocks.index',
                'stock',
                'Yeni stok eklenemedi. Lütfen bilgileri kontrol edip tekrar deneyin.',
                500
            );
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Urun olusturuldu.',
            ]);
        }

        return redirect()->route('stocks.index')->with('status', 'Urun olusturuldu.');
    }

    public function stockEdit(Product $product): RedirectResponse
    {
        return redirect()->route('stocks.index', ['modal' => 'edit', 'edit' => $product->id]);
    }

    public function stockAdjust(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'operation_type' => ['required', Rule::in(['increase', 'decrease', 'sync'])],
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $batch = $product->batches()->latest('id')->first();
        if (! $batch) {
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Bu urun icin ayarlanabilir stok partisi bulunamadi.',
                ], 422);
            }

            return redirect()->route('stocks.index', ['modal' => 'detail', 'product' => $product->id, 'tab' => 'history'])
                ->withErrors(['stock' => 'Bu urun icin ayarlanabilir stok partisi bulunamadi.']);
        }

        $reason = $validated['reason'];
        if (! empty($validated['notes'])) {
            $reason .= ' - '.$validated['notes'];
        }

        app(StockService::class)->adjustStock(
            $batch->id,
            (int) $validated['quantity'],
            $reason,
            auth()->user()->name,
            false,
            $validated['operation_type'],
            (int) $validated['quantity']
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Stok hareketi kaydedildi.',
            ]);
        }

        return redirect()->route('stocks.index', ['modal' => 'detail', 'product' => $product->id, 'tab' => 'history'])
            ->with('status', 'Stok hareketi kaydedildi.');
    }

    public function stockUpdate(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products')->where(fn ($query) => $query->where('company_id', $companyId))->ignore($product->id)],
            'description' => 'nullable|string',
            'unit' => 'required|string|max:20',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'yellow_alert_level' => 'nullable|integer|min:0',
            'red_alert_level' => 'nullable|integer|min:0',
            'clinic_id' => ['required', Rule::exists('clinics', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'quantity' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|required_if:has_expiration_date,1',
            'expiry_yellow_days' => 'nullable|integer|min:0',
            'expiry_red_days' => 'nullable|integer|min:0',
            'storage_location' => 'nullable|string|max:100',
            'has_sub_unit' => 'nullable|boolean',
            'sub_unit_name' => 'nullable|required_if:has_sub_unit,1|string|max:50',
            'sub_unit_multiplier' => 'nullable|required_if:has_sub_unit,1|integer|min:1',
            'has_expiration_date' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $product->update([
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'description' => $validated['description'] ?? null,
            'unit' => $validated['unit'],
            'category' => $validated['category'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'clinic_id' => $validated['clinic_id'],
            'yellow_alert_level' => $validated['yellow_alert_level'] ?? 10,
            'red_alert_level' => $validated['red_alert_level'] ?? 5,
            'min_stock_level' => $validated['yellow_alert_level'] ?? 10,
            'critical_stock_level' => $validated['red_alert_level'] ?? 5,
            'is_active' => $request->boolean('is_active', false),
            'has_expiration_date' => $request->boolean('has_expiration_date', false),
        ]);

        $trackExpiry = $request->boolean('has_expiration_date', false);
        $hasSubUnit = $request->boolean('has_sub_unit', false);
        $batchPayload = [
            'clinic_id' => $validated['clinic_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'current_stock' => $validated['quantity'] ?? 0,
            'purchase_price' => $validated['purchase_price'] ?? null,
            'currency' => $validated['currency'] ?? 'TRY',
            'purchase_date' => $validated['purchase_date'] ?? null,
            'expiry_date' => $trackExpiry ? ($validated['expiry_date'] ?? null) : null,
            'expiry_yellow_days' => $validated['expiry_yellow_days'] ?? 30,
            'expiry_red_days' => $validated['expiry_red_days'] ?? 15,
            'storage_location' => $validated['storage_location'] ?? null,
            'track_expiry' => $trackExpiry,
            'has_sub_unit' => $hasSubUnit,
            'sub_unit_name' => $hasSubUnit ? ($validated['sub_unit_name'] ?? null) : null,
            'sub_unit_multiplier' => $hasSubUnit ? ($validated['sub_unit_multiplier'] ?? null) : null,
            'current_sub_stock' => 0,
            'is_active' => $request->boolean('is_active', false),
        ];

        $editingBatch = $product->batches()->latest('id')->first();

        if ($editingBatch) {
            app(\App\Services\StockService::class)->updateStock($editingBatch->id, $batchPayload);
        } elseif (! empty($validated['clinic_id'])) {
            app(\App\Services\StockService::class)->createStock([
                'product_id' => $product->id,
                'company_id' => $companyId,
                ...$batchPayload,
                'available_stock' => $validated['quantity'] ?? 0,
            ]);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Urun guncellendi.',
            ]);
        }

        return redirect()->route('stocks.index')->with('status', 'Ürün güncellendi.');
    }

    public function stockDestroy(Product $product): JsonResponse
    {
        $success = app(ProductService::class)->deleteProduct($product->id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Ürün başarıyla silindi.' : 'Ürün silinirken bir hata oluştu.',
        ]);
    }

    public function reports(Request $request): View
    {
        $companyId = Auth::user()->company_id;
        $clinicId = $request->integer('clinic_id');
        $dateFrom = $request->string('date_from')->value();
        $dateTo = $request->string('date_to')->value();

        $summary = [
            'products' => Product::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'clinics' => Clinic::query()->count(),
            'alerts' => StockAlert::query()->active()->count(),
            'pending_requests' => StockRequest::query()->where('status', 'pending')->count(),
            'open_todos' => Todo::query()->where('completed', false)->count(),
            'company_id' => $companyId,
        ];

        $movements = \App\Models\StockTransaction::query()
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

    public function profile(): View
    {
        $user = Auth::user()->load(['company', 'clinic', 'roles']);

        return view('operations.profile.index', compact('user'));
    }

    public function profileUpdateInfo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.auth()->id(),
        ]);

        auth()->user()->update($validated);

        return redirect()->route('profile.index')->with('status', 'Profil bilgileri guncellendi.');
    }

    public function profileUpdatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($validated['current_password'], auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Mevcut sifre dogru degil.']);
        }

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile.index')->with('status', 'Sifre guncellendi.');
    }

    private function getStocksViewData(Request $request): array
    {
        $filters = $request->only(['search', 'clinic_id', 'category', 'status', 'level', 'per_page']);
        $products = app(ProductService::class)->getAllProducts($filters, $request->integer('per_page', 20));

        $companyId = auth()->user()->company_id;
        $selectedClinicId = $request->filled('clinic_id') ? $request->integer('clinic_id') : null;
        $stockStats = app(StockService::class)->getStockStats($companyId, $selectedClinicId);

        $editingProduct = null;
        $editingBatch = null;
        if ($request->filled('edit')) {
            $editingProduct = Product::with(['batches' => fn ($query) => $query->with(['supplier', 'clinic'])->latest('id')])
                ->findOrFail($request->integer('edit'));
            $editingBatch = $editingProduct->batches->first();
        }

        $selectedProduct = null;
        $selectedBatch = null;
        $selectedTransactions = null;
        $chartSeries = collect();
        $detailMeta = null;

        if ($request->filled('product')) {
            $selectedProduct = Product::with(['clinic', 'batches.supplier', 'batches.clinic'])
                ->findOrFail($request->integer('product'));
            $selectedBatch = $selectedProduct->batches->sortByDesc('id')->first();
            $selectedTransactions = $selectedProduct->stockTransactions()
                ->with(['clinic', 'stock'])
                ->latest('transaction_date')
                ->paginate(10, ['*'], 'transactions_page')
                ->withQueryString();

            $chartSeries = $selectedTransactions->getCollection()
                ->reverse()
                ->values()
                ->map(fn ($transaction) => [
                    'label' => optional($transaction->transaction_date)->format('d/m/Y') ?: '-',
                    'value' => (int) $transaction->new_stock,
                ]);

            $totalStockValue = $selectedProduct->batches->sum(fn ($batch) => (float) ($batch->purchase_price ?? 0) * (int) $batch->current_stock);
            $weightedAveragePrice = $selectedProduct->total_stock > 0 ? $totalStockValue / max(1, $selectedProduct->total_stock) : 0;

            $detailMeta = [
                'total_stock_value' => $totalStockValue,
                'weighted_average_price' => $weightedAveragePrice,
                'last_purchase_price' => (float) ($selectedBatch?->purchase_price ?? 0),
                'batch_count' => $selectedProduct->batches->count(),
                'tracking_type' => $selectedProduct->has_expiration_date ? 'SKT Takipli' : 'Genel Stok Takibi',
            ];
        }

        return [
            'products' => $products,
            'clinics' => Clinic::query()->active()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'units' => ['Adet', 'Kutu', 'Paket', 'Sise', 'Ml', 'Lt', 'Kg', 'Gr', 'Set'],
            'currencies' => ['TRY' => '₺ (TL)', 'USD' => '$ (USD)', 'EUR' => '€ (EUR)'],
            'stockStats' => $stockStats,
            'modalMode' => $request->query('modal'),
            'editingProduct' => $editingProduct,
            'editingBatch' => $editingBatch,
            'selectedProduct' => $selectedProduct,
            'selectedBatch' => $selectedBatch,
            'selectedTransactions' => $selectedTransactions,
            'activeDetailTab' => $request->query('tab', 'history'),
            'chartSeries' => $chartSeries,
            'detailMeta' => $detailMeta,
        ];
    }

    private function moduleResponse(
        Request $request,
        string $pageView,
        array $viewData,
        string $tableView,
        ?string $modalView = null,
        array $extra = [],
    ): View|JsonResponse {
        if ($request->ajax()) {
            return response()->json([
                'tableHtml' => view($tableView, $viewData)->render(),
                'modalHtml' => $modalView ? view($modalView, $viewData)->render() : '',
                ...$extra,
            ]);
        }

        return view($pageView, $viewData);
    }

    private function actionResponse(Request $request, string $route, string $message): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route($route)->with('status', $message);
    }

    private function actionErrorResponse(
        Request $request,
        string $route,
        string $key,
        string $message,
        int $status = 422,
    ): RedirectResponse|JsonResponse {
        if ($request->ajax()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    $key => [$message],
                ],
            ], $status);
        }

        return redirect()->route($route)->withErrors([$key => $message]);
    }

    public function profile2faGenerate(Request $request): JsonResponse
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret(auth()->user());
        $qrUrl = $service->getQrCodeUrl(auth()->user());

        return response()->json([
            'secret' => $secret,
            'qrUrl' => $qrUrl,
        ]);
    }

    public function profile2faConfirm(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);

        $success = app(TwoFactorService::class)->confirm2FA(auth()->user(), $request->code);

        return response()->json([
            'success' => $success,
            'message' => $success ? '2FA dogrulandi ve aktif edildi.' : 'Gecersiz kod.',
            'recoveryCodes' => $success ? auth()->user()->two_factor_recovery_codes : [],
        ]);
    }

    public function profile2faDisable(Request $request): RedirectResponse|JsonResponse
    {
        auth()->user()->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return $this->actionResponse($request, 'profile.index', '2FA devre disi birakildi.');
    }

    public function profile2faRecoveryCodes(Request $request): JsonResponse
    {
        $codes = app(TwoFactorService::class)->generateRecoveryCodes(auth()->user());

        return response()->json(['recoveryCodes' => $codes]);
    }

    public function alertBulkResolve(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:stock_alerts,id']);
        app(StockAlertService::class)->bulkResolve($request->ids, auth()->user()->name);

        return $this->actionResponse($request, 'alerts.index', 'Secili uyarilar cozuldu.');
    }

    public function alertBulkDismiss(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:stock_alerts,id']);
        app(StockAlertService::class)->bulkDismiss($request->ids);

        return $this->actionResponse($request, 'alerts.index', 'Secili uyarilar yoksayildi.');
    }

    public function alertBulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:stock_alerts,id']);
        app(StockAlertService::class)->bulkDelete($request->ids);

        return $this->actionResponse($request, 'alerts.index', 'Secili uyarilar silindi.');
    }

    public function alertSync(Request $request): RedirectResponse|JsonResponse
    {
        $count = app(StockAlertService::class)->syncAlerts();

        return $this->actionResponse($request, 'alerts.index', "{$count} stok kaydi tarandi ve uyarilar guncellendi.");
    }

    public function alertSettings(Request $request): View
    {
        return view('operations.alerts.settings');
    }

    public function alertUpdateSettings(Request $request): RedirectResponse
    {
        // For now, settings are mocked as per report's mention of hardcoded settings
        return redirect()->route('alerts.index')->with('status', 'Ayarlar guncellendi (simule edildi).');
    }
}
