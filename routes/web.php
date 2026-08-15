<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\PrediksiController;

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get(
    '/',
    [DashboardController::class,'index']
)->name('dashboard');

/*
|--------------------------------------------------------------------------
| Data Produksi
|--------------------------------------------------------------------------
*/

Route::prefix('produksi')->group(function(){

    Route::get(
        '/',
        [ProduksiController::class,'index']
    )->name('produksi.index');

    Route::get(
        '/import',
        [ProduksiController::class,'import']
    )->name('produksi.import');

    Route::post(
        '/upload',
        [ProduksiController::class,'upload']
    )->name('produksi.upload');

    Route::delete(
        '/{id}',
        [ProduksiController::class,'destroy']
    )->name('produksi.destroy');

});

/*
|--------------------------------------------------------------------------
| Training Model
|--------------------------------------------------------------------------
*/

Route::prefix('training')->group(function(){

    Route::get(
        '/',
        [TrainingController::class,'index']
    )->name('training.index');

    Route::post(
        '/proses',
        [TrainingController::class,'train']
    )->name('training.train');

});

/*
|--------------------------------------------------------------------------
| Prediksi
|--------------------------------------------------------------------------
*/

Route::prefix('prediksi')->group(function(){

    Route::get('/',[PrediksiController::class,'index'])
        ->name('prediksi.index');

    Route::post('/proses',[PrediksiController::class,'proses'])
        ->name('prediksi.proses');

    Route::get('/history',[PrediksiController::class,'history'])
        ->name('prediksi.history');

    Route::get('/history/{id}', [PrediksiController::class,'show'])
    ->name('prediksi.show');

});
