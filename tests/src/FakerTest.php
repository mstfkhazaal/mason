<?php

declare(strict_types=1);

use Awcodes\Mason\Support\Faker;

describe('Faker', function () {
    describe('make()', function () {
        it('creates a new instance', function () {
            $faker = Faker::make();

            expect($faker)->toBeInstanceOf(Faker::class);
        });
    });

    describe('brick()', function () {
        it('adds a brick to the content', function () {
            $faker = Faker::make()
                ->brick('test-brick', ['title' => 'Hello']);

            $json = $faker->asJson();

            expect($json)->toHaveCount(1)
                ->and($json[0]['type'])->toBe('masonBrick')
                ->and($json[0]['attrs']['id'])->toBe('test-brick')
                ->and($json[0]['attrs']['config'])->toBe(['title' => 'Hello']);
        });

        it('chains multiple bricks', function () {
            $faker = Faker::make()
                ->brick('test-brick', ['title' => 'First'])
                ->brick('test-brick', ['title' => 'Second'])
                ->brick('test-brick', ['title' => 'Third']);

            $json = $faker->asJson();

            expect($json)->toHaveCount(3)
                ->and($json[0]['attrs']['config']['title'])->toBe('First')
                ->and($json[1]['attrs']['config']['title'])->toBe('Second')
                ->and($json[2]['attrs']['config']['title'])->toBe('Third');
        });

        it('handles empty config', function () {
            $faker = Faker::make()
                ->brick('test-brick', []);

            $json = $faker->asJson();

            expect($json[0]['attrs']['config'])->toBe([]);
        });
    });

    describe('asJson()', function () {
        it('returns array representation', function () {
            $faker = Faker::make()
                ->brick('test-brick', ['title' => 'Test']);

            $json = $faker->asJson();

            expect($json)->toBeArray()
                ->and($json)->toHaveCount(1);
        });

        it('returns empty array when no bricks added', function () {
            $faker = Faker::make();

            expect($faker->asJson())->toBe([]);
        });
    });

    describe('asHtml()', function () {
        it('returns string output', function () {
            $faker = Faker::make()
                ->brick('section', ['text' => '<p>Hello World</p>']);

            $html = $faker->asHtml();

            // Section brick is registered by default
            expect($html)->toBeString()
                ->and($html)->toContain('Hello World');
        });

        it('returns empty string when no bricks', function () {
            $faker = Faker::make();

            expect($faker->asHtml())->toBe('');
        });
    });

    describe('asText()', function () {
        it('returns text without HTML tags', function () {
            $faker = Faker::make()
                ->brick('section', ['text' => '<p>Hello World</p>']);

            $text = $faker->asText();

            expect($text)->toBeString()
                ->and($text)->toContain('Hello World')
                ->and($text)->not->toContain('<p>');
        });
    });
});
