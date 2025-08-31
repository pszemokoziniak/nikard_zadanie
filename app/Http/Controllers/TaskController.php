<?php

namespace App\Http\Controllers;

use App\Http\Requests\TasksRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    protected function baseQuery()
    {
        return Task::query();
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Task::class);

        return Inertia::render('Tasks/Index', [
            'filters' => Request::all('search', 'status', 'trashed'),
            'tasks' => $this->baseQuery()
                ->when(! (Auth::user()?->owner ?? false), fn ($q) => $q->where('user_id', Auth::id()))
                ->orderByDesc('created_at')
                ->filter(Request::only('search', 'status', 'trashed', 'role'))
                ->paginate(5)
                ->through(fn ($task) => (new TaskResource($task))->toArray(request())),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Task::class);

        return Inertia::render('Tasks/Create', [
            'statusOptions' => Task::statusSelectOptions(),
        ]);
    }

    public function store(TasksRequest $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $data = $request->validated();
        $data['user_id'] = Auth::id();
        Task::create($data);

        return redirect()->route('tasks.index')->with('success', Task::MSG_CREATED);
    }

    public function edit(Task $task): Response
    {
        $this->authorize('update', $task);

        return Inertia::render('Tasks/Edit', [
            'task' => (new TaskResource($task))->toArray(request()),
            'statusOptions' => Task::statusSelectOptions(),
        ]);
    }

    public function update(TasksRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return redirect()->route('tasks.index', $task)->with('success', Task::MSG_UPDATED);
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return to_route('tasks.index')->with('success', Task::MSG_DELETED);
    }

    public function restore(Task $task): RedirectResponse
    {
        $this->authorize('restore', $task);

        $task->restore();

        return to_route('tasks.index')->with('success', Task::MSG_RESTORED);
    }

    /**
     * GET /api/tasks – zwraca JSON (paginowany)
     *
     * @throws AuthorizationException
     */
    public function apiIndex(\Illuminate\Http\Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $query = $this->baseQuery()
            ->when(! (Auth::user()?->owner ?? false), fn ($q) => $q->where('user_id', Auth::id()))
            ->orderByDesc('created_at')
            ->filter($request->only('search', 'status', 'trashed', 'role'));

        $tasks = $query->paginate((int) $request->get('per_page', 10));

        return TaskResource::collection($tasks)->response();
    }
}
