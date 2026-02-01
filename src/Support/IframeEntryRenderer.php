<?php

declare(strict_types=1);

namespace Awcodes\Mason\Support;

use Awcodes\Mason\Concerns\HasBricks;
use Filament\Support\Concerns\EvaluatesClosures;

class IframeEntryRenderer
{
    use EvaluatesClosures;
    use HasBricks;

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function __construct(protected array $blocks = [])
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
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
                return $brick::toHtml($config);
            }
        }

        return view('mason::components.unregistered-brick', ['label' => $id])->render();
    }

    /**
     * Render the full iframe HTML document
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
                'label' => $this->getBlockLabel($id),
            ];
        }, $this->blocks, array_keys($this->blocks));

        // Use the provided layout, fallback to config, then default
        $layoutToUse = $layout ?? config('mason.iframe-entry.layout');

        // If a layout is configured, use it with the entry content slotted in
        if ($layoutToUse) {
            return view($layoutToUse, [
                'blocks' => $blocks,
            ])->render();
        }

        // Otherwise, use the default full HTML document
        return view('mason::iframe-entry', [
            'blocks' => $blocks,
        ])->render();
    }

    /**
     * Get the label for a block
     */
    protected function getBlockLabel(?string $id): string
    {
        if (blank($id)) {
            return 'Unknown Brick';
        }

        foreach ($this->getBricks() as $brick) {
            if (is_string($brick) && ($brick::getId() === $id)) {
                return $brick::getLabel();
            }
        }

        return 'Unknown Brick';
    }
}
