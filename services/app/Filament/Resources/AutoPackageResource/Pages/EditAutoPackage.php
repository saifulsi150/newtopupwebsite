<?php

namespace App\Filament\Resources\AutoPackageResource\Pages;

use App\Filament\Resources\AutoPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAutoPackage extends EditRecord
{
    protected static string $resource = AutoPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
