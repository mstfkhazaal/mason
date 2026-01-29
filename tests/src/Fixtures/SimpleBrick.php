<?php

declare(strict_types=1);

namespace Awcodes\Mason\Tests\Fixtures;

use Awcodes\Mason\Brick;

class SimpleBrick extends Brick
{
    public static function getId(): string
    {
        return 'simple-brick';
    }

    public static function toHtml(array $config, ?array $data = null): ?string
    {
        return '<div class="simple-brick">Simple content</div>';
    }
}
