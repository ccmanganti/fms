<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\Student;


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
            Card::make('Total', User::whereHas('roles', function ($query) {
                $query->where('name', 'Superadmin');
            })->count())
            ->description('Student currently enrolled')
            ->descriptionIcon('heroicon-s-users')
            ->color('success'),
            Card::make('Total', User::whereHas('roles', function ($query) {
                $query->where('name', 'Adviser');
            })->count())
            ->description('Not enrolled')
            ->descriptionIcon('heroicon-s-users')
            ->color('danger'),
        ];
    }
}
