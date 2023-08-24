<?php

namespace App\Filament\Resources\UserResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;


class UserStatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            // Card::make('Total', User::count())
            // ->description('Registered Users')
            // ->descriptionIcon('heroicon-s-user-group')
            // ->chart([7, 2, 10, 3, 15, 4, 17])
            // ->color('secondary'),
            // Card::make('Superadmins', ((User::whereHas('roles', function ($query) {
            //     $query->where('name', 'Superadmin');
            // })->count()/User::count())*100).'%')
            // ->description('of users registered')
            // ->descriptionIcon('heroicon-s-users')
            // ->color('danger'),
            // Card::make('Advisers', ((User::whereHas('roles', function ($query) {
            //     $query->where('name', 'Adviser');
            // })->count()/User::count())*100).'%')
            // ->description('of users registered')
            // ->descriptionIcon('heroicon-s-users')
            // ->color('warning'),
            // Card::make('Subject Teachers', ((User::whereHas('roles', function ($query) {
            //     $query->where('name', 'Subject Teacher');
            // })->count()/User::count())*100).'%')
            // ->description('of users registered')
            // ->descriptionIcon('heroicon-s-users')
            // ->color('success'),
            Card::make('Total', User::count())
            ->description('Registered Users')
            ->descriptionIcon('heroicon-s-user-group')
            ->chart([7, 2, 10, 3, 15, 4, 17])
            ->color('secondary'),
            Card::make('Superadmins', User::whereHas('roles', function ($query) {
                $query->where('name', 'Superadmin');
            })->count())
            ->description('of users registered')
            ->descriptionIcon('heroicon-s-users')
            ->color('danger'),
            Card::make('Advisers', User::whereHas('roles', function ($query) {
                $query->where('name', 'Adviser');
            })->count())
            ->description('of users registered')
            ->descriptionIcon('heroicon-s-users')
            ->color('warning'),
            Card::make('Subject Teachers', User::whereHas('roles', function ($query) {
                $query->where('name', 'Subject Teacher');
            })->count())
            ->description('of users registered')
            ->descriptionIcon('heroicon-s-users')
            ->color('success'),
        ];
    }
}
