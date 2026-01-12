@php
    use Filament\Support\Facades\FilamentView;
    use Filament\Support\Icons\Heroicon;
    use function Filament\Support\generate_icon_html;

    $id = $getId();
    $key = $getKey();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $bricks = $getBricks();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        @if (FilamentView::hasSpaMode())
            {{-- format-ignore-start --}}x-load="visible || event (x-modal-opened)"{{-- format-ignore-end --}}
        @else
            x-load
        @endif
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('mason', 'awcodes/mason') }}"
        x-data="masonComponent({
            key: @js($key),
            livewireId: @js($this->getId()),
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
            statePath: @js($statePath),
            placeholder: @js($getPlaceholder()),
            isDisabled: @js($isDisabled),
            isLiveDebounced: @js($isLiveDebounced()),
            isLiveOnBlur: @js($isLiveOnBlur()),
            liveDebounce: @js($getLiveDebounce()),
            dblClickToEdit: @js($shouldDblClickToEdit()),
            bricks: @js(array_map(fn($brick) => is_string($brick) ? $brick : get_class($brick), $bricks)),
            previewLayout: @js($getPreviewLayout()),
        })"
        id="{{ 'mason-wrapper-' . $statePath }}"
        class="mason-wrapper"
        x-bind:class="{
            'fullscreen': fullscreen,
            'is-focused': isFocused,
            'display-mobile': viewport === 'mobile',
            'display-tablet': viewport === 'tablet',
            'display-desktop': viewport === 'desktop'
        }"
        x-on:keydown.escape.window="fullscreen = false"
    >
        <x-filament::input.wrapper
            :valid="! $errors->has($statePath)"
            :attributes="
                \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                    ->class([
                        'mason-input-wrapper',

                    ])
            "
        >
            <div
                @class([
                    'flex flex-1',
                    'flex-row-reverse' => $getSidebarPosition() === \Awcodes\Mason\Enums\SidebarPosition::Start,
                ])
            >
                <div class="mason-editor-wrapper">
                    <iframe
                        x-ref="previewIframe"
                        name="mason-preview-iframe"
                        class="mason-iframe"
                        wire:ignore
                        style="width: 100%; height: 100%; border: none;"
                    ></iframe>
                </div>

                @if (! $isDisabled && filled($bricks))
                    <div wire:key="sidebar-{{ hash('sha256', json_encode($bricks)) }}">
                        <x-mason::sidebar :bricks="$bricks" />
                    </div>
                @endif
            </div>
        </x-filament::input.wrapper>
    </div>
</x-dynamic-component>
