<?php

namespace App\Filament\Resources\EClassRecordResource\Pages;

use App\Filament\Resources\EClassRecordResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEClassRecords extends ListRecords
{
    protected static string $resource = EClassRecordResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EClassRecordResource\Widgets\EClassOverview::class,
        ];
    }
}
