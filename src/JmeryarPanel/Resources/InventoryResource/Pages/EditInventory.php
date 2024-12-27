<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InventoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InventoryResource;

class EditInventory extends EditRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
