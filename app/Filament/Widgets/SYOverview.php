<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\SchoolYear;
use App\Models\Classes;
use App\Models\SubjectLoad;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class SYOverview extends BaseWidget
{
    protected function getCards(): array
    {   
        if(!SchoolYear::where('current', 1)->first()){
            return [];
        }
        if(auth()->user()->hasRole('Superadmin') || auth()->user()->hasRole('Principal')){
            return [
                Card::make('School Year', SchoolYear::where('current', 1)->first()->sy)
                ->description('Current School Year')
                ->descriptionIcon('heroicon-s-calendar')
                ->color('warning'),
                Card::make('Total', Classes::where('school_year_id', SchoolYear::where('current', 1)->first()->id)->count())
                ->description('Classes for this S.Y.')
                ->descriptionIcon('heroicon-s-bookmark')
                ->color('warning'),
                Card::make('Total', Student::count())
                ->description('Registered students in the system')
                ->descriptionIcon('heroicon-s-user-group')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('secondary'),
            ];
        } else if(auth()->user()->hasRole('Adviser') && !auth()->user()->hasRole('Subject Teacher')){
            return [
                Card::make('School Year', SchoolYear::where('current', 1)->first()->sy)
                ->description('Current School Year')
                ->descriptionIcon('heroicon-s-calendar')
                ->color('warning'),
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
            ];
        } else if(auth()->user()->hasRole('Subject Teacher') && !auth()->user()->hasRole('Adviser')){
            return [
                Card::make('School Year', SchoolYear::where('current', 1)->first()->sy)
                ->description('Current School Year')
                ->descriptionIcon('heroicon-s-calendar')
                ->color('warning'),
                Card::make('Total Subject Loads', function(){
                    return SubjectLoad::where('teacher_id', auth()->user()->id)
                    ->where('school_year_id', SchoolYear::where('current', 1)->first()->id)
                    ->count();
                })
                ->description('For this S.Y.')
                ->descriptionIcon('heroicon-s-bookmark')
                ->color('success'),
            ];
        } else if(auth()->user()->hasRole('Adviser') && auth()->user()->hasRole('Subject Teacher')){
            return [
                Card::make('School Year', SchoolYear::where('current', 1)->first()->sy)
                ->description('Current School Year')
                ->descriptionIcon('heroicon-s-calendar')
                ->color('warning'),
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
                Card::make('Total Subject Loads', function(){
                    return SubjectLoad::where('teacher_id', auth()->user()->id)
                    ->where('school_year_id', SchoolYear::where('current', 1)->first()->id)
                    ->count();
                })
                ->description('For this S.Y.')
                ->descriptionIcon('heroicon-s-bookmark')
                ->color('success'),
            ];
        }
        else {
            return [];
        }

        
        
    }
}
