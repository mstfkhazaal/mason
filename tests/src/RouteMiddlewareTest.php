<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

describe('Route middleware configuration', function () {
    describe('default configuration', function () {
        it('has default middleware of web and auth', function () {
            $config = config('mason.routes.middleware');

            expect($config)->toBe(['web', 'auth']);
        });

        it('registers preview route with configured middleware', function () {
            $route = Route::getRoutes()->getByName('mason.preview');

            expect($route)->not->toBeNull()
                ->and($route->middleware())->toContain('web')
                ->and($route->middleware())->toContain('auth');
        });

        it('registers entry route with configured middleware', function () {
            $route = Route::getRoutes()->getByName('mason.entry');

            expect($route)->not->toBeNull()
                ->and($route->middleware())->toContain('web')
                ->and($route->middleware())->toContain('auth');
        });

        it('registers routes under mason prefix', function () {
            $previewRoute = Route::getRoutes()->getByName('mason.preview');
            $entryRoute = Route::getRoutes()->getByName('mason.entry');

            expect($previewRoute->uri())->toBe('mason/preview')
                ->and($entryRoute->uri())->toBe('mason/entry');
        });
    });

    describe('custom middleware configuration', function () {
        it('accepts custom auth guard middleware', function () {
            config()->set('mason.routes.middleware', ['web', 'auth:admin']);

            $middleware = config('mason.routes.middleware');

            expect($middleware)->toBe(['web', 'auth:admin']);
        });

        it('accepts additional middleware', function () {
            config()->set('mason.routes.middleware', ['web', 'auth', 'verified']);

            $middleware = config('mason.routes.middleware');

            expect($middleware)->toBe(['web', 'auth', 'verified']);
        });

        it('accepts single middleware as array', function () {
            config()->set('mason.routes.middleware', ['web']);

            $middleware = config('mason.routes.middleware');

            expect($middleware)->toBe(['web']);
        });
    });
});
