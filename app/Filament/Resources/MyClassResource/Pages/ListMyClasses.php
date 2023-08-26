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
        $class = Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id ?? 0)->where('adviser_id', auth()->user()->id)->first();
        if($class){
            $actions[] = Actions\ActionGroup::make([
                Action::make('export_sf1_first_xlsx')
                ->label('SF1 - 1st Sem.xlsx')
                ->url(('/'.$class->id.'/export-sf1-first')),
                Action::make('export_sf1_second_xlsx')
                ->label('SF1 - 2nd Sem.xlsx')
                ->url(('/'.$class->id.'/export-sf1-second')),
                Action::make('export_sf1_first_pdf')
                ->label('SF1 - 1st Sem.pdf')
                ->url(('/'.$class->id.'/export-sf1-first-pdf')),
                Action::make('export_sf1_second_pdf')
                ->label('SF1 - 2nd Sem.pdf')
                ->url(('/'.$class->id.'/export-sf1-second-pdf'))
            ]);
            // $actions[] = Action::make('export_sf1_first_xlsx')
            // ->label('SF1 - 1st Sem.xlsx')
            // ->url(('/'.$class->id.'/export-sf1-first'));
            // $actions[] = Action::make('export_sf1_second_xlsx')
            // ->label('SF1 - 2nd Sem.xlsx')
            // ->url(('/'.$class->id.'/export-sf1-second'));
        } else{
            $actions = [];
        }

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MyClassResource\Widgets\MyClassOverview::class,
        ];
    }
}
