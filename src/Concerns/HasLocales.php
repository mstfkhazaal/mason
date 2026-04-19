<?php

namespace Awcodes\Mason\Concerns;

use Awcodes\Mason\Enums\LocaleStyle;
use Closure;

trait HasLocales
{
    protected array | Closure | null $locales = null;

    protected LocaleStyle | Closure | null $localeStyle = null;

    protected string | Closure | null $defaultLocale = null;

    /**
     * @param  array<string> | Closure  $locales
     */
    public function locales(array | Closure $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    public function localeStyle(LocaleStyle | Closure | null $style = null): static
    {
        $this->localeStyle = $style;

        return $this;
    }

    public function defaultLocale(string | Closure | null $locale = null): static
    {
        $this->defaultLocale = $locale;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getLocales(): array
    {
        return $this->evaluate($this->locales) ?? [];
    }

    public function getLocaleStyle(): LocaleStyle
    {
        return $this->evaluate($this->localeStyle) ?? LocaleStyle::Dropdown;
    }

    public function getDefaultLocale(): string
    {
        $locales = $this->getLocales();
        
        if (empty($locales)) {
            return 'en';
        }

        return $this->evaluate($this->defaultLocale) ?? $locales[0];
    }
}
