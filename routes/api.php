<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::middleware('auth:sanctum')->get('/tasks', [TaskController::class, 'apiIndex']);
