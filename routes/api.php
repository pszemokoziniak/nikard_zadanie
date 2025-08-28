<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/tasks', [TaskController::class, 'apiIndex']);
