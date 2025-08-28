<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\TaskController;
use App\Http\Requests\TasksRequest;
use App\Models\Task;
use Illuminate\Auth\Access\Response as AccessResponse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use DatabaseTransactions;
    use MockeryPHPUnitIntegration;

    protected function setCurrentRequest(Request $request): void
    {
        $this->app->instance('request', $request);
    }

    private function makeControllerWithQuery($fakeQuery): TaskController
    {
        return new class($fakeQuery) extends TaskController
        {
            private $fakeQuery;

            public function __construct($fakeQuery)
            {
                $this->fakeQuery = $fakeQuery;
            }

            protected function baseQuery()
            {
                return $this->fakeQuery;
            }
        };
    }

    public function test_index_returns_inertia_with_transformed_tasks_and_filters(): void
    {
        Gate::shouldReceive('authorize')->once()->with('viewAny', Task::class)->andReturn(AccessResponse::allow());

        $filters = ['search' => 'abc', 'status' => 'pending', 'trashed' => 'with'];
        $this->setCurrentRequest(Request::create('/tasks', 'GET', $filters));

        $fakeTaskRow = (object) [
            'id' => 1,
            'uuid' => 't-1',
            'title' => 'Title',
            'description' => 'Desc',
            'status' => 'pending',
            'due_date' => null,
            'deleted_at' => null,
        ];

        $fakeUser = new class
        {
            public $owner = true;

            public $id = 7;

            public function can($ability, $model): bool
            {
                return true;
            }
        };

        Auth::shouldReceive('user')->andReturn($fakeUser)->byDefault();
        Auth::shouldReceive('id')->andReturn($fakeUser->id)->byDefault();

        $fakeQuery = new class($fakeTaskRow)
        {
            private $row;

            public function __construct($row)
            {
                $this->row = $row;
            }

            public function when($condition, $callback)
            {
                if ($condition) {
                    return is_callable($callback) ? $callback($this, $condition) ?? $this : $this;
                }

                return $this;
            }

            public function withTrashed()
            {
                return $this;
            }

            public function onlyTrashed()
            {
                return $this;
            }

            public function where($col, $opOrValue = null, $value = null)
            {
                return $this;
            }

            public function orderByDesc($col)
            {
                return $this;
            }

            public function filter($filters)
            {
                return $this;
            }

            public function paginate($perPage)
            {
                return $this;
            }

            public function through($callback)
            {
                return [$callback($this->row)];
            }
        };

        Inertia::shouldReceive('render')
            ->once()
            ->withArgs(function ($component, $props) use ($filters) {
                $this->assertSame('Tasks/Index', $component);
                $this->assertSame($filters, $props['filters']);
                $this->assertIsArray($props['tasks']);
                $this->assertCount(1, $props['tasks']);
                $row = $props['tasks'][0];
                $this->assertSame(1, $row['id']);
                $this->assertSame('t-1', $row['uuid']);
                $this->assertSame('Title', $row['title']);
                $this->assertSame('Desc', $row['description']);
                $this->assertSame('pending', $row['status']);
                $this->assertNull($row['due_date']);
                $this->assertNull($row['deleted_at']);
                $this->assertTrue($row['can']['update']);
                $this->assertTrue($row['can']['delete']);

                return true;
            })
            ->andReturn(Mockery::mock(InertiaResponse::class));

        $controller = $this->makeControllerWithQuery($fakeQuery);
        $controller->index();
    }

    public function test_create_returns_inertia_create_with_status_options(): void
    {
        Gate::shouldReceive('authorize')->once()->with('create', Task::class)->andReturn(AccessResponse::allow());

        Inertia::shouldReceive('render')
            ->once()
            ->withArgs(function ($component, $props) {
                $this->assertSame('Tasks/Create', $component);
                $this->assertArrayHasKey('statusOptions', $props);
                $this->assertIsArray($props['statusOptions']);
                $this->assertNotEmpty($props['statusOptions']);

                return true;
            })
            ->andReturn(Mockery::mock(InertiaResponse::class));

        (new TaskController)->create();
    }

    public function test_store_creates_task_and_redirects_with_success(): void
    {
        Gate::shouldReceive('authorize')->once()->with('create', Task::class)->andReturn(AccessResponse::allow());

        $request = Mockery::mock(TasksRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'title' => 'X',
            'description' => 'Y',
            'status' => 'pending',
            'due_date' => null,
        ]);

        Auth::shouldReceive('id')->once()->andReturn(5);

        $resp = (new TaskController)->store($request);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('success'));
    }

    public function test_edit_returns_inertia_edit_with_task_data(): void
    {
        $task = new Task;
        $task->id = 10;
        $task->uuid = 'uuid-10';
        $task->title = 'T';
        $task->description = 'D';
        $task->status = 'pending';
        $task->due_date = null;
        $task->deleted_at = null;

        Gate::shouldReceive('authorize')->once()->with('update', $task)->andReturn(AccessResponse::allow());

        Inertia::shouldReceive('render')
            ->once()
            ->withArgs(function ($component, $props) {
                $this->assertSame('Tasks/Edit', $component);
                $this->assertArrayHasKey('task', $props);
                $t = $props['task'];
                $this->assertSame(10, $t['id']);
                $this->assertSame('uuid-10', $t['uuid']);
                $this->assertSame('T', $t['title']);
                $this->assertSame('D', $t['description']);
                $this->assertSame('pending', $t['status']);
                $this->assertNull($t['due_date']);
                $this->assertNull($t['deleted_at']);
                $this->assertArrayHasKey('statusOptions', $props);
                $this->assertIsArray($props['statusOptions']);

                return true;
            })
            ->andReturn(Mockery::mock(InertiaResponse::class));

        (new TaskController)->edit($task);
    }

    public function test_update_updates_task_and_redirects_with_success(): void
    {
        $task = Mockery::mock(Task::class);
        Gate::shouldReceive('authorize')->once()->with('update', $task)->andReturn(AccessResponse::allow());

        $request = Mockery::mock(TasksRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'title' => 'New',
            'description' => 'New D',
            'status' => 'done',
            'due_date' => null,
        ]);

        $task->shouldReceive('update')->once()->with([
            'title' => 'New',
            'description' => 'New D',
            'status' => 'done',
            'due_date' => null,
        ]);
        $task->shouldReceive('getRouteKey')->andReturn('uuid-1');

        $resp = (new TaskController)->update($request, $task);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('success'));
    }

    public function test_destroy_deletes_task_and_redirects_with_success(): void
    {
        $task = Mockery::mock(Task::class);
        Gate::shouldReceive('authorize')->once()->with('delete', $task)->andReturn(AccessResponse::allow());

        $task->shouldReceive('delete')->once();

        $resp = (new TaskController)->destroy($task);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('success'));
    }

    public function test_restore_restores_task_and_redirects_with_success(): void
    {
        $task = Mockery::mock(Task::class);
        Gate::shouldReceive('authorize')->once()->with('restore', $task)->andReturn(AccessResponse::allow());

        $task->shouldReceive('restore')->once();

        $resp = (new TaskController)->restore($task);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('success'));
    }
}
