<?php

namespace App\Filament\Resources\SchoolYearResource\Pages;

use App\Filament\Resources\SchoolYearResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchoolYears extends ListRecords
{
    protected static string $resource = SchoolYearResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            SchoolYearResource\Widgets\SchoolYearOverview::class,
        ];
    }
}
