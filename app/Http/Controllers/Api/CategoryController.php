<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoryService) {}

    public function index()
    {
        return $this->success($this->categoryService->getAllCategories());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->where(fn ($query) => $query->where('company_id', auth()->user()->company_id))],
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $category = $this->categoryService->createCategory($validated);

        return $this->success($category, 'Kategori basariyla olusturuldu', 201);
    }

    public function show($id)
    {
        $category = $this->categoryService->getCategoryById($id);

        if (! $category) {
            return $this->error('Kategori bulunamadi', 404);
        }

        return $this->success($category);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn ($query) => $query->where('company_id', auth()->user()->company_id))->ignore($id),
            ],
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $category = $this->categoryService->updateCategory($id, $validated);

        if (! $category) {
            return $this->error('Kategori bulunamadi', 404);
        }

        return $this->success($category, 'Kategori basariyla guncellendi');
    }

    public function destroy($id)
    {
        $deleted = $this->categoryService->deleteCategory($id);

        if (! $deleted) {
            return $this->error('Kategori bulunamadi', 404);
        }

        return $this->success(null, 'Kategori basariyla silindi');
    }

    public function stats($id)
    {
        $stats = $this->categoryService->getCategoryStats($id);

        return $this->success($stats);
    }
}
