<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Pages\Actions;
use Filament\Pages\Actions\Action;
use App\Models\Classes;
use App\Models\SchoolYear;
use Filament\Resources\Pages\ListRecords;
use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\Actions\ImportField;
use Illuminate\Support\Str;

Str::macro('capitalizeWords', function ($value) {
    return ucwords($value);
});

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getActions(): array
    {
        $actions = [];
        if (auth()->user()->hasRole('Superadmin')) {
            $actions[] = Actions\CreateAction::make();
            $actions[] = ImportAction::make()
            ->handleBlankRows(true)
            ->fields([
                ImportField::make('lrn')
                    ->required()
                    ->label('Lrn')
                    ->helperText('Define as project helper'),
                ImportField::make('lname')
                    ->required()
                    ->label('Last Name'),
                ImportField::make('fname')
                    ->required()
                    ->label('First Name'),
                ImportField::make('mname')
                    ->required()
                    ->label('Middle Name'),
                ImportField::make('gender')
                    ->label('Gender'),
                ImportField::make('date_of_birth')
                    ->label('Date of Birth'),
                ImportField::make('age')
                    ->label('Age'),
                ImportField::make('religion')
                    ->label('Religion'),
                ImportField::make('no_street_purok')
                    ->label('No./Street/Purok'),
                ImportField::make('barangay')
                    ->label('Barangay'),
                ImportField::make('municipality')
                    ->label('Municipality'),
                ImportField::make('province')
                    ->label('Province'),
                ImportField::make('father_name')
                    ->label('Father\'s Name'),
                ImportField::make('mother_name')
                    ->label('Mother\'s Name'),
                ImportField::make('guardian')
                    ->label('Guardian'),
                ImportField::make('relationship')
                    ->label('Relationship'),
                ImportField::make('contact_number')
                    ->label('Contact Number'),
                ImportField::make('modality')
                    ->label('Modality'),
                ImportField::make('student_type')
                    ->label('Student Type'),
                ImportField::make('remarks')
                    ->label('Remarks'),
            ], columns:3);
            
        }
        else{
            $class = Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->where('adviser_id', auth()->user()->id)->first();
            if($class){
                $actions[] = Action::make('export')
                ->label('Export SF1')
                ->url(('/'.$class->id.'/export-sf1'));
            } else{
                $actions = [];
            }
            
        }

        return $actions;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StudentResource\Widgets\StudentArchiveOverview::class,
        ];
    }

}

