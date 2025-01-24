<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\AccountResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\AccountResource;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
