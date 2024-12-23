<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource;
use Xoshbin\JmeryarAccounting\Models\Account;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['revenue_account_id'] = Account::where('type', Account::TYPE_REVENUE)->first()->id;
        $data['inventory_account_id'] = Account::where('name', Account::TYPE_ACCOUNTS_RECEIVABLE)->first()->id;

        return $data;
    }
}
