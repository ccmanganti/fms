<?php

namespace App\Filament\Resources\EClassRecordResource\Pages;

use App\Filament\Resources\EClassRecordResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEClassRecord extends EditRecord
{
    protected static string $resource = EClassRecordResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
