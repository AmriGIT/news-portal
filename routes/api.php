<?php

use App\Http\Controllers\Api\NewsImportController;
use App\Http\Middleware\ValidateNewsImportToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:news-import', ValidateNewsImportToken::class])->group(function (): void {
    Route::post('/import/news', [NewsImportController::class, 'store'])->name('api.import.news');
    Route::get('/import/news/{newsImport:uuid}', [NewsImportController::class, 'show'])->name('api.import.news.show');
});
