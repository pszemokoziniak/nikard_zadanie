<?php

namespace App\Http\Controllers;

use App\Http\Requests\TasksRequest;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
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
                ->when(Request::get('status'), fn ($q, $status) => $q->where('status', $status))
                ->when(Request::get('trashed') === 'with', fn ($q) => $q->withTrashed())
                ->when(Request::get('trashed') === 'only', fn ($q) => $q->onlyTrashed())
                ->orderByDesc('created_at')
                ->filter(Request::only('search', 'role', 'trashed'))
                ->paginate(5)
                ->through(fn ($task) => [
                    'id' => $task->id,
                    'uuid' => $task->uuid,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'due_date' => optional($task->due_date)->toDateString(),
                    'deleted_at' => $task->deleted_at,
                    'can' => [
                        'update' => Auth::user()?->can('update', $task) ?? false,
                        'delete' => Auth::user()?->can('delete', $task) ?? false,
                    ],
                ]),
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
            'task' => [
                'id' => $task->id,
                'uuid' => $task->uuid,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'due_date' => optional($task->due_date)->toDateString(),
                'deleted_at' => $task->deleted_at,
            ],
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
}
