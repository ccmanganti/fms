<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Forms;
use Filament\Tables;
use App\Models\User;
use Carbon\Carbon;
use App\Models\SchoolYear;
use App\Models\SubjectLoad;
use App\Models\Student;
use App\Models\Role;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\Philprovince;
use App\Models\Philmuni;
use App\Models\Philbrgy;
use Illuminate\Support\Str;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Layout\Split;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\EClassRecordResource\Pages;
use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\Actions\ImportField;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\EClassRecordResource\RelationManagers;

class EClassRecordResource extends Resource
{
    protected static ?int $navigationSort = 6;

    protected static ?string $model = SubjectLoad::class;
    protected static ?string $navigationLabel = 'E-Class Records';
    protected static function shouldRegisterNavigation(): bool
    {
        if(auth()->user()->hasRole("Subject Teacher")){
            return true;
        } else{
            return false;
        }
    }
    protected static ?string $navigationGroup = 'Class Subject';
    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $slug = 'e-class-records';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Select::make('school_year_id')
                ->label('School Year')
                ->required()
                ->disabled()
                ->reactive()
                ->default(SchoolYear::where('current', 1)->first()->id)
                ->options(SchoolYear::all()->pluck('sy', 'id')),
            Select::make('teacher_id')
                ->label('Subject Teacher')
                ->required()
                ->disabled()
                ->options(User::role('Subject Teacher')->pluck('name', 'id')),
            Select::make('class_id')
                ->label('Class')
                ->required()
                ->disabled()
                ->reactive()
                ->options(function (callable $get){
                    $sy = SchoolYear::where('id',$get('school_year_id'))->first()->id;
                    return Classes::where('school_year_id', $get('school_year_id'))->pluck('name', 'id');
                }),
            Select::make('subject_id')
                ->label('Subject')
                ->required()
                ->disabled()
                ->reactive()
                ->options(function(callable $get, $record){
                    if($record){
                        $existingSubjects = SubjectLoad::where('class_id', $get('class_id'))->where('school_year_id', $get('school_year_id'))
                        ->pluck('subject_id')
                        ->toArray();
                        $availableSubjects = Subject::whereNotIn('id', $existingSubjects)
                            ->pluck('subject_name', 'id');
                        $availableSubjects[Subject::where('id',$record->subject_id)->first()->id] = Subject::where('id',$record->subject_id)->first()->subject_name;
                        
                        return $availableSubjects;    
                    }
                    $existingSubjects = SubjectLoad::where('class_id', $get('class_id'))->where('school_year_id', $get('school_year_id'))
                        ->pluck('subject_id')
                        ->toArray();
                    $availableSubjects = Subject::whereNotIn('id', $existingSubjects)
                        ->pluck('subject_name', 'id');
                    return $availableSubjects;
                }),
                Section::make('Student Grades')->schema([
                    Repeater::make('student_grades')
                        ->schema([
                            Select::make('name')
                            ->reactive()
                            ->disabled()
                            ->columnSpan(3)
                            ->options(function (callable $get) {
                                if(!$get('../../class_id')){
                                    return [];
                                }
                                $students = Student::whereIn('lrn', Classes::where('id', $get('../../class_id'))->first()->students)->get();
                            
                                return $students->pluck('lname', 'lrn')->map(function ($lname, $lrn) use ($students) {
                                    $student = $students->where('lrn', $lrn)->first();
                                    $fname = $student->fname;
                                    $mname = $student->mname;
                                    $fullName = $lname.', '.$fname . ' ' . $mname;
                                    
                                    return $fullName;
                                });
                            }),
                            TextInput::make('1st_quarter_grade')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(function (Closure $set, $get) {
                                if($get('2nd_quarter_grade')){
                                    $average = ($get('1st_quarter_grade') + $get('2nd_quarter_grade'))/2;
                                    $set('average', $average);
                                    if($average > 75){
                                        $set('remarks', 'Passed');
                                    } else{
                                        $set('remarks', 'Failed');
                                    }
                                    if($average > 89){
                                        $set('description', 'Outstanding');
                                    } else if ($average > 84){
                                        $set('description', 'Very Satisfactory');
                                    } else if ($average > 79){
                                        $set('description', 'Satisfactory');
                                    } else if ($average > 74){
                                        $set('description', 'Fairly Satisfactory');
                                    } else{
                                        $set('description', 'Did Not Meet Expectations');
                                    }
                                }
                                
                            }),
                            TextInput::make('2nd_quarter_grade')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(function (Closure $set, $get) {
                                $average = ((int)$get('1st_quarter_grade') + (int)$get('2nd_quarter_grade'))/2;
                                $set('average', $average);
                                if($average > 75){
                                    $set('remarks', 'Passed');
                                } else{
                                    $set('remarks', 'Failed');
                                }
                                if($average > 89){
                                    $set('description', 'Outstanding');
                                } else if ($average > 84){
                                    $set('description', 'Very Satisfactory');
                                } else if ($average > 79){
                                    $set('description', 'Satisfactory');
                                } else if ($average > 74){
                                    $set('description', 'Fairly Satisfactory');
                                } else{
                                    $set('description', 'Did Not Meet Expectations');
                                }
                            }),
                            TextInput::make('average')
                            ->reactive()
                            ->disabled(),
                            Select::make('remarks')
                            ->disabled()
                            ->columnSpan(1)
                            ->options([
                                'Passed' => 'Passed',
                                'Failed' => 'Failed',
                            ]),
                            Select::make('description')
                            ->disabled()
                            ->columnSpan(2)
                            ->options([
                                'Outstanding' => 'Outstanding',
                                'Very Satisfactory' => 'Very Satisfactory',
                                'Satisfactory' => 'Satisfactory',
                                'Fairly Satisfactory' => 'Fairly Satisfactory',
                                'Did Not Meet Expectations' => 'Did Not Meet Expectations',
                            ]),

                        ])
                        ->disableItemCreation()
                        ->disableItemDeletion()
                        ->columns(3)
                        ->columnSpan(2)
                ])->collapsed()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school_year_id')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($record) => SchoolYear::where('id',$record->school_year_id)->first()->sy)
                    ->label("School Year"),
                TextColumn::make('class_id')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label("Class/Section")
                    ->formatStateUsing(fn ($record) => Classes::where('id',$record->class_id)->first()->name),
                TextColumn::make('subject_id')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label("Subject")
                    ->formatStateUsing(fn ($record) => Subject::where('id',$record->subject_id)->first()->subject_name),
                TextColumn::make('semester')
                    ->searchable()
                    ->sortable()
                    // ->toggleable(isToggledHiddenByDefault: true)
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
                Tables\Actions\EditAction::make()->label('Input Grades'),
                Action::make(auth()->user()->hasRole('Superadmin') ? 'export_class_record' : 'export')
                ->url(fn (SubjectLoad $record): string => ('/e-class-records/'.$record->id.'/export')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListEClassRecords::route('/'),
            // 'create' => Pages\CreateEClassRecord::route('/create'),
            // 'edit' => Pages\EditEClassRecord::route('/{record}/edit'),
            'export' => Pages\ExportEClass::route('/{record}/export'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    { 
        if (auth()->user()->hasRole('Superadmin')) {
            return parent::getEloquentQuery()->where('teacher_id', '!=', '');
        } elseif (auth()->user()->hasRole('Subject Teacher')) {
            $teacherId = auth()->user()->id;

            // Get the ID of the current school year
            $currentSchoolYearId = SchoolYear::where('current', true)->value('id');

            // Get the student LRNs of the classes owned by the current adviser
            return parent::getEloquentQuery()->where('teacher_id', $teacherId)->where('school_year_id', $currentSchoolYearId);
        }

        return parent::getEloquentQuery();
    }
}
