<?php

namespace App\Filament\Resources\SubjectLoadResource\Pages;

use App\Filament\Resources\SubjectLoadResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubjectLoad extends EditRecord
{
    protected static string $resource = SubjectLoadResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
