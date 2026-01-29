@props([
    'label' => null,
])

<div class="not-prose flex items-center gap-3 bg-gray-100 p-4 dark:bg-gray-800">
    <x-filament::icon
        icon="heroicon-o-exclamation-triangle"
        class="text-danger-600 dark:text-danger-400 h-6 w-6"
    />
    <p>{{ trans('mason::mason.unregistered_brick', ['label' => $label]) }}</p>
</div>
