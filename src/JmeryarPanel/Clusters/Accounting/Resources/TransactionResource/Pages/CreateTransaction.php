<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\TransactionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\TransactionResource;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
}
