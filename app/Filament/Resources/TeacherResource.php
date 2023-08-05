<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Forms;
use Filament\Tables;
use App\Models\User;
use App\Models\Role;
use App\Models\Subject;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\TeacherResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\TeacherResource\RelationManagers;

class TeacherResource extends Resource
{
    protected static ?int $navigationSort = 4;

    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Teachers';

    protected static ?string $navigationGroup = 'School Archive';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $slug = 'teachers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignorable: fn ($record) => $record),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignorable: fn ($record) => $record),
                TextInput::make('password')
                    ->password()
                    ->maxLength(255)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Select::make('roles')
                    ->preload()
                    ->reactive()
                    ->required()
                    ->multiple(1)
                    ->relationship('roles', 'name')
                    ->options(function() {
                        if (auth()->user()->getRoleNames()->first() === 'Principal' || auth()->user()->getRoleNames()->first() === 'Superadmin') {
                            return Role::where([['name', '!=', 'Superadmin'], ['name', '!=', 'Principal']])->pluck('name', 'id');
                        }
                        
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        $bulkActions = [];
        if (auth()->user()->hasRole('Superadmin')) {
            $bulkActions[] = DeleteBulkAction::make();
        }

        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->toggleable(),
                TextColumn::make('email')->searchable()->sortable()->toggleable()->icon('heroicon-s-inbox')->default('Undefined'),
                TextColumn::make('roles.name')
                    ->label("Teacher Role")
                    ->searchable()->sortable()->toggleable()->color(function(User $record){
                        if($record->hasRole('Superadmin')){
                            return "danger";
                        } else if($record->hasRole('Principal')){
                            return "primary";
                        } else if($record->hasRole('Adviser') || $record->hasRole('Subject Teacher')){
                            return "secondary";
                        }
                    })->icon('heroicon-s-user')->size('lg'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions($bulkActions);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeachers::route('/'),
            // 'create' => Pages\CreateTeacher::route('/create'),
            // 'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('name', '!=', '')
                ->whereHas('roles', function (Builder $query) {
                $query->where('name', '!=', 'Superadmin')
                    ->where('name', '!=', 'Principal');
            });
    }
}
