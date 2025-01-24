<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\BillResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\BillResource;
use Xoshbin\JmeryarAccounting\Models\Account;

class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    public function mutateFormDataBeforeSave(array $data): array
    {
        $data['expense_account_id'] = Account::where('type', Account::TYPE_EXPENSE)->first()->id;
        $data['liability_account_id'] = Account::where('name', 'Accounts Payable')->first()->id;

        return $data;
    }
}
