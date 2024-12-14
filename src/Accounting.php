<?php

namespace Xoshbin\JmeryarAccounting;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Pages\FinanceDashboard;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\BillResource;

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
            ->pages([
                FinanceDashboard::class
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Inventory')
                    ->icon('heroicon-o-table-cells'),
                NavigationGroup::make()
                    ->label('Customers')
                    ->icon('heroicon-o-document-arrow-down'),
                NavigationGroup::make()
                    ->label('Vendors')
                    ->icon('heroicon-o-document-arrow-up'),
                NavigationGroup::make()
                    ->label('Accounting')
                    ->icon('heroicon-o-book-open'),
                NavigationGroup::make()
                    ->label('Reports')
                    ->icon('heroicon-o-chart-bar'),
                NavigationGroup::make()
                    ->label('Configuration')
                    ->icon('heroicon-o-cog-8-tooth'),
            ]);
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
