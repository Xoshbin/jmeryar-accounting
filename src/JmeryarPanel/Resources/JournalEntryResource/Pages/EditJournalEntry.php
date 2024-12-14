<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\JournalEntryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\JournalEntryResource;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
