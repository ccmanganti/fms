<?php

namespace App\Filament\Resources\ClassesResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\SchoolYear;
use App\Models\Classes;

class ClassesOverview extends BaseWidget
{
    protected function getCards(): array
    {
        if(!SchoolYear::where('current', 1)->first()){
            return [];
        }
        return [
            Card::make('School Year', SchoolYear::where('current', 1)->first()->sy)
            ->description('Current School Year')
            ->descriptionIcon('heroicon-s-calendar')
            ->color('warning'),
            Card::make('Total', Classes::where('school_year_id', SchoolYear::where('current', 1)->first()->id)->count())
            ->description('Classes for this S.Y.')
            ->descriptionIcon('heroicon-o-calendar')
            ->color('success'),
        ];
    }
}
