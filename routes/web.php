<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\EditorialController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/offers', [CatalogController::class, 'offers'])->name('offers.index');
Route::get('/offers/{slug}', [CatalogController::class, 'offer'])->name('offers.show');
Route::get('/editor/previews/offers/{offer}', [CatalogController::class, 'previewOffer'])
    ->name('offers.preview');
Route::get('/brands', [CatalogController::class, 'brands'])->name('brands.index');
Route::get('/brands/{slug}', [CatalogController::class, 'brand'])->name('brands.show');
Route::get('/categories/{slug}', [CatalogController::class, 'category'])->name('categories.show');
Route::get('/search', [CatalogController::class, 'offers'])->name('search');
Route::get('/editor/previews/articles/{article}', [EditorialController::class, 'previewArticle'])->name('articles.preview');
Route::get('/editor/previews/pages/{page}', [EditorialController::class, 'previewPage'])->name('pages.preview');
Route::get('/articles', [EditorialController::class, 'articles'])->name('articles.index');
Route::get('/articles/{slug}', [EditorialController::class, 'article'])->name('articles.show');
Route::get('/pages/{slug}', [EditorialController::class, 'page'])->name('pages.show');
Route::get('/sitemap.xml', [EditorialController::class, 'sitemap'])->name('sitemap');
