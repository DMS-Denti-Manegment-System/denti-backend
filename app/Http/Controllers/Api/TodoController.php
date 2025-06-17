<?php
// app/Http/Controllers/Api/TodoController.php - PROFESYONEL VERSİYON

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Http\Resources\TodoResource;
use App\Http\Resources\TodoCollection;
use App\Services\TodoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TodoController extends Controller
{
    protected $todoService;

    public function __construct(TodoService $todoService)
    {
        $this->todoService = $todoService;
    }

    /**
     * GET /api/todos
     */
    public function index(): JsonResponse
    {
        try {
            $todos = $this->todoService->getAllTodos();

            return response()->json([
                'success' => true,
                'message' => 'Todo\'lar başarıyla getirildi',
                ...(new TodoCollection($todos))->response()->getData(true)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Todo\'lar getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/todos - REQUEST VALİDATİON İLE
     */
    public function store(CreateTodoRequest $request): JsonResponse
    {
        try {
            $todo = $this->todoService->createTodo($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Todo başarıyla oluşturuldu',
                'data' => new TodoResource($todo->load('category'))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Todo oluşturulamadı',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/todos/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $todo = $this->todoService->getTodoById($id);

            if (!$todo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Todo bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Todo başarıyla getirildi',
                'data' => new TodoResource($todo)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Todo getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/todos/{id} - REQUEST VALİDATİON İLE
     */
    public function update(UpdateTodoRequest $request, int $id): JsonResponse
    {
        try {
            $todo = $this->todoService->updateTodo($id, $request->validated());

            if (!$todo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Todo bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Todo başarıyla güncellendi',
                'data' => new TodoResource($todo->load('category'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Todo güncellenemedi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * DELETE /api/todos/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->todoService->deleteTodo($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Todo bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Todo başarıyla silindi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Todo silinemedi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * PATCH /api/todos/{id}/toggle
     */
    public function toggle(int $id): JsonResponse
    {
        try {
            $todo = $this->todoService->toggleTodoStatus($id);

            if (!$todo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Todo bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Todo durumu değiştirildi',
                'data' => new TodoResource($todo->load('category'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Todo durumu değiştirilemedi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/todos/category/{categoryId}
     */
    public function getByCategory(int $categoryId): JsonResponse
    {
        try {
            $todos = $this->todoService->getTodosByCategory($categoryId);

            return response()->json([
                'success' => true,
                'message' => 'Kategoriye ait todo\'lar getirildi',
                'data' => TodoResource::collection($todos),
                'meta' => [
                    'category_id' => $categoryId,
                    'count' => $todos->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Todo\'lar getirilemedi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/todos/uncategorized
     */
    public function getUncategorized(): JsonResponse
    {
        try {
            $todos = $this->todoService->getUncategorizedTodos();

            return response()->json([
                'success' => true,
                'message' => 'Kategorisiz todo\'lar getirildi',
                'data' => TodoResource::collection($todos),
                'meta' => [
                    'count' => $todos->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Todo\'lar getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/todos/{id}/move
     */
    public function moveToCategory(Request $request, int $id): JsonResponse
    {
        // Basit validation
        $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id'
        ]);

        try {
            $todo = $this->todoService->moveTodoToCategory(
                $id,
                $request->input('category_id')
            );

            if (!$todo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Todo bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Todo başarıyla taşındı',
                'data' => new TodoResource($todo->load('category'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Todo taşınamadı',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * GET /api/todos/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->todoService->getTodoStats();

            return response()->json([
                'success' => true,
                'message' => 'İstatistikler getirildi',
                'data' => $stats,
                'meta' => [
                    'generated_at' => now()->toISOString(),
                    'resource_type' => 'stats'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İstatistikler getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}