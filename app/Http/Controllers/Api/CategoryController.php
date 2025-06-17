<?php
// app/Http/Controllers/Api/CategoryController.php - PROFESYONEL VERSİYON

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryCollection;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * GET /api/categories
     */
    public function index(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getCategoriesWithStats();

            return response()->json([
                'success' => true,
                'message' => 'Kategoriler başarıyla getirildi',
                ...(new CategoryCollection($categories))->response()->getData(true)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kategoriler getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/categories - REQUEST VALİDATİON İLE
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        try {
            // Request otomatik olarak validate edildi
            $category = $this->categoryService->createCategory($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Kategori başarıyla oluşturuldu',
                'data' => new CategoryResource($category)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori oluşturulamadı',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryById($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Kategori başarıyla getirildi',
                'data' => new CategoryResource($category)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/categories/{id} - REQUEST VALİDATİON İLE
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->updateCategory($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Kategori başarıyla güncellendi',
                'data' => new CategoryResource($category)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori güncellenemedi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * DELETE /api/categories/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->categoryService->deleteCategory($id);

            return response()->json([
                'success' => true,
                'message' => 'Kategori başarıyla silindi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori silinemedi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * PATCH /api/categories/{id}/toggle
     */
    public function toggle(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->toggleCategoryStatus($id);

            return response()->json([
                'success' => true,
                'message' => 'Kategori durumu değiştirildi',
                'data' => new CategoryResource($category)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori durumu değiştirilemedi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/categories/search?q=term
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Arama terimi gerekli (q parametresi)'
            ], 400);
        }

        try {
            $categories = $this->categoryService->searchCategories($query);

            return response()->json([
                'success' => true,
                'message' => "'{$query}' için arama sonuçları",
                'data' => CategoryResource::collection($categories),
                'meta' => [
                    'search_term' => $query,
                    'result_count' => $categories->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Arama yapılamadı',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}