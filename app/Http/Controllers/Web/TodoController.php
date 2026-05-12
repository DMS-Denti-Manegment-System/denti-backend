<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Traits\HandlesOperationsResponses;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Models\Todo;
use App\Repositories\TodoRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    use HandlesOperationsResponses;

    protected $repository;

    public function __construct(TodoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request): View|JsonResponse
    {
        $includeModalData = ! $request->ajax() || $request->query('modal') || $request->boolean('include_modal');
        $viewData = $this->getTodosViewData($request, $includeModalData);

        return $this->moduleResponse($request, 'operations.todos.index', $viewData, 'operations.todos.table.index', 'operations.todos.modal.form');
    }

    protected function getTodosViewData(Request $request, bool $includeModalData = false): array
    {
        $todos = $this->repository->getAllWithFilters($request->all(), $this->perPage($request));

        $data = [
            'todos' => $todos,
        ];

        if ($includeModalData) {
            $data['modalMode'] = $request->query('modal');
            $data['editingTodo'] = $request->filled('edit') ? $this->repository->find($request->integer('edit')) : null;
            $data['categories'] = \App\Models\Category::all();
        } else {
            $data['modalMode'] = null;
            $data['editingTodo'] = null;
            $data['categories'] = collect();
        }

        return $data;
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('todos.index', ['modal' => 'create']);
    }

    public function store(StoreTodoRequest $request): RedirectResponse|JsonResponse
    {
        $this->repository->create($request->validated());

        return $this->actionResponse($request, 'todos.index', 'Görev oluşturuldu.');
    }

    public function edit(Todo $todo): RedirectResponse
    {
        return redirect()->route('todos.index', ['modal' => 'edit', 'edit' => $todo->id]);
    }

    public function update(UpdateTodoRequest $request, Todo $todo): RedirectResponse|JsonResponse
    {
        $this->repository->update($todo->id, $request->validated());

        return $this->actionResponse($request, 'todos.index', 'Görev güncellendi.');
    }

    public function toggle(Todo $todo): JsonResponse
    {
        $todo->update(['completed' => ! $todo->completed]);

        return response()->json(['success' => true, 'completed' => $todo->completed]);
    }

    public function destroy(Request $request, Todo $todo): RedirectResponse|JsonResponse
    {
        $todo->delete();

        return $this->actionResponse($request, 'todos.index', 'Görev silindi.');
    }
}
