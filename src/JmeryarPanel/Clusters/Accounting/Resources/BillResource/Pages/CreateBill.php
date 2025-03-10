<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\BillResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\BillResource;
use Xoshbin\JmeryarAccounting\Models\Account;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['expense_account_id'] = Account::where('type', Account::TYPE_EXPENSE)->first()->id;
        $data['liability_account_id'] = Account::where('name', 'Accounts Payable')->first()->id;

        return $data;
    }
}
