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
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\SubjectResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\SubjectResource\RelationManagers;

class SubjectResource extends Resource
{
    protected static ?int $navigationSort = 3;

    protected static ?string $model = Subject::class;

    protected static ?string $navigationGroup = 'School Archive';
    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('subject_name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignorable: fn ($record) => $record),
                Select::make('subject_type')
                    ->required()
                    ->options([
                        'Core' => 'Core',
                        'Applied' => 'Applied',
                        'Specialized' => 'Specialized',
                ]),
                Select::make('semester')
                    ->required()
                    ->options([
                        '1' => '1st Semester',
                        '2' => '2nd Semester',
                ]),
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
                TextColumn::make('subject_name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('subject_type')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('semester')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($record){
                        if($record->semester == 1){
                            return "1st Semester";
                        } else {
                            return "2nd Semester";
                        }
                    }),
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
            'index' => Pages\ListSubjects::route('/'),
            // 'create' => Pages\CreateSubject::route('/create'),
            // 'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }    
}
