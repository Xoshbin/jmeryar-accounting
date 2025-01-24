<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\InvoiceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\InvoiceResource;
use Xoshbin\JmeryarAccounting\Models\Account;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['revenue_account_id'] = Account::where('type', Account::TYPE_REVENUE)->first()->id;
        $data['asset_account_id'] = Account::where('name', 'Accounts Receivable')->first()->id;

        return $data;
    }
}
