<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\TransactionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\TransactionResource;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //            Actions\DeleteAction::make(),
        ];
    }
}
