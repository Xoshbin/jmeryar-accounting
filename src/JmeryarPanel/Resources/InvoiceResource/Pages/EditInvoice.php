<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Setting;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mutateFormDataBeforeSave(array $data): array
    {
        $data['revenue_account_id'] = Account::where('type', Account::TYPE_REVENUE)->first()->id;
        $data['asset_account_id'] = Account::where('name', 'Accounts Receivable')->first()->id;

        return $data;
    }
}
