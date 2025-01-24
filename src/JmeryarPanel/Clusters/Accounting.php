<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters;

use Filament\Clusters\Cluster;

class Accounting extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public function getTitle(): string
    {
        return __('jmeryar-accounting::jmeryar.accounting');
    }

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::jmeryar.accounting');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('jmeryar-accounting::jmeryar.accounting');
    }
}
