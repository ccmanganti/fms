<?php

namespace App\Filament\Widgets;

use Closure;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SubjectLoad;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\StudentOfClass;
use App\Models\Classes;

class MyClass extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Adviser');
    }

    protected function getTableQuery(): Builder
    {
        $currentSchoolYearId = SchoolYear::where('current', true)->value('id');
        // Get the student LRNs of the classes owned by the current adviser
        $studentLRNs = Classes::where('adviser_id', auth()->user()->id)
            ->where('school_year_id', $currentSchoolYearId)
            ->pluck('students')
            ->flatten()
            ->toArray();
        return StudentOfClass::whereIn('lrn', $studentLRNs)->where('school_year_id', $currentSchoolYearId);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('lrn')->label("LRN")->searchable()->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('student_name')->label("Name")->searchable()->sortable()->toggleable()
            ->formatStateUsing(fn ($record) => $record->lname.', '.$record->fname.', '.$record->mname),
            Tables\Columns\TextColumn::make('gender')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
