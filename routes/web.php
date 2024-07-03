<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect(route('dashboard.home'));
});

Route::get('/dashboard', [DashboardController::class, 'home'])->middleware(['auth', 'verified'])->name('dashboard.home');
Route::get('/dashboard/account/{id}', [DashboardController::class, 'ordersOverview'])->middleware(['auth', 'verified'])->name('dashboard.account');
Route::post('/dashboard/process-orders', [DashboardController::class, 'processOrders'])->middleware(['auth', 'verified'])->name('dashboard.process-orders');
Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->middleware(['auth', 'verified'])->name('dashboard.settings');
Route::get('/dashboard/settings/delete/{id}', [DashboardController::class, 'deleteBolAccount'])->middleware(['auth', 'verified'])->name('dashboard.settings.delete');

Route::get('/dashboard/manual-labels', [DashboardController::class, 'manualLabels'])->middleware(['auth', 'verified'])->name('dashboard.manual-labels');
Route::post('/dashboard/manual-labels', [DashboardController::class, 'createManualLabels'])->middleware(['auth', 'verified'])->name('dashboard.manual-labels.post');

Route::get('/dashboard/settings/add', [DashboardController::class, 'addBolAccount'])->middleware(['auth', 'verified'])->name('dashboard.settings.add');
Route::post('/dashboard/settings/add', [DashboardController::class, 'storeBolAccount'])->middleware(['auth', 'verified'])->name('dashboard.settings.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
