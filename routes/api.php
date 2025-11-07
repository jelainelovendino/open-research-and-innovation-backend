<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;

//Public routes (no login required)
Route::post('/register',[AuthController::class, 'register']);
Route::post('/login',[AuthController::class, 'login']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/search', [ProjectController::class, 'search']);

//Protected routes (login required)
Route::middleware('auth:sanctum')->group(function () {  
    Route::post('/logout',[AuthController::class, 'logout']);
    Route::apiResource('projects', ProjectController::class)->except(['index']);
});
