<?php

namespace App\Filament\Resources\AutoTopupOrderResource\Pages;

use App\Filament\Resources\AutoTopupOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutoTopupOrders extends ListRecords
{
    protected static string $resource = AutoTopupOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
