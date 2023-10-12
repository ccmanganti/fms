<?php

namespace App\Filament\Widgets;

use Closure;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Student;
use App\Models\StudentOfClass;
use App\Models\Classes;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;


class ClassStudents extends BaseWidget
{

    
    public static function canView(): bool
    {
        if ($currentPath=Route::getFacadeRoot()->current()->uri() == "/"){
            return false;
        } else {
            return true;
        }
    }


    protected function getTableQuery(): Builder
    {
        $request = request(); // Get the current request using the global helper function
        $currentUri = $request->path(); // Use the Request object to get the current path
        $uriSegments = explode('/', $currentUri);

        // $currentClass = Classes::where('id', (int)$uriSegments[1]);
        // return Student::query()->latest();
        $currentClass = Classes::where('id', (int)$uriSegments[1])->first();
        $studentIds = $currentClass->students;
        // dd($studentIds);
        // Query the Student model to get students whose IDs are in the $studentIds array
        $students = StudentOfClass::whereIn('lrn', $studentIds)->where('name', $currentClass->name)->latest();
        return $students;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('lrn')->label("LRN")->searchable()->sortable(),
            TextColumn::make('student_name')->label("Name")->searchable()->sortable()
            ->formatStateUsing(fn ($record) => $record->lname.', '.$record->fname.', '.$record->mname),
            // TextColumn::make('lname')->label("Last Name")->searchable()->sortable()->toggleable(),
            // TextColumn::make('fname')->label("First Name")->searchable()->sortable()->toggleable(),
            // TextColumn::make('mname')->label("Middle Name")->searchable()->sortable()->toggleable(),
            TextColumn::make('gender')->searchable()->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('export_sf9')
                ->label('SF9.XLSX')
                ->icon('heroicon-o-newspaper')
                ->url(fn (StudentOfClass $record): string => ('/'.$record->id.'/export-sf9/')),
            Action::make('export_sf9_pdf')
                ->label('SF9.PDF')
                ->icon('heroicon-o-printer')
                ->url(fn (StudentOfClass $record): string => ('/'.$record->id.'/export-sf9-pdf/'))
                ->openUrlInNewTab(),
            Action::make('export_sf10')
                ->icon('heroicon-o-newspaper')
                ->label('SF10.XLSX')
                ->url(fn (StudentOfClass $record): string => ('/'.$record->id.'/export-sf10/')),
            Action::make('export_sf10_pdf')
                ->label('SF10.PDF')
                ->icon('heroicon-o-printer')
                ->url(fn (StudentOfClass $record): string => ('/'.$record->id.'/export-sf10-pdf/'))
                ->openUrlInNewTab(),

        ];
    }

}
