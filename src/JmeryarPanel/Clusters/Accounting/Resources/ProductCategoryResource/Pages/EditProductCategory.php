<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\ProductCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources\ProductCategoryResource;

class EditProductCategory extends EditRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
