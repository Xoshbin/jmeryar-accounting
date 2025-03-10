<?php

namespace Xoshbin\JmeryarAccounting;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;

class Accounting implements Plugin
{
    public function getId(): string
    {
        return 'xoshbin-jmeryar-accounting';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(
                in: __DIR__ . '/JmeryarPanel/Resources',
                for: 'Xoshbin\\JmeryarAccounting\\JmeryarPanel\\Resources'
            )
            ->discoverPages(
                in: __DIR__ . '/JmeryarPanel/Pages',
                for: 'Xoshbin\\JmeryarAccounting\\JmeryarPanel\\Pages'
            )
            ->discoverWidgets(
                in: __DIR__ . '/JmeryarPanel/Widgets',
                for: 'Xoshbin\\JmeryarAccounting\\JmeryarPanel\\Widgets'
            )
            ->discoverClusters(
                in: __DIR__ . '/JmeryarPanel/Clusters',
                for: 'Xoshbin\\JmeryarAccounting\\JmeryarPanel\\Clusters'
            )
            ->navigationGroups([
                // TODO: FIX: the navigation sort is broken after adding the locales
                //                NavigationGroup::make('inventory')
                //                    ->label(__('jmeryar-accounting::jmeryar.inventory'))
                //                    ->icon('heroicon-o-table-cells'),
                //                NavigationGroup::make('customers')
                //                    ->label(__('jmeryar-accounting::jmeryar.customers'))
                //                    ->icon('heroicon-o-document-arrow-down'),
                //                NavigationGroup::make('vendors')
                //                    ->label(__('jmeryar-accounting::jmeryar.vendors'))
                //                    ->icon('heroicon-o-document-arrow-up'),
                //                NavigationGroup::make('accounting')
                //                    ->label(__('jmeryar-accounting::jmeryar.accounting'))
                //                    ->icon('heroicon-o-book-open'),
                //                NavigationGroup::make('reports')
                //                    ->label(trans('jmeryar-accounting::jmeryar.reports'))
                //                    ->icon('heroicon-o-chart-bar'),
                //                NavigationGroup::make('configuration')
                //                    ->label(__('jmeryar-accounting::jmeryar.configuration'))
                //                    ->icon('heroicon-o-cog-8-tooth'),
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
