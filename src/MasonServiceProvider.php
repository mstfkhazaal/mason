<?php

declare(strict_types=1);

namespace Awcodes\Mason;

use Awcodes\Mason\Commands\MakeBrickCommand;
use Awcodes\Mason\Commands\UpgradeBricksCommand;
use Awcodes\Mason\Testing\TestsMason;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Livewire\Features\SupportTesting\Testable;
use ReflectionException;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MasonServiceProvider extends PackageServiceProvider
{
    public static string $name = 'mason';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasRoute('web')
            ->hasCommands([
                MakeBrickCommand::class,
                UpgradeBricksCommand::class,
            ]);
    }

    public function packageRegistered(): void {}

    /**
     * @throws ReflectionException
     */
    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        if (app()->runningInConsole()) {
            foreach (app(abstract: Filesystem::class)->files(directory: __DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path(path: "stubs/filament/mason/{$file->getFilename()}"),
                ], groups: 'mason-stubs');
            }
        }

        Blade::directive(
            name: 'mason',
            handler: fn ($expression): string => "<?php echo (new Awcodes\Mason\Support\MasonRenderer({$expression}))->toHtml(); ?>"
        );

        Blade::directive(
            name: 'masonStyles',
            handler: function (): string {
                $styles = <<<'CSS'
                    <style>
                        body {
                            margin: 0;
                            padding: 0;
                            font-family: system-ui, -apple-system, sans-serif;
                        }
                        .mason-block {
                            position: relative;
                            min-height: 2rem;
                            cursor: move;
                            transition: outline 0.2s;
                        }
                        .mason-block:hover {
                            outline: 2px dashed #0ea5e9;
                            outline-offset: 2px;
                        }
                        .mason-block.selected {
                            outline: 4px solid #0ea5e9;
                            outline-offset: -4px;
                        }
                        .mason-block.dragging {
                            opacity: 0.5;
                            cursor: grabbing;
                            pointer-events: none;
                            user-select: none;
                        }
                        .mason-block.dragging * {
                            pointer-events: none;
                            user-select: none;
                        }
                        .mason-block-content {
                            pointer-events: auto;
                        }
                        .mason-block.dragging .mason-block-content * {
                            pointer-events: none !important;
                            user-select: none !important;
                            -webkit-user-select: none !important;
                            -moz-user-select: none !important;
                            -ms-user-select: none !important;
                        }
                        .mason-block-controls {
                            position: absolute;
                            top: 0.5rem;
                            right: 0.5rem;
                            z-index: 10;
                            display: none;
                            gap: 0.25rem;
                            padding: 0.5rem;
                            background: rgba(0, 0, 0, 0.8);
                            border-radius: 0.25rem;
                        }
                        .mason-block.selected .mason-block-controls {
                            display: flex;
                        }
                        .mason-block-btn {
                            background: transparent;
                            border: none;
                            color: white;
                            cursor: pointer;
                            padding: 0.25rem;
                            border-radius: 0.25rem;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        .mason-block-btn:hover:not(:disabled) {
                            background: rgba(255, 255, 255, 0.2);
                        }
                        .mason-block-btn:disabled {
                            opacity: 0.3;
                            cursor: not-allowed;
                        }
                        .mason-block-btn svg {
                            width: 1rem;
                            height: 1rem;
                        }
                        .mason-drop-zone {
                            min-height: 2rem;
                            border: 2px dashed transparent;
                            transition: border-color 0.2s;
                        }
                        .mason-drop-zone.active {
                            border-color: #0ea5e9;
                            background: rgba(14, 165, 233, 0.1);
                        }
                    </style>
                CSS;

                return $styles;
            }
        );

        Testable::mixin(new TestsMason);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'awcodes/mason';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            AlpineComponent::make(id: 'mason', path: __DIR__ . '/../resources/dist/mason.js'),
        ];
    }
}
