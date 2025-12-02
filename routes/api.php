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
    Route::get('/my-projects', [ProjectController::class, 'myProjects']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['projects' => function ($q) {
            $q->latest();
        }, 'projects.category']);
    });
});

//Admin routes (admin only)
Route::middleware(['auth:sanctum', 'admin'])->group(function() {
    Route::get('/admin/projects', [ProjectController::class, 'index']); // browse all projects
    Route::get('/admin/projects/search', [ProjectController::class, 'search']); // search all projects
    Route::post('/admin/projects', [ProjectController::class, 'store']); // create
    Route::put('/admin/projects/{project}', [ProjectController::class, 'update']); // edit
    Route::delete('/admin/projects/{project}', [ProjectController::class, 'destroy']); // delete
});