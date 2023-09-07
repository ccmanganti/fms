<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Forms;
use Filament\Tables;
use App\Models\User;
use App\Models\Student;
use App\Models\Classes;
use Carbon\Carbon;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\Role;
use Illuminate\Support\Str;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Illuminate\Validation\Rules\Unique;
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
use App\Filament\Resources\ClassesResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\ClassesResource\RelationManagers;
use Illuminate\Support\Facades\DB;


class ClassesResource extends Resource
{
    
    protected static ?int $navigationSort = 6;

    protected static ?string $model = Classes::class;
    

    protected static ?string $navigationLabel = 'Classes/Sections';

    protected static ?string $navigationGroup = 'School Year Records';
    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('school_year_id')
                    ->label('School Year')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (Closure $set, $record, callable $get) {
                        if($get('track_course') != null){
                            $level = $get('grade_level');
                            $track = $get('track_course');
                            $sy = SchoolYear::where('id',$get('school_year_id'))->first()->sy;
                            $section = $get('section');
                            $set('name', $track.' '.$level.$section.' | '.$sy);
                        }
                        
                    })
                    ->default(function(){
                        return SchoolYear::where('current', 1)->first()->id ?? null;
                    })
                    ->options(SchoolYear::all()->pluck('sy', 'id')),
                DatePicker::make('completion')->format('d/m/Y')->displayFormat('d/m/Y')
                    ->label("Date of Graduation/Completion")
                    ->minDate(now()),
                TextInput::make('name')
                    ->required()
                    ->disabled()
                    ->unique(ignorable: fn ($record) => $record)
                    ->reactive()
                    ->label('Section Name')
                    ->placeholder('This field is auto-generated')
                    // ->unique(ignorable: fn ($record) => $record)
                    ->maxLength(255),
                Select::make('grade_level')
                    ->required()
                    ->afterStateUpdated(function (Closure $set, $record, callable $get) {
                        if($get('track_course') != null){
                            $level = $get('grade_level');
                            $track = $get('track_course');
                            $sy = SchoolYear::where('id',$get('school_year_id'))->first()->sy;
                            $section = $get('section');
                            $set('name', $track.' '.$level.$section.' | '.$sy);
                        }
                        
                    })
                    ->reactive()
                    ->options([
                        '11' => '11',
                        '12' => '12',
                    ]),
                Select::make('section')
                    ->required()
                    ->afterStateUpdated(function (Closure $set, $record, callable $get) {
                        if($get('track_course') != null){
                            $level = $get('grade_level');
                            $track = $get('track_course');
                            $sy = SchoolYear::where('id',$get('school_year_id'))->first()->sy;
                            $section = $get('section');
                            $set('name', $track.' '.$level.$section.' | '.$sy);
                        }
                        
                    })
                    ->reactive()
                    ->disabled(fn (Closure $get) => $get('grade_level') == null)
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'C' => 'C',
                        'D' => 'D',
                        'E' => 'E',
                        'F' => 'F',
                        'G' => 'G',
                        'H' => 'H',
                        'I' => 'I',
                        'J' => 'J',
                        'K' => 'K',
                        'L' => 'L',
                        'M' => 'M',
                        'N' => 'N',
                        'O' => 'O',
                        'P' => 'P',
                        'Q' => 'Q',
                        'R' => 'R',
                        'S' => 'S',
                        'T' => 'T',
                        'U' => 'U',
                        'V' => 'V',
                        'W' => 'W',
                        'X' => 'X',
                        'Y' => 'Y',
                        'Z' => 'Z',
                    ]),
                Select::make('adviser_id')
                    ->label('Adviser')
                    ->required()
                    ->reactive()
                    ->disabled(fn (Closure $get) => $get('section') == null)
                    ->options(function(callable $get, $record){
                        if($record){
                            $existingUserIds = Classes::where('school_year_id', $get('school_year_id'))
                            ->pluck('adviser_id')
                            ->toArray();
                        
                            $availableUsers = User::role('Adviser')->whereNotIn('id', $existingUserIds)->pluck('name', 'id');    
                            $availableUsers[User::where('id',$record->adviser_id)->first()->id] = User::where('id',$record->adviser_id)->first()->name;
                            return $availableUsers;
                        }
                        $existingUserIds = Classes::where('school_year_id', $get('school_year_id'))
                        ->pluck('adviser_id')
                        ->toArray();
                    
                        $availableUsers = User::role('Adviser')->whereNotIn('id', $existingUserIds)->pluck('name', 'id');
                        return $availableUsers;
                        
                        }),
                Select::make('track_course')
                    ->required()
                    ->columnSpan(2)
                    ->disabled(fn (Closure $get) => $get('adviser_id') == null)
                    ->reactive()
                    ->afterStateUpdated(function (Closure $set, $record, callable $get) {
                        $level = $get('grade_level');
                        $track = $get('track_course');
                        $sy = SchoolYear::where('id',$get('school_year_id'))->first()->sy;
                        $section = $get('section');
                        $set('name', $track.' '.$level.$section.' | '.$sy);
                    })
                    ->options([
                        'ABM' => 'Academic Track: ABM',
                        'STEM' => 'Academic Track: STEM',
                        'HUMSS' => 'Academic Track: HUMSS',
                        'GAS' => 'Academic Track: GAS',
                        'ADT' => 'Arts and Design Track',
                        'ST' => 'Sports Track',
                        'TVL - AFA' => 'TVL Track: Agricultural-Fishery Arts (AFA) Strand',
                        'TVL - HE' => 'TVL Track: Home Economics (HE) Strand',
                        'TVL - IA' => 'TVL Track: Industrial Arts (IA) Strand',
                        'TVL - ICT' => 'TVL Track: Information and Communications Technology (ICT) Strand',
                    ]),
                Section::make('Class Subjects')->schema([
                    Select::make('subjects')
                        ->searchable()
                        ->multiple()
                        ->options(Subject::all()->pluck('subject_name', 'id'))
                ]),
                // Section::make('Class Students')->schema([
                //     Select::make('students')
                //         ->searchable()
                //         ->multiple()
                //         ->options(Student::orderBy('lname', 'asc')->get()->map(function ($student) {
                //             // Modify the 'lname' data here
                //             $student['lname'] = $student['lname'].', '.$student['fname'].' '.$student['mname'];
                    
                //             return $student;
                //         })
                //         ->pluck('lname', 'lrn'))
                // ]),
                Section::make('Class Students')->schema([
                    Select::make('students')
                        ->searchable()
                        ->multiple()
                        ->options(function () {
                            // Get the JSON string containing LRNs and decode it into an array
                            $studentsInClassesJson = DB::table('classes')->where('school_year_id', SchoolYear::where('current', 1)->first()->id)->pluck('students')->first();
                            $studentsInClasses = json_decode($studentsInClassesJson);
                
                            // Check if $studentsInClasses is empty or null
                            if (empty($studentsInClasses)) {
                                // If it's empty or null, return all students
                                return Student::orderBy('lname', 'asc')
                                    ->get()
                                    ->map(function ($student) {
                                        // Modify the 'lname' data here
                                        $student['lname'] = $student['lname'] . ', ' . $student['fname'] . ' ' . $student['mname'];
                
                                        return $student;
                                    })
                                    ->pluck('lname', 'lrn');
                            }
                
                            // Fetch students who are not in existing classes
                            $studentsNotInClasses = Student::orderBy('lname', 'asc')
                                ->whereNotIn('lrn', $studentsInClasses)
                                ->get()
                                ->map(function ($student) {
                                    // Modify the 'lname' data here
                                    $student['lname'] = $student['lname'] . ', ' . $student['fname'] . ' ' . $student['mname'];
                
                                    return $student;
                                })
                                ->pluck('lname', 'lrn');
                
                            return $studentsNotInClasses;
                        })
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
                TextColumn::make('school_year_id')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($record) => SchoolYear::where('id',$record->school_year_id)->first()->sy)
                    ->label("School Year"),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label("Section"),
                TextColumn::make('grade_level')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label("Grade Level"),
                TextColumn::make('track_course')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('adviser_id')
                    ->searchable()
                    ->formatStateUsing(fn ($record) => User::where('id',$record->adviser_id)->first()->name)
                    ->label("Adviser")
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('students')
                ->label('Students')
                ->icon('heroicon-o-user-group')
                ->url(fn (Classes $record): string => ('/classes/'.$record->id.'/class')),
                Tables\Actions\ActionGroup::make([
                    Action::make('export_sf1_first_xlsx')
                    ->icon('heroicon-o-newspaper')
                    ->label('SF1 - 1st Sem.xlsx')
                    ->url(fn (Classes $record): string => ('/'.$record->id.'/export-sf1-first')),
                    Action::make('export_sf1_second_xlsx')
                    ->icon('heroicon-o-newspaper')
                    ->label('SF1 - 2nd Sem.xlsx')
                    ->url(fn (Classes $record): string => ('/'.$record->id.'/export-sf1-second')),
                    // Action::make('export_sf1_first_pdf')
                    // ->icon('heroicon-o-newspaper')
                    // ->label('SF1 - 1st Sem.pdf')
                    // ->url(fn (Classes $record): string => ('/'.$record->id.'/export-sf1-first-pdf')),
                    // Action::make('export_sf1_second_pdf')
                    // ->icon('heroicon-o-newspaper')
                    // ->label('SF1 - 2nd Sem.pdf')
                    // ->url(fn (Classes $record): string => ('/'.$record->id.'/export-sf1-second-pdf')),
                ]),
                

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
            'index' => Pages\ListClasses::route('/'),
            // 'create' => Pages\CreateClasses::route('/create'),
            // 'edit' => Pages\EditClasses::route('/{record}/edit'),
            'class' => Pages\ClassesPage::route('/{classid}/class'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    { 
            // Get the ID of the current school year
            $currentSchoolYearId = SchoolYear::where('current', true)->value('id');

            // Get the student LRNs of the classes owned by the current adviser
            return parent::getEloquentQuery()->where('school_year_id', $currentSchoolYearId);
    }
}
