<?php

namespace App\Filament\Resources\TeacherResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\SchoolYear;

class TeacherOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total', User::whereHas('roles', function ($query){
                $query->whereIn('name', ['Adviser', 'Subject Teacher']);
            })->count())
            ->description('Teachers')
            ->descriptionIcon('heroicon-s-user-group')
            ->color('secondary'),
            Card::make('Total', User::whereHas('roles', function ($query){
                $query->whereIn('name', ['Adviser']);
            })->count())
            ->description('Adviser')
            ->descriptionIcon('heroicon-s-users')
            ->color('warning'),
            Card::make('Total', User::whereHas('roles', function ($query){
                $query->whereIn('name', ['Subject Teacher']);
            })->count())
            ->description('Subject Teacher')
            ->descriptionIcon('heroicon-s-users')
            ->color('success'),
        ];
    }
}
