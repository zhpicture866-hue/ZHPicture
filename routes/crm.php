<?php

use App\Http\Controllers\Crm\FollowupController;
use App\Http\Controllers\Crm\InteraksiController;
use App\Http\Controllers\Crm\ProfilController;
use App\Http\Controllers\Crm\ReaktivasiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('crm')->name('crm.')->group(function () {
    Route::middleware('can:lihat data crm')->group(function () {
        Route::get('customers/{customer}', [ProfilController::class, 'show'])->name('customers.show');
        Route::get('interaksi', [InteraksiController::class, 'index'])->name('interaksi.index');
        Route::get('reaktivasi', [ReaktivasiController::class, 'index'])->name('reaktivasi.index');
    });

    Route::middleware('can:kelola data crm')->group(function () {
        Route::put('customers/{customer}/profil', [ProfilController::class, 'update'])->name('customers.profil');
        Route::post('customers/{customer}/followups', [FollowupController::class, 'store'])->name('followups.store');
        Route::patch('followups/{followup}/selesai', [FollowupController::class, 'selesai'])->name('followups.selesai');
        Route::post('interaksi', [InteraksiController::class, 'store'])->name('interaksi.store');
        Route::patch('interaksi/{interaksi}/dibalas', [InteraksiController::class, 'dibalas'])->name('interaksi.dibalas');
        Route::patch('reaktivasi/{jadwal}/selesai', [ReaktivasiController::class, 'selesai'])->name('reaktivasi.selesai');
    });
});
