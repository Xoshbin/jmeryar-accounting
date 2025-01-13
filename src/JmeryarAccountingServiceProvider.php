<?php

namespace Xoshbin\JmeryarAccounting;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Xoshbin\JmeryarAccounting\Console\JmeryarAccountingInstallCommand;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class JmeryarAccountingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('jmeryar-accounting')
            ->discoversMigrations()
            ->runsMigrations(true)
            ->hasViews('jmeryar-accounting')
            ->hasTranslations();
    }

    public function packageBooted()
    {
        // Dynamically set the Spatie Media Library configuration
        // without this some tests fail
        config()->set('media-library.media_model', Media::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                JmeryarAccountingInstallCommand::class,
            ]);
        }

        FilamentAsset::register([
            Css::make('jmeryar-assets', __DIR__ . '/../resources/css/jmeryar/theme.css'),
        ]);
    }
}
