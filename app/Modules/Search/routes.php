<?php

use App\Modules\Search\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

// Public: anyone can browse (RN-01); requesting a rental needs a verified account.
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('stays/{property}', [CatalogController::class, 'show'])->name('stays.show');
