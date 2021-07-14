<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;

Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('refresh', [AuthController::class, 'refresh'])
    ->name('refresh');

Route::middleware('auth')->group(function () {
    Route::get('me', [AuthController::class, 'me'])
        ->name('me');

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('change-password', [AuthController::class, 'changePassword'])->name('change_password');
});
