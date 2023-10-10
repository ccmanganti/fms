<?php

namespace App\Filament\Resources;


use Closure;
use DateTime;
use Filament\Forms;
use Filament\Tables;
use App\Models\User;
use Carbon\Carbon;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentOfClass;
use App\Models\Role;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\SubjectLoad;
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
use App\Filament\Resources\MyClassResource\Pages;
use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\Actions\ImportField;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\MyClassResource\RelationManagers;

class MyClassResource extends Resource
{
    protected static ?int $navigationSort = 5;

    protected static ?string $model = StudentOfClass::class;

    protected static ?string $slug = 'my-class';
    protected static ?string $navigationLabel = 'My Class';

    protected static function shouldRegisterNavigation(): bool
    {
        if(auth()->user()->hasRole("Adviser")){
            return true;
        } else{
            return false;
        }
    }

    protected static function getNavigationGroup(): string
    {
        $isAdviser = auth()->user()->hasRole('Adviser');

        // Set the navigation label based on the user's role
        switch ($isAdviser) {
            case 1:
                return 'Class Management';
            case 0:
                return 'School Archive';
            default:
                return 'School Archive';
        }
    }

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('I. Basic Student Information')->schema([
                    TextInput::make('lrn')
                        ->required()
                        ->numeric(),
                    TextInput::make('lname')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('fname')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('mname')
                        ->required()
                        ->maxLength(255),
                    Select::make('gender')
                        ->required()
                        ->options([
                            'M' => 'Male',
                            'F' => 'Female',
                    ]),
                    DatePicker::make('date_of_birth')->format('d/mY')
                        ->minDate(now()->subYears(50))
                        ->maxDate(now()->subYears(8))
                        ->default(now()->subYears(8))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (Closure $set, $record, callable $get) {
                            $birthdateDate = date_create_from_format('Y-m-d H:i:s', $get('date_of_birth'));
                            // if ($birthdateDate === false) {
                            //     $set('age', 0); // Set a default age value
                            //     return;
                            // }
                            $currentDate = new DateTime();
                            $age = $birthdateDate->diff($currentDate)->y;
                            $set('age', $age);
                        }),
                    TextInput::make('age')
                        ->required()
                        ->disabled()
                        ->numeric(),
                    Select::make('religion')
                        ->options([
                            'Roman Catholic' => 'Roman Catholic',
                            'Protestant' => 'Protestant',
                            'INC' => 'INC',
                            'Aglipay' => 'Aglipay',
                            'Islam' => 'Islam',
                            'Hinduism' => 'Hinduism',
                            'Jehova\'s Witnesses' => 'Jehova\'s-Witnesses',
                            'Adventist' => 'Adventist',
                            'Christian' => 'Christian',
                            'Other Christian' => 'Other Christian',
                            'Others' => 'Others'
                        ]),
                ])
                ->columns(2),
                
                Section::make('II. Address')->schema([
                    TextInput::make('no_street_purok')
                        ->maxLength(255),
                    Select::make('province')
                        ->reactive()
                        ->label('Province Name')
                        ->options(function(){
                            return Philprovince::all()->pluck('provDesc', 'provDesc');
                        }),
                    Select::make('municipality')
                    ->reactive()
                    ->label('City/Municipality Name')
                    ->options(function(callable $get) {
                        $provCode = optional(Philprovince::where('provDesc', $get('province'))->first());
                        return Philmuni::where('provCode', '=', $provCode->provCode ?? '')->pluck('citymunDesc', 'citymunDesc');
                    })
                    ->disabled(fn (Closure $get) => $get('province') == null),
                    Select::make('barangay')
                    ->label('Barangay Name')
                    ->options(function(callable $get) {
                        $provCode = optional(Philprovince::where('provDesc', $get('province'))->first());
                        $muniCode = optional(Philmuni::where('provCode', '=', $provCode->provCode ?? '')->where('citymunDesc', $get('municipality'))->first());
                        return Philbrgy::where('citymunCode', '=', $muniCode->citymunCode ?? '')->pluck('brgyDesc', 'brgyDesc');
                    })
                    ->disabled(fn (Closure $get) => $get('municipality') == null),
                ]),

                Section::make('III. Parental and Guardian Information')->schema([
                    TextInput::make('mother_name')
                        ->maxLength(255),
                    TextInput::make('father_name')
                        ->maxLength(255),
                    TextInput::make('guardian')
                        ->maxLength(255),
                    Select::make('relationship')
                        ->options([
                            'FATHER' => 'Father',
                            'MOTHER' => 'Mother',
                            'STEP FATHER' => 'Step Father',
                            'STEP MOTHER' => 'Step Mother',
                            'RELATIVE' => 'Relative',
                            'OTHERS' => 'Others'
                        ]),
                ])->columns(2),

                Section::make('IV. Additional Information')->schema([
                    TextInput::make('contact_number'),
                    Select::make('modality')
                        ->options([
                            'Face to Face' => 'Face to Face',
                            'Distance Learning' => 'Distance Learning',
                        ]),
                    Select::make('student_type')
                        ->options([
                            'REGULAR' => 'Regular',
                            'IRREGULAR' => 'Irregular',
                        ]),
                    TextInput::make('remarks'),
                    ]),
                    Section::make('V. Student Semestral Status')->schema([
                        Toggle::make('sem_1_status')
                        ->reactive()
                        ->label('Drop - 1st Semester')
                        ->default(0)
                        ->afterStateUpdated(function (Closure $set, callable $get) {
                            if($get('sem_1_status') == true){
                                $set('sem_2_status', true);
                            }
                            else{
                                $set('sem_2_status', false);
                            }
                        }),
                        Toggle::make('sem_2_status')
                        ->reactive()
                        ->default(0)
                        ->label('Drop - 2nd Semester')
                        ->disabled(function (callable $get) {
                            if($get('sem_1_status') == true){
                                return true;
                            }
                            return false;
                        })
                    ])
                    ->hidden(fn() => auth()->user()->hasRole("Superadmin"))
                    ->columns(2)
                    ->description("Set student's semestral status. If dropped on first semester, student will automatically be dropped on second semester."),
                


            ]);
    }

    public static function table(Table $table): Table
    {
        $bulkActions = [];
        if (auth()->user()->hasRole('Superadmin')) {
            $bulkActions[] = DeleteBulkAction::make();
        }
        $currentSchoolYearId = SchoolYear::where('current', true)->value('id');
        $class = Classes::where('adviser_id', auth()->user()->id)
            ->where('school_year_id', $currentSchoolYearId)->first();
        $textColumnArrays = [];
        if($class){
            $subjects = SubjectLoad::where('class_id', $class->id)->get();

            $textColumnArrays[] = TextColumn::make('semester1')->label('SEMESTER')->toggleable()
            ->color('primary')
            ->formatStateUsing(function($record){
                return '1st Semester';
            });
            foreach ($subjects as $subject) {
                if(Subject::where('id', $subject->subject_id)->first()->semester == '1'){
                    $textColumnArrays[] = TextColumn::make($subject->subject_id.'name')->label('Subject Name')->toggleable()
                    ->color('success')
                    ->formatStateUsing(function($record) use ($subject){
                        return Subject::where('id', $subject->subject_id)->first()->subject_name;
                    });
                    $textColumnArrays[] = TextColumn::make($subject->subject_id.'1')->label('1st Quarter')->toggleable()
                    ->formatStateUsing(function($record) use ($subject){
                        $grade = collect($subject->student_grades)->where('name', $record->lrn)->first();
                        return $grade['1st_quarter_grade'] ?? 'No Data';
                    });
                    $textColumnArrays[] = TextColumn::make($subject->subject_id.'2')->label('2nd Quarter')->toggleable()
                    ->formatStateUsing(function($record) use ($subject){
                        $grade = collect($subject->student_grades)->where('name', $record->lrn)->first();
                        return $grade['2nd_quarter_grade'] ?? 'No Data';
                    });
                    $textColumnArrays[] = TextColumn::make($subject->subject_id.'3')->label('Average')->toggleable()
                    ->formatStateUsing(function($record) use ($subject){
                        $grade = collect($subject->student_grades)->where('name', $record->lrn)->first();
                        return $grade['average'] ?? 'No Data';
                    });
                }
            }
            $textColumnArrays[] = TextColumn::make('semester2')->label('SEMESTER')->toggleable()
            ->color('primary')
            ->formatStateUsing(function($record){
                return '2nd Semester';
            });
            foreach ($subjects as $subject) {
                if(Subject::where('id', $subject->subject_id)->first()->semester == '2'){
                    $textColumnArrays[] = TextColumn::make($subject->subject_id.'name')->label('Subject Name')->toggleable()
                    ->color('success')
                    ->formatStateUsing(function($record) use ($subject){
                        return Subject::where('id', $subject->subject_id)->first()->subject_name;
                    });
                    $textColumnArrays[] = TextColumn::make($subject->subject_id.'1')->label('1st Quarter')->toggleable()
                    ->formatStateUsing(function($record) use ($subject){
                        $grade = collect($subject->student_grades)->where('name', $record->lrn)->first();
                        return $grade['1st_quarter_grade'] ?? 'No Data';
                    });
                    $textColumnArrays[] = TextColumn::make($subject->subject_id.'2')->label('2nd Quarter')->toggleable()
                    ->formatStateUsing(function($record) use ($subject){
                        $grade = collect($subject->student_grades)->where('name', $record->lrn)->first();
                        return $grade['2nd_quarter_grade'] ?? 'No Data';
                    });
                    $textColumnArrays[] = TextColumn::make($subject->subject_id.'3')->label('Average')->toggleable()
                    ->formatStateUsing(function($record) use ($subject){
                        $grade = collect($subject->student_grades)->where('name', $record->lrn)->first();
                        return $grade['average'] ?? 'No Data';
                    });
                }
            }
        }
        
        
        
        return $table
            ->columns([
                TextColumn::make('lrn')->label("LRN")->searchable()->sortable()->toggleable(),
                TextColumn::make('student_name')->label("Name")->searchable()->sortable()->toggleable()
                ->icon('heroicon-s-user')
                ->formatStateUsing(fn ($record) => $record->lname.', '.$record->fname.', '.$record->mname),
                TextColumn::make('gender')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                ...$textColumnArrays,
                TextColumn::make('REMARKS')->toggleable()
                ->formatStateUsing(function($record){
                    
                }),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Action::make('export_sf9')
                    ->icon('heroicon-o-newspaper')
                    ->label('SF9.XLSX')
                    ->hidden(function(){
                        if(auth()->user()->hasRole('Subject Teacher') && !auth()->user()->hasRole('Adviser')){
                            return true;
                        }
                    })
                    ->url(fn (StudentOfClass $record): string => ('/'.$record->id.'/export-sf9/')),
                    Action::make('export_sf9_pdf')
                    ->icon('heroicon-o-printer')
                    ->label('SF9.PDF')
                    ->hidden(function(){
                        if(auth()->user()->hasRole('Subject Teacher') && !auth()->user()->hasRole('Adviser')){
                            return true;
                        }
                    })
                    ->url(fn (StudentOfClass $record): string => ('/'.$record->id.'/export-sf9-pdf/'))
                    ->openUrlInNewTab(),
                    Action::make('export_sf10')
                    ->icon('heroicon-o-newspaper')
                    ->label('SF10.XLSX')
                    ->hidden(function(){
                        if(auth()->user()->hasRole('Subject Teacher') && !auth()->user()->hasRole('Adviser')){
                            return true;
                        }
                    })
                    ->url(fn (StudentOfClass $record): string => ('/'.$record->id.'/export-sf10/')),
                    Action::make('export_sf10_pdf')
                    ->icon('heroicon-o-printer')
                    ->label('SF10.PDF')
                    ->hidden(function(){
                        if(auth()->user()->hasRole('Subject Teacher') && !auth()->user()->hasRole('Adviser')){
                            return true;
                        }
                    })
                    ->url(fn (StudentOfClass $record): string => ('/'.$record->id.'/export-sf10-pdf/'))
                    ->openUrlInNewTab(),
                    Action::make('export_diploma')
                    ->icon('heroicon-o-printer')
                    ->label('Diploma')
                    ->hidden(function(){
                        if((auth()->user()->hasRole('Subject Teacher') || auth()->user()->hasRole('Superadmin') || auth()->user()->hasRole('Principal')) && !auth()->user()->hasRole('Adviser')){
                            return true;
                        }
                    })
                    ->url(fn (StudentOfClass $record): string => ('/'.$record->id.'/export-diploma-pdf/'))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListMyClasses::route('/'),
            // 'create' => Pages\CreateStudent::route('/create'),
            // 'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }    

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        $adviserId = $user->id;

        // Get the ID of the current school year
        $currentSchoolYearId = SchoolYear::where('current', true)->value('id');
        // Get the student LRNs of the classes owned by the current adviser
        $studentLRNs = Classes::where('adviser_id', $adviserId)
            ->where('school_year_id', $currentSchoolYearId)
            ->pluck('students')
            ->flatten()
            ->toArray();
        return parent::getEloquentQuery()->whereIn('lrn', $studentLRNs)->where('school_year_id', $currentSchoolYearId);

    }
}
