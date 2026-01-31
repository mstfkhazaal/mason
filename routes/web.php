<?php

declare(strict_types=1);

use Awcodes\Mason\Http\Controllers\MasonController;
use Illuminate\Support\Facades\Route;

Route::post('/mason/preview', [MasonController::class, 'preview'])
    ->name('mason.preview')
    ->middleware(['web', 'auth']);

Route::post('/mason/entry', [MasonController::class, 'entry'])
    ->name('mason.entry')
    ->middleware(['web', 'auth']);
