<?php

namespace App\Filament\Widgets;

use Closure;
use Filament\Tables;
use App\Models\SubjectLoad;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\Classes;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SubjectLoads extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;


    public static function canView(): bool
    {
        return auth()->user()->hasRole('Subject Teacher');
    }

    protected function getTableQuery(): Builder
    {
        return SubjectLoad::query()->where('school_year_id', SchoolYear::where('current', 1)->first()->id)->where('teacher_id', auth()->user()->id)->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('class_id')
                ->searchable()
                ->sortable()
                ->toggleable()
                ->label("Class/Section")
                ->formatStateUsing(fn ($record) => Classes::where('id',$record->class_id)->first()->name),
            Tables\Columns\TextColumn::make('subject_id')
                ->searchable()
                ->sortable()
                ->toggleable()
                ->label("Subject")
                ->formatStateUsing(fn ($record) => Subject::where('id',$record->subject_id)->first()->subject_name),
        ];
    }
}
