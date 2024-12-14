<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductCategoryResource;

class ListProductCategories extends ListRecords
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
