<?php

namespace App\Filament\Widgets;

use Closure;
use App\Models\User;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class Teachers extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Superadmin') || auth()->user()->hasRole('Principal');
    }

    protected function getTableQuery(): Builder
    {
        return User::query()
        ->whereHas('roles', function ($query) {
            $query->whereIn('name', ['Adviser', 'Subject Teacher']);
        })
        ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->searchable()->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('roles.name')
                    ->label("Teacher Role")
                    ->searchable()->sortable()->toggleable()->color(function(User $record){
                        if($record->hasRole('Adviser') && !$record->hasRole('Subject Teacher')){
                            return "success";
                        } else if($record->hasRole('Subject Teacher') && !$record->hasRole('Adviser')){
                            return "primary";
                        } else if($record->hasRole('Adviser') && $record->hasRole('Subject Teacher')){
                            return "warning";
                        }
                    })->icon('heroicon-s-user')->size('lg'),
        ];
    }
}
