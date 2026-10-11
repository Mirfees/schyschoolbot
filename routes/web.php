<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AccessCodeController;
use App\Http\Controllers\Admin\ReportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Защищаем маршруты группой middleware: пользователь должен быть залогинен (auth) и иметь роль admin
Route::middleware(['auth', 'is_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/codes', [AccessCodeController::class, 'index'])->name('codes.index');
    Route::post('/codes', [AccessCodeController::class, 'store'])->name('codes.store');
    Route::patch('/codes/{accessCode}/toggle', [AccessCodeController::class, 'toggle'])->name('codes.toggle');
    Route::delete('/codes/{accessCode}', [AccessCodeController::class, 'destroy'])->name('codes.destroy');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__.'/auth.php';
