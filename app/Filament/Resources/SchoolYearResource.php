<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Forms;
use Filament\Tables;
use App\Models\User;
use Carbon\Carbon;
use App\Models\SchoolYear;
use App\Models\Role;
use Illuminate\Support\Str;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Layout\Split;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\SchoolYearResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\SchoolYearResource\RelationManagers;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;

class SchoolYearResource extends Resource
{
    protected static ?int $navigationSort = 2;

    protected static ?string $model = SchoolYear::class;

    protected static ?string $navigationGroup = 'School Archive';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('sydate')->format('Y')->displayFormat('Y')
                ->label("Start of School Year")
                ->minDate(now()->subYears(2))
                ->maxDate(now()->subYears(-2))
                ->required()
                ->reactive()
                ->unique(ignorable: fn ($record) => $record)
                ->afterStateUpdated(function (Closure $set, $state) {
                    $date = Carbon::parse($state);
                    $year = $date->year;
                    $set('sy', 'S.Y. '.$year.' - '.$year+1);
                }),
                TextInput::make('sy')
                ->label("School Year Name")
                ->placeholder("This field is auto generated")
                ->required()
                ->unique(ignorable: fn ($record) => $record)
                ->disabled(),
                DatePicker::make('completion')
                ->label("Completion Date")
                ->minDate(now()->subYears(2))
                ->required()
                ->reactive()
                ->unique(ignorable: fn ($record) => $record)
                ->columnSpan(2),
                TextInput::make('principal')
                ->label("Principal's Name")
                ->required(),
                Select::make('position')
                ->label("Position")
                ->options([
                    'I' => 'Principal I',
                    'II' => 'Principal II',
                    'III' => 'Principal III',
                    'IV' => 'Principal IV',
                ]),
                FileUpload::make('signature')
                ->label("Principal's Signature")
                ->image(),
                TextInput::make('sds')
                ->label("School Division Superintendent's Name")
                ->required(),
                FileUpload::make('signature_sds')
                ->label("School Division Superintendent's Signature")
                ->image()
                ->columnSpan(2),
                Toggle::make('current')
                ->columnSpan(2)
                ->label('Current school year'),
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
                TextColumn::make('sy')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label("School Year"),
                TextColumn::make('created_at')
                    ->searchable()
                    ->date()
                    ->sortable()
                    ->toggleable(),
                ToggleColumn::make('current')
                    ->searchable()
                    ->sortable()
                    ->disabled(function(){
                        if(auth()->user()->hasRole("Principal") && !auth()->user()->hasRole("Superadmin")){
                            return true;
                        }
                        return false;
                    })
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->searchable()
                    ->since()
                    ->sortable()
                    ->toggleable(),
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
            'index' => Pages\ListSchoolYears::route('/'),
            // 'create' => Pages\CreateSchoolYear::route('/create'),
            // 'edit' => Pages\EditSchoolYear::route('/{record}/edit'),
        ];
    }
}
