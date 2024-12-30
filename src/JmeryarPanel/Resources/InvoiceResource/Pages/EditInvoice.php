<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource;
use Xoshbin\JmeryarAccounting\Models\Account;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        $data['revenue_account_id'] = Account::where('type', Account::TYPE_REVENUE)->first()->id;
        $data['asset_account_id'] = Account::where('name', 'Accounts Receivable')->first()->id;

        return $data;
    }
}
