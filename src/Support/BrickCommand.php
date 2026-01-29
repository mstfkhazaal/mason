<?php

declare(strict_types=1);

namespace Awcodes\Mason\Support;

use Illuminate\Contracts\Support\Arrayable;

readonly class BrickCommand implements Arrayable
{
    public function __construct(
        public string $name,
        public array $arguments = [],
    ) {}

    public static function make(string $name, array $arguments = []): static
    {
        return app(static::class, ['name' => $name, 'arguments' => $arguments]);
    }

    public static function insertBrick(array $brick, int $position): static
    {
        return static::make('insertBrick', [
            'brick' => $brick,
            'position' => $position,
        ]);
    }

    public static function updateBrick(int $index, array $brick): static
    {
        return static::make('updateBrick', [
            'index' => $index,
            'brick' => $brick,
        ]);
    }

    public static function deleteBrick(int $index): static
    {
        return static::make('deleteBrick', [
            'index' => $index,
        ]);
    }

    public static function moveBrick(int $from, int $to): static
    {
        return static::make('moveBrick', [
            'from' => $from,
            'to' => $to,
        ]);
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
}
