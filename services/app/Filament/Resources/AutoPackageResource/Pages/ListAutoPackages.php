<?php

namespace App\Filament\Resources\AutoPackageResource\Pages;

use App\Filament\Resources\AutoPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutoPackages extends ListRecords
{
    protected static string $resource = AutoPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
