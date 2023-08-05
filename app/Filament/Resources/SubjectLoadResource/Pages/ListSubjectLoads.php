<?php

namespace App\Filament\Resources\SubjectLoadResource\Pages;

use App\Filament\Resources\SubjectLoadResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubjectLoads extends ListRecords
{
    protected static string $resource = SubjectLoadResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
