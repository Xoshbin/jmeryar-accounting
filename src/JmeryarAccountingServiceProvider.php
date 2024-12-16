<?php

namespace Xoshbin\JmeryarAccounting;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class JmeryarAccountingServiceProvider extends PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package
            ->name('jmeryar-accounting');
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ]);
        $this->publishes([
            __DIR__ . '/../database/seeders' => database_path('seeders'),
        ], 'seeds');
        $this->loadViewsFrom(__DIR__. '/../resources/views', 'jmeryar-accounting');
    }

    public function packageBooted()
    {
        FilamentAsset::register([
            Css::make('jmeryar-assets', __dir__ . '/../resources/css/jmeryar/theme.css')
        ]);
    }


}
