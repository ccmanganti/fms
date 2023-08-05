<?php

namespace App\Filament\Resources\SchoolYearResource\Pages;

use App\Filament\Resources\SchoolYearResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchoolYear extends EditRecord
{
    protected static string $resource = SchoolYearResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
