<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [
        TransactionController::class,
        'categories',
    ]);

    Route::get('/transactions', [
        TransactionController::class,
        'index',
    ]);

    Route::post('/transactions', [
        TransactionController::class,
        'store',
    ]);

    Route::get('/transactions/{id}', [
        TransactionController::class,
        'show',
    ])->whereNumber('id');

    Route::put('/transactions/{id}', [
        TransactionController::class,
        'update',
    ])->whereNumber('id');

    Route::delete('/transactions/{id}', [
        TransactionController::class,
        'destroy',
    ])->whereNumber('id');

    Route::get('/admin/users', [
        AuthController::class,
        'users',
    ]);
});
