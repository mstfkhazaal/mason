<?php

declare(strict_types=1);

namespace Awcodes\Mason\Support;

use Illuminate\Contracts\Support\Arrayable;

readonly class BlockCommand implements Arrayable
{
    public function __construct(
        public string $name,
        public array $arguments = [],
    ) {}

    public static function make(string $name, array $arguments = []): static
    {
        return app(static::class, ['name' => $name, 'arguments' => $arguments]);
    }

    /**
     * @return array{name: string, arguments: array}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];
    }

    public static function insertBlock(array $brick, int $position): static
    {
        return static::make('insertBlock', [
            'brick' => $brick,
            'position' => $position,
        ]);
    }

    public static function updateBlock(int $index, array $brick): static
    {
        return static::make('updateBlock', [
            'index' => $index,
            'brick' => $brick,
        ]);
    }

    public static function deleteBlock(int $index): static
    {
        return static::make('deleteBlock', [
            'index' => $index,
        ]);
    }

    public static function moveBlock(int $from, int $to): static
    {
        return static::make('moveBlock', [
            'from' => $from,
            'to' => $to,
        ]);
    }
}
