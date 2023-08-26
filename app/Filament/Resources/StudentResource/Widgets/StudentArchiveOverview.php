<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\Student;
use App\Models\Classes;
use Illuminate\Support\Facades\DB;

class StudentArchiveOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total', Student::count())
            ->description('Registered students in the system')
            ->descriptionIcon('heroicon-s-user-group')
            ->chart([7, 2, 10, 3, 15, 4, 17])
            ->color('secondary'),
            Card::make('Total', function(){
                $classes = Classes::all(); // Retrieve all classes
                $studentLrns = $classes->pluck('students')->flatten()->unique()->toArray();
                $registeredStudentCount = DB::table('students')
                    ->whereIn('lrn', $studentLrns)
                    ->count();
                return $registeredStudentCount;
            })
            ->description('Student currently enrolled')
            ->descriptionIcon('heroicon-s-users')
            ->color('success'),
            Card::make('Total', function(){
                $classes = Classes::all(); // Retrieve all classes
                $studentLrns = $classes->pluck('students')->flatten()->unique()->toArray();
                $registeredStudentCount = DB::table('students')
                    ->whereIn('lrn', $studentLrns)
                    ->count();

                return (Student::count())-$registeredStudentCount;
            })
            ->description('Not enrolled')
            ->descriptionIcon('heroicon-s-users')
            ->color('danger'),
        ];
    }
}
