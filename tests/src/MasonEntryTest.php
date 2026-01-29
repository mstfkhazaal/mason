<?php

declare(strict_types=1);

use Awcodes\Mason\Bricks\Section;
use Awcodes\Mason\MasonEntry;
use Awcodes\Mason\Tests\Fixtures\TestBrick;

describe('MasonEntry', function () {
    it('creates entry with name', function () {
        $entry = MasonEntry::make('content');

        expect($entry)->toBeInstanceOf(MasonEntry::class);
    });

    it('has HasBricks trait functionality', function () {
        $entry = MasonEntry::make('content')
            ->bricks([TestBrick::class]);

        expect($entry->getBricks())->toBe([TestBrick::class]);
    });

    it('returns default Section brick when not set', function () {
        $entry = MasonEntry::make('content');

        expect($entry->getBricks())->toBe([Section::class]);
    });

    it('caches bricks by id', function () {
        $entry = MasonEntry::make('content')
            ->bricks([TestBrick::class]);

        $cached = $entry->getCachedBricks();

        expect($cached)->toHaveKey('test-brick')
            ->and($cached['test-brick'])->toBe(TestBrick::class);
    });

    it('gets brick by id', function () {
        $entry = MasonEntry::make('content')
            ->bricks([TestBrick::class]);

        expect($entry->getBrick('test-brick'))->toBe(TestBrick::class)
            ->and($entry->getBrick('unknown'))->toBeNull();
    });
});
