<?php

namespace App\Filament\Resources\EClassRecordResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\SchoolYear;
use App\Models\Classes;
use App\Models\StudentOfClass;
use App\Models\SubjectLoad;

class EClassOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total Subject Loads', function(){
                return SubjectLoad::where('teacher_id', auth()->user()->id)
                ->where('school_year_id', SchoolYear::where('current', 1)->first()->id)
                ->count();
            })
            ->description('For this S.Y.')
            ->descriptionIcon('heroicon-s-bookmark')
            ->color('success'),
            Card::make('Grade 11', function(){
                $grade11ClassIds = Classes::where('grade_level', 11)->pluck('id');
                return SubjectLoad::whereIn('class_id', $grade11ClassIds)
                    ->where('teacher_id', auth()->user()->id)
                    ->where('school_year_id', SchoolYear::where('current', 1)->first()->id)
                    ->count();
            })
            ->description('Subjects assigned')
            ->descriptionIcon('heroicon-o-newspaper')
            ->color('primary'),
            Card::make('Grade 12', function(){
                $grade11ClassIds = Classes::where('grade_level', 12)->pluck('id');
                return SubjectLoad::whereIn('class_id', $grade11ClassIds)
                    ->where('teacher_id', auth()->user()->id)
                    ->where('school_year_id', SchoolYear::where('current', 1)->first()->id)
                    ->count();
            })
            ->description('Subjects assigned')
            ->descriptionIcon('heroicon-o-newspaper')
            ->color('primary'),
        ];
    }
}
