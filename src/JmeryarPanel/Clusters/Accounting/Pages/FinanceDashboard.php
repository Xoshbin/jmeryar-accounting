<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting;

class FinanceDashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $routePath = 'finance';

    protected static ?string $title = 'Finance dashboard';

    protected static ?string $cluster = Accounting::class;

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::jmeryar.finance_dashboard');
    }

    public function getTitle(): string
    {
        return __('jmeryar-accounting::jmeryar.finance_dashboard');
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->minDate(fn (Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(3),
            ]);
    }
}
