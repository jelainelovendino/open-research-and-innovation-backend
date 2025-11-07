<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
