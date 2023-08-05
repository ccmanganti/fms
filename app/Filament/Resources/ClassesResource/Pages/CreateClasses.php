<?php

namespace App\Filament\Resources\ClassesResource\Pages;

use App\Filament\Resources\ClassesResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClasses extends CreateRecord
{
    protected static string $resource = ClassesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
