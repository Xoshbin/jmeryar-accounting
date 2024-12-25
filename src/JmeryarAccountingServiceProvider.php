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
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ]);


        $this->publishes([
            __DIR__ . '/../database/seeders' => database_path('seeders'),
        ], 'jmeryar-accounting-seeds');


        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'jmeryar-accounting');

        //Publish Views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/jmeryar-accounting'),
        ], 'jmeryar-accounting-views');

        //Register Langs
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'jmeryar-accounting');

        //Publish Lang
        $this->publishes([
            __DIR__ . '/../resources/lang' => base_path('lang/vendor/jmeryar-accounting'),
        ], 'jmeryar-accounting-lang');
    }

    public function packageBooted()
    {
        FilamentAsset::register([
            Css::make('jmeryar-assets', __DIR__ . '/../resources/css/jmeryar/theme.css'),
        ]);
    }
}
