<?php

declare(strict_types=1);

namespace Awcodes\Mason\Concerns;

use Awcodes\Mason\Brick;
use Awcodes\Mason\Bricks\Section;
use Closure;

trait HasBricks
{
    protected array | Closure | null $bricks = null;

    protected ?string $bricksSortDirection = null;

    /**
     * @var array<string, class-string<Brick>>
     */
    protected array $cachedBricks;

    /**
     * @param  array<class-string<Brick>> | Closure | null  $bricks
     */
    public function bricks(array | Closure | null $bricks): static
    {
        $this->bricks = $bricks;

        return $this;
    }

    public function sortBricks(?string $direction = 'asc'): static
    {
        $this->bricksSortDirection = $direction;

        return $this;
    }

    public function getBricksSortDirection(): ?string
    {
        return $this->bricksSortDirection;
    }

    /**
     * @return array<class-string<Brick>>
     */
    public function getBricks(): array
    {
        $bricks = $this->evaluate($this->bricks) ?? [
            Section::class,
        ];

        if ($this->bricksSortDirection !== null) {
            usort(
                $bricks,
                fn ($a, $b): int => $this->bricksSortDirection === 'asc'
                ? $a::getLabel() <=> $b::getLabel()
                : $b::getLabel() <=> $a::getLabel()
            );
        }

        return $bricks;
    }

    /**
     * @return array<string, class-string<Brick>>
     */
    public function getCachedBricks(): array
    {
        if (isset($this->cachedBricks)) {
            return $this->cachedBricks;
        }

        foreach ($this->getBricks() as $brick) {
            $this->cachedBricks[$brick::getId()] = $brick;
        }

        return $this->cachedBricks;
    }

    /**
     * @return ?class-string<Brick>
     */
    public function getBrick(string $id): ?string
    {
        return $this->getCachedBricks()[$id] ?? null;
    }
}
