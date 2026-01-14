<?php

declare(strict_types=1);

namespace Awcodes\Mason\Support;

use Awcodes\Mason\Brick;
use Awcodes\Mason\Concerns\HasBricks;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Contracts\Support\Htmlable;

class IframeRenderer
{
    use EvaluatesClosures;
    use HasBricks;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $blocks = [];

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function __construct(array $blocks = [])
    {
        $this->blocks = $blocks;
        app('debugbar')->disable();
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public static function make(array $blocks = []): static
    {
        return app(static::class, ['blocks' => $blocks]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function setBlocks(array $blocks): static
    {
        $this->blocks = $blocks;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Get the HTML for a single block
     *
     * @param  array<string, mixed>  $block
     */
    public function getBlockHtml(array $block): ?string
    {
        if (($block['type'] ?? null) !== 'masonBrick') {
            return null;
        }

        $id = $block['attrs']['id'] ?? null;
        $config = $block['attrs']['config'] ?? [];

        if (blank($id)) {
            return null;
        }

        foreach ($this->getBricks() as $brick) {
            if (is_string($brick) && ($brick::getId() === $id)) {
                return $brick::toPreviewHtml($config);
            }
        }

        return null;
    }

    /**
     * Render the full iframe HTML document
     *
     * @param  string|null  $layout
     */
    public function toHtml(?string $layout = null): string
    {
        $blocks = array_map(function (array $block, int $index): array {
            $html = $this->getBlockHtml($block);
            $id = $block['attrs']['id'] ?? null;
            $config = $block['attrs']['config'] ?? [];

            return [
                'index' => $index,
                'id' => $id,
                'config' => $config,
                'html' => $html,
                'label' => $this->getBlockLabel($id, $config),
            ];
        }, $this->blocks, array_keys($this->blocks));

        // Use provided layout, fallback to config, then default
        $layoutToUse = $layout ?? config('mason.iframe.layout');

        // If a layout is configured, use it with the preview content slotted in
        if ($layoutToUse) {
            return view($layoutToUse, [
                'blocks' => $blocks,
            ])->render();
        }

        // Otherwise, use the default full HTML document
        return view('mason::iframe-preview', [
            'blocks' => $blocks,
        ])->render();
    }

    /**
     * Get the label for a block
     */
    protected function getBlockLabel(?string $id, array $config): string
    {
        if (blank($id)) {
            return 'Unknown Brick';
        }

        foreach ($this->getBricks() as $brick) {
            if (is_string($brick) && ($brick::getId() === $id)) {
                return $brick::getPreviewLabel($config);
            }
        }

        return 'Unknown Brick';
    }
}
