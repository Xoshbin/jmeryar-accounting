<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Setting;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('pdf')
                ->label(__('jmeryar-accounting::invoices.form.PDF/Print'))
                ->color('success')
                ->action(function (Model $record) {
                    Pdf::setOptions(['debugCss' => false]);
                    $setting = Setting::first();

                    return response()->streamDownload(function () use ($record, $setting) {
                        echo Pdf::loadHtml(
                            Blade::render('jmeryar-accounting::invoices.invoice', ['record' => $record, 'setting' => $setting]),
                        )->stream();
                    }, $record->invoice_number . '.pdf');
                }),
        ];
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        $data['revenue_account_id'] = Account::where('type', Account::TYPE_REVENUE)->first()->id;
        $data['asset_account_id'] = Account::where('name', 'Accounts Receivable')->first()->id;

        return $data;
    }
}
