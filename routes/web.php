<?php

use App\Http\Controllers\ApiDemoController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth

Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login')
    ->middleware('guest');

Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->name('login.store')
    ->middleware('guest');

Route::delete('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');
    // Użytkownicy
    Route::resource('users', UsersController::class)
        ->except('show')
        ->scoped(['task' => 'uuid']);
    Route::put('users/{user}/restore', [UsersController::class, 'restore'])->name('users.restore');
    // Zadania
    Route::resource('tasks', TaskController::class)
        ->except('show')
        ->scoped(['task' => 'uuid']);
    Route::put('tasks/{task:uuid}/restore', [TaskController::class, 'restore'])->name('tasks.restore');
    // Api
    Route::get('/demo/rest/posts', [ApiDemoController::class, 'restPosts'])
        ->name('demo.rest.posts');
    Route::get('/demo/soap/capital/{code}', [ApiDemoController::class, 'soapCapital'])
        ->name('demo.soap.capital');
});

// Images

Route::get('/img/{path}', [ImagesController::class, 'show'])
    ->where('path', '.*')
    ->name('image');
