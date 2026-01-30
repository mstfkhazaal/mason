<?php

declare(strict_types=1);

namespace Awcodes\Mason;

use Awcodes\Mason\Concerns\HasBricks;
use Closure;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Infolists\Components\Entry;

class MasonEntry extends Entry
{
    use HasBricks;
    use HasExtraInputAttributes;

    protected string $view = 'mason::mason-entry';

    protected string | Closure | null $previewLayout = null;

    public function previewLayout(string | Closure | null $layout): static
    {
        $this->previewLayout = $layout;

        return $this;
    }

    public function getPreviewLayout(): ?string
    {
        return $this->evaluate($this->previewLayout) ?? config('mason.entry.layout');
    }
}
