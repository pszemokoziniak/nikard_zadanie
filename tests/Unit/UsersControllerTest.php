<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\UsersController;
use App\Http\Requests\UsersRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App as AppFacade;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class UsersControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setCurrentRequest(Request $request): void
    {
        $this->app->instance('request', $request);
    }

    public function testIndexReturnsInertiaResponseWithTransformedUsers(): void
    {
        $filters = ['search' => 'john', 'role' => 'user', 'trashed' => 'with'];
        $this->setCurrentRequest(Request::create('/users', 'GET', $filters));

        $fakeUserRow = (object) [
            'id' => 1,
            'uuid' => 'u-1',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'owner' => false,
            'photo_path' => null,
            'deleted_at' => null,
        ];

        $fakeQuery = new class($fakeUserRow) {
            private $userRow;
            public function __construct($userRow) { $this->userRow = $userRow; }
            public function orderByName() { return $this; }
            public function filter($filters) { return $this; }
            public function paginate($perPage) { return $this; }
            public function withQueryString() { return $this; }
            public function through($callback) { return [$callback($this->userRow)]; }
        };

        $fakeAccount = new class($fakeQuery) {
            private $fakeQuery;
            public function __construct($fakeQuery) { $this->fakeQuery = $fakeQuery; }
            public function users() { return $this->fakeQuery; }
        };

        $fakeAuthUser = new class($fakeAccount) {
            public $account;
            public function __construct($account) { $this->account = $account; }
        };

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($fakeAuthUser);

        Inertia::shouldReceive('render')
            ->once()
            ->withArgs(function ($component, $props) use ($filters) {
                $this->assertSame('Users/Index', $component);
                $this->assertSame($filters, $props['filters']);
                $this->assertIsArray($props['users']);
                $this->assertCount(1, $props['users']);
                $row = $props['users'][0];
                $this->assertSame(1, $row['id']);
                $this->assertSame('u-1', $row['uuid']);
                $this->assertSame('John Doe', $row['name']);
                $this->assertSame('john@example.com', $row['email']);
                $this->assertFalse($row['owner']);
                $this->assertNull($row['photo']);
                $this->assertNull($row['deleted_at']);
                return true;
            })
            ->andReturn(Mockery::mock(InertiaResponse::class));

        $controller = new UsersController();
        $controller->index();
    }

    public function testCreateReturnsInertiaCreateComponent(): void
    {
        Inertia::shouldReceive('render')
            ->once()
            ->withArgs(function (...$args) {
                $component = $args[0] ?? null;
                $props = $args[1] ?? [];
                $this->assertSame('Users/Create', $component);
                $this->assertIsArray($props);
                return true;
            })
            ->andReturn(Mockery::mock(InertiaResponse::class));

        $controller = new UsersController();
        $controller->create();
    }

    public function testStoreCreatesUserAndRedirectsWithSuccess(): void
    {
        $request = Mockery::mock(UsersRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
            'password'   => 'secret',
            'owner'      => false,
        ]);
        $request->shouldReceive('file')->with('photo')->andReturn(null);

        $usersRelation = Mockery::mock();
        $usersRelation->shouldReceive('create')->once()->with([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
            'password'   => 'secret',
            'owner'      => false,
            'photo_path' => null,
        ]);

        $account = Mockery::mock();
        $account->shouldReceive('users')->andReturn($usersRelation);

        $authUser = new class($account) {
            public $account;
            public function __construct($account) { $this->account = $account; }
        };

        Auth::shouldReceive('user')->once()->andReturn($authUser);

        $controller = new UsersController();
        $resp = $controller->store($request);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('success'));
    }

    public function testEditReturnsInertiaEditComponentWithUserData(): void
    {
        $user = new User();
        $user->id = 10;
        $user->uuid = 'uuid-10';
        $user->first_name = 'Alice';
        $user->last_name = 'Doe';
        $user->email = 'alice@example.com';
        $user->owner = true;
        $user->photo_path = null;
        $user->deleted_at = null;

        Inertia::shouldReceive('render')
            ->once()
            ->withArgs(function ($component, $props) {
                $this->assertSame('Users/Edit', $component);
                $this->assertArrayHasKey('user', $props);
                $u = $props['user'];
                $this->assertSame(10, $u['id']);
                $this->assertSame('uuid-10', $u['uuid']);
                $this->assertSame('Alice', $u['first_name']);
                $this->assertSame('Doe', $u['last_name']);
                $this->assertTrue($u['owner']);
                $this->assertNull($u['photo']);
                return true;
            })
            ->andReturn(Mockery::mock(InertiaResponse::class));

        $controller = new UsersController();
        $controller->edit($user);
    }

    public function testUpdateUpdatesFieldsAndRedirectsWithSuccess(): void
    {
        AppFacade::shouldReceive('environment')->with('demo')->andReturn(false);

        $request = Mockery::mock(UsersRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'first_name' => 'Bob',
            'last_name'  => 'Smith',
            'email'      => 'bob@example.com',
            'owner'      => false,
            'password'   => '',
        ]);
        $request->shouldReceive('file')->with('photo')->andReturn(null);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('update')->once()->with([
            'first_name' => 'Bob',
            'last_name'  => 'Smith',
            'email'      => 'bob@example.com',
            'owner'      => false,
        ]);

        $controller = new UsersController();
        $resp = $controller->update($request, $user);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('success'));
    }

    public function testUpdateInDemoModeReturnsErrorForDemoUser(): void
    {
        AppFacade::shouldReceive('environment')->with('demo')->andReturn(true);

        $request = Mockery::mock(UsersRequest::class);
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isDemoUser')->once()->andReturn(true);

        $resp = (new UsersController())->update($request, $user);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('error'));
    }

    public function testDestroyDeletesAndRedirectsBackWithSuccess(): void
    {
        AppFacade::shouldReceive('environment')->with('demo')->andReturn(false);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isDemoUser')->never();
        $user->shouldReceive('delete')->once();

        $resp = (new UsersController())->destroy($user);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('success'));
    }

    public function testDestroyInDemoModeBlocksDemoUser(): void
    {
        AppFacade::shouldReceive('environment')->with('demo')->andReturn(true);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isDemoUser')->once()->andReturn(true);

        $resp = (new UsersController())->destroy($user);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('error'));
    }

    public function testRestoreRestoresAndRedirects(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('restore')->once();

        $resp = (new UsersController())->restore($user);

        $this->assertTrue($resp->isRedirect());
        $this->assertNotEmpty($resp->getSession()->get('success'));
    }
}
