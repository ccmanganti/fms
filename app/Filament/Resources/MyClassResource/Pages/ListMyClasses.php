<?php

namespace App\Filament\Resources\MyClassResource\Pages;

use App\Filament\Resources\MyClassResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\StudentResource;
use Filament\Pages\Actions\Action;
use App\Models\Classes;
use App\Models\SchoolYear;
use Konnco\FilamentImport\Actions\ImportAction;
use Illuminate\Support\Str;

class ListMyClasses extends ListRecords
{
    protected static string $resource = MyClassResource::class;

    protected function getActions(): array
    {
        // $actions[] = Actions\CreateAction::make();
        $class = Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->where('adviser_id', auth()->user()->id)->first();
        if($class){
            $actions[] = Action::make('export')
            ->label('Export SF1')
            ->url(('/'.$class->id.'/export-sf1'));
        } else{
            $actions = [];
        }

        return $actions;
    }
}
