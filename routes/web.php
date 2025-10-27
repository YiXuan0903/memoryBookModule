<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MemoryController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $memoryData = App\Http\Controllers\MemoryController::getMemoryDataForMainDashboard();

    return view('dashboard', $memoryData);
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // CRUD routes for memories
    Route::resource('memories', MemoryController::class);
});



require __DIR__.'/auth.php';
