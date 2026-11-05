<?php

use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/offers', [CatalogController::class, 'offers'])->name('offers.index');
Route::get('/offers/{slug}', [CatalogController::class, 'offer'])->name('offers.show');
Route::get('/brands', [CatalogController::class, 'brands'])->name('brands.index');
Route::get('/brands/{slug}', [CatalogController::class, 'brand'])->name('brands.show');
Route::get('/categories/{slug}', [CatalogController::class, 'category'])->name('categories.show');
Route::get('/search', [CatalogController::class, 'offers'])->name('search');
