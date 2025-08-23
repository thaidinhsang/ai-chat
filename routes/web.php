<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
})->name('home');
Route::get('login', [AdminController::class, 'loginForm'])->name('login');
Route::post('login', [AdminController::class, 'login']);


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard',[\App\Http\Controllers\AdminController::class, 'index'])->name('dashboard');
    Route::get('admin/pages', [\App\Http\Controllers\AdminController::class, 'index'])->name('admin.pages.index');
    Route::get('admin/pages/create', [\App\Http\Controllers\AdminController::class, 'create'])->name('admin.pages.create');
    Route::post('admin/pages/store', [\App\Http\Controllers\AdminController::class, 'store'])->name('admin.pages.store');
    Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('admin.pages.edit');
    Route::put('/{id}', [AdminController::class, 'update'])->name('admin.pages.update');
    Route::delete('/{id}', [AdminController::class, 'destroy'])->name('admin.pages.destroy');
    Route::get('/{id}', [AdminController::class, 'show'])->name('admin.pages.show');
    Route::post('logout', [AdminController::class, 'logout'])->name('logout');
});

require __DIR__.'/settings.php';
// require __DIR__.'/auth.php';
// require __DIR__.'/api.php';
