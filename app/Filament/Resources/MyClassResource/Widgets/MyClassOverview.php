<?php

namespace App\Filament\Resources\MyClassResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\SchoolYear;
use App\Models\Classes;
use App\Models\StudentOfClass;

class MyClassOverview extends BaseWidget
{
    protected function getCards(): array
    {
        if(!SchoolYear::where('current', 1)->first()){
            return [];
        }
        return [
            Card::make('Total Students', Classes::where('adviser_id', auth()->user()->id)
            ->where('school_year_id', SchoolYear::where('current', 1)->first()->id)
            ->pluck('students')
            ->flatMap(function ($students) {
                return $students;
            })
            ->count())
            ->description('My Class')
            ->descriptionIcon('heroicon-s-user-group')
            ->color('success'),
            Card::make('Gender', function(){
                $currentSchoolYearId = SchoolYear::where('current', true)->value('id');
                // Get the student LRNs of the classes owned by the current adviser
                $studentLRNs = Classes::where('adviser_id', auth()->user()->id)
                    ->where('school_year_id', $currentSchoolYearId)
                    ->pluck('students')
                    ->flatten()
                    ->toArray();
                return StudentOfClass::whereIn('lrn', $studentLRNs)->where('school_year_id', $currentSchoolYearId)->where('gender', 'M')->count().':'.StudentOfClass::whereIn('lrn', $studentLRNs)->where('school_year_id', $currentSchoolYearId)->where('gender', 'F')->count();
            })
            ->description('Male to Female Ratio')
            ->descriptionIcon('heroicon-o-calendar')
            ->color('primary'),
        ];
    }
}
