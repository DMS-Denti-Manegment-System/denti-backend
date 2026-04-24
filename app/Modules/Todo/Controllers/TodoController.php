<?php

namespace App\Modules\Todo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Todo\Services\TodoService;
use App\Modules\Todo\Requests\StoreTodoRequest;
use App\Modules\Todo\Requests\UpdateTodoRequest;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    use JsonResponseTrait;

    protected $todoService;

    public function __construct(TodoService $todoService)
    {
        $this->todoService = $todoService;
    }

    public function index(): JsonResponse
    {
        $todos = $this->todoService->getAllTodos();
        return $this->success($todos, 'Todos retrieved successfully');
    }

    public function store(StoreTodoRequest $request): JsonResponse
    {
        try {
            $todo = $this->todoService->createTodo($request->validated());
            return $this->success($todo->load('category'), 'Todo created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show($id): JsonResponse
    {
        $todo = $this->todoService->getTodoById($id);

        if (!$todo) {
            return $this->error('Todo not found', 404);
        }

        return $this->success($todo->load('category'), 'Todo retrieved successfully');
    }

    public function update(UpdateTodoRequest $request, $id): JsonResponse
    {
        try {
            $todo = $this->todoService->updateTodo($id, $request->validated());

            if (!$todo) {
                return $this->error('Todo not found', 404);
            }

            return $this->success($todo->load('category'), 'Todo updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $deleted = $this->todoService->deleteTodo($id);

            if (!$deleted) {
                return $this->error('Todo not found', 404);
            }

            return $this->success(null, 'Todo deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function toggle($id): JsonResponse
    {
        try {
            $todo = $this->todoService->toggleTodoStatus($id);

            if (!$todo) {
                return $this->error('Todo not found', 404);
            }

            return $this->success($todo->load('category'), 'Todo status toggled successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function stats(): JsonResponse
    {
        $stats = $this->todoService->getTodoStats();
        return $this->success($stats, 'Todo statistics retrieved successfully');
    }

    public function byCategory($categoryId): JsonResponse
    {
        try {
            $todos = $this->todoService->getTodosByCategory($categoryId);
            return $this->success($todos, 'Todos by category retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}