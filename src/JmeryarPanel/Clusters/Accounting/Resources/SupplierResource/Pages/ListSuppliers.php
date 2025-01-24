<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\SupplierResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\SupplierResource;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
