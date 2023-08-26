<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\SchoolYear;
use App\Models\Classes;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class SYOverview extends BaseWidget
{
    protected function getCards(): array
    {
        if(auth()->user()->hasRole('Superadmin')){
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
        }
        return [];
        
    }
}
