<?php

declare(strict_types=1);

use Awcodes\Mason\Http\Controllers\MasonController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('mason.routes.middleware'))
    ->prefix('mason')
    ->group(function () {
        Route::post('/preview', [MasonController::class, 'preview'])
            ->name('mason.preview');

        Route::post('/entry', [MasonController::class, 'entry'])
            ->name('mason.entry');
    });
