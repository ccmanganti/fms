<?php

namespace App\Filament\Resources\SchoolYearResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\SchoolYear;


class SchoolYearOverview extends BaseWidget
{
    protected function getCards(): array
    {
        if(!SchoolYear::where('current', 1)->first()){
            return [];
        }
        return [
            Card::make('School Year', SchoolYear::where('current', 1)->first()->sy)
            ->description('Registered as System Environment')
            ->descriptionIcon('heroicon-s-calendar')
            ->color('warning'),
            Card::make('Total', SchoolYear::count())
            ->description('School Years previously registered')
            ->descriptionIcon('heroicon-o-calendar')
            ->color('success'),
        ];
    }
}
