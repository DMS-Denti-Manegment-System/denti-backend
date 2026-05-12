<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Traits\HandlesOperationsResponses;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use HandlesOperationsResponses;

    protected $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request): View|JsonResponse
    {
        $includeModalData = ! $request->ajax() || $request->query('modal') || $request->boolean('include_modal');
        $viewData = $this->getCategoriesViewData($request, $includeModalData);

        return $this->moduleResponse($request, 'operations.categories.index', $viewData, 'operations.categories.table.index', 'operations.categories.modal.form');
    }

    protected function getCategoriesViewData(Request $request, bool $includeModalData = false): array
    {
        $categories = $this->repository->getAllWithFilters($request->all(), $this->perPage($request));

        $data = [
            'categories' => $categories,
        ];

        if ($includeModalData) {
            $data['modalMode'] = $request->query('modal');
            $data['editingCategory'] = $request->filled('edit') ? $this->repository->find($request->integer('edit')) : null;
        } else {
            $data['modalMode'] = null;
            $data['editingCategory'] = null;
        }

        return $data;
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('categories.index', ['modal' => 'create']);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse|JsonResponse
    {
        $this->repository->create([
            ...$request->validated(),
            'color' => $request->color ?: '#6c757d',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->actionResponse($request, 'categories.index', 'Kategori oluşturuldu.');
    }

    public function edit(Category $category): RedirectResponse
    {
        return redirect()->route('categories.index', ['modal' => 'edit', 'edit' => $category->id]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse|JsonResponse
    {
        $this->repository->update($category->id, $request->validated());

        return $this->actionResponse($request, 'categories.index', 'Kategori güncellendi.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        // Kontrol: Kategoriye ait görev veya ürün var mı?
        if ($category->todos()->exists() || $category->products()->exists()) {
            return $this->actionErrorResponse($request, 'categories.index', 'error', 'Bu kategoriye ait görevler veya ürünler olduğu için silinemez.');
        }

        $category->delete();

        return $this->actionResponse($request, 'categories.index', 'Kategori başarıyla silindi.');
    }
}
