<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\JournalEntryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\JournalEntryResource;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
        ];
    }
}
