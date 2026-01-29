<?php

declare(strict_types=1);

use Awcodes\Mason\Support\MasonRenderer;
use Awcodes\Mason\Tests\Fixtures\TestBrick;

describe('mason() helper function', function () {
    it('returns MasonRenderer instance', function () {
        $renderer = mason([]);

        expect($renderer)->toBeInstanceOf(MasonRenderer::class);
    });

    it('accepts array content', function () {
        $content = [
            ['type' => 'masonBrick', 'attrs' => ['id' => 'test-brick', 'config' => ['title' => 'Test']]],
        ];
        $renderer = mason($content, [TestBrick::class]);

        expect($renderer->toArray())->toHaveCount(1);
    });

    it('accepts string content', function () {
        $content = json_encode([
            ['type' => 'masonBrick', 'attrs' => ['id' => 'test-brick', 'config' => []]],
        ]);
        $renderer = mason($content, [TestBrick::class]);

        expect($renderer->toArray())->toHaveCount(1);
    });

    it('accepts null content', function () {
        $renderer = mason(null, [TestBrick::class]);

        expect($renderer->toArray())->toBe([]);
    });

    it('accepts empty array content', function () {
        $renderer = mason([], [TestBrick::class]);

        expect($renderer->toArray())->toBe([]);
    });

    it('sets bricks', function () {
        $renderer = mason([], [TestBrick::class]);

        expect($renderer->getBricks())->toBe([TestBrick::class]);
    });

    it('renders to HTML', function () {
        $content = [
            ['type' => 'masonBrick', 'attrs' => ['id' => 'test-brick', 'config' => ['title' => 'Hello']]],
        ];
        $html = mason($content, [TestBrick::class])->toHtml();

        expect($html)->toContain('Hello');
    });

    it('renders to text', function () {
        $content = [
            ['type' => 'masonBrick', 'attrs' => ['id' => 'test-brick', 'config' => ['title' => 'Hello']]],
        ];
        $text = mason($content, [TestBrick::class])->toText();

        expect($text)->toContain('Hello')
            ->and($text)->not->toContain('<');
    });

    it('accepts content with wrapper', function () {
        $content = [
            'content' => [
                ['type' => 'masonBrick', 'attrs' => ['id' => 'test-brick', 'config' => []]],
            ],
        ];
        $renderer = mason($content, [TestBrick::class]);

        expect($renderer)->toBeInstanceOf(MasonRenderer::class)
            ->and($renderer->toArray())->toHaveCount(1);
    });
});
