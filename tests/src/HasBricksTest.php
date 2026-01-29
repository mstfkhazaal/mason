<?php

declare(strict_types=1);

use Awcodes\Mason\Bricks\Section;
use Awcodes\Mason\Mason;
use Awcodes\Mason\Support\MasonRenderer;
use Awcodes\Mason\Tests\Fixtures\SimpleBrick;
use Awcodes\Mason\Tests\Fixtures\TestBrick;

describe('HasBricks trait', function () {
    describe('on Mason field', function () {
        it('sets bricks via bricks()', function () {
            $field = Mason::make('content')
                ->bricks([TestBrick::class, SimpleBrick::class]);

            expect($field->getBricks())->toBe([TestBrick::class, SimpleBrick::class]);
        });

        it('returns default Section brick when not set', function () {
            $field = Mason::make('content');

            expect($field->getBricks())->toBe([Section::class]);
        });

        it('accepts closure for bricks', function () {
            $field = Mason::make('content')
                ->bricks(fn () => [TestBrick::class]);

            expect($field->getBricks())->toBe([TestBrick::class]);
        });

        it('caches bricks by id', function () {
            $field = Mason::make('content')
                ->bricks([TestBrick::class, SimpleBrick::class]);

            $cached = $field->getCachedBricks();

            expect($cached)->toHaveKey('test-brick')
                ->and($cached)->toHaveKey('simple-brick')
                ->and($cached['test-brick'])->toBe(TestBrick::class)
                ->and($cached['simple-brick'])->toBe(SimpleBrick::class);
        });

        it('returns cached bricks on subsequent calls', function () {
            $field = Mason::make('content')
                ->bricks([TestBrick::class]);

            $first = $field->getCachedBricks();
            $second = $field->getCachedBricks();

            expect($first)->toBe($second);
        });

        it('gets brick by id', function () {
            $field = Mason::make('content')
                ->bricks([TestBrick::class, SimpleBrick::class]);

            expect($field->getBrick('test-brick'))->toBe(TestBrick::class)
                ->and($field->getBrick('simple-brick'))->toBe(SimpleBrick::class);
        });

        it('returns null for unknown brick id', function () {
            $field = Mason::make('content')
                ->bricks([TestBrick::class]);

            expect($field->getBrick('unknown-brick'))->toBeNull();
        });
    });

    describe('on MasonRenderer', function () {
        it('sets bricks via bricks()', function () {
            $renderer = MasonRenderer::make([])
                ->bricks([TestBrick::class]);

            expect($renderer->getBricks())->toBe([TestBrick::class]);
        });

        it('returns default Section brick when not set', function () {
            $renderer = MasonRenderer::make([]);

            expect($renderer->getBricks())->toBe([Section::class]);
        });

        it('gets brick by id', function () {
            $renderer = MasonRenderer::make([])
                ->bricks([TestBrick::class, SimpleBrick::class]);

            expect($renderer->getBrick('test-brick'))->toBe(TestBrick::class);
        });
    });
});
