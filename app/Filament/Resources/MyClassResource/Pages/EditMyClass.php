<?php

namespace App\Filament\Resources\MyClassResource\Pages;

use App\Filament\Resources\MyClassResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMyClass extends EditRecord
{
    protected static string $resource = MyClassResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
