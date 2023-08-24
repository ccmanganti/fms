<?php

namespace App\Filament\Resources\SubjectResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\SchoolYear;
use App\Models\Subject;

class SubjectOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total', Subject::where('subject_type', 'Core')->count())
            ->description('Core Subjects')
            ->descriptionIcon('heroicon-s-bookmark')
            ->color('warning'),
            Card::make('Total', Subject::where('subject_type', 'Applied')->count())
            ->description('Applied Subjects')
            ->descriptionIcon('heroicon-s-briefcase')
            ->color('success'),
            Card::make('Total', Subject::where('subject_type', 'Specialized')->count())
            ->description('Specialized Subjects')
            ->descriptionIcon('heroicon-s-book-open')
            ->color('secondary'),
        ];
    }
}
