<?php

declare(strict_types=1);

use Awcodes\Mason\Support\IframeRenderer;
use Illuminate\Support\Facades\Route;

Route::post('/mason/preview', function () {
    // Handle both JSON and form data
    $blocksJson = request()->input('blocks');
    $bricksJson = request()->input('bricks');
    $layout = request()->input('layout');

    // Decode JSON strings if they come from form submission
    $blocks = is_string($blocksJson) ? json_decode($blocksJson, true) : ($blocksJson ?? []);
    $bricks = is_string($bricksJson) ? json_decode($bricksJson, true) : ($bricksJson ?? []);

    if (! is_array($blocks)) {
        $blocks = [];
    }

    $renderer = IframeRenderer::make($blocks);

    if (filled($bricks) && is_array($bricks)) {
        // Convert string class names back to class strings if needed
        $brickClasses = array_map(function ($brick) {
            if (is_string($brick) && class_exists($brick)) {
                return $brick;
            }

            return $brick;
        }, $bricks);

        $renderer->bricks($brickClasses);
    }

    // Use layout from request, fallback to config
    $layoutToUse = $layout ?? config('mason.iframe.layout');

    return response($renderer->toHtml($layoutToUse))
        ->header('Content-Type', 'text/html');
})->name('mason.preview')->middleware('web');
