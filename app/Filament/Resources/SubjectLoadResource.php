<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Forms;
use Filament\Tables;
use App\Models\User;
use App\Models\Student;
use App\Models\Classes;
use App\Models\SubjectLoad;
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
use App\Filament\Resources\SubjectLoadResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\SubjectLoadResource\RelationManagers;


class SubjectLoadResource extends Resource
{
    protected static ?int $navigationSort = 7;

    protected static ?string $model = SubjectLoad::class;

    protected static ?string $navigationLabel = 'Subject Loads';

    protected static ?string $navigationGroup = 'School Year Records';
    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static function shouldRegisterNavigation(): bool
    {
        if(auth()->user()->hasRole("Superadmin") || auth()->user()->hasRole("Principal")){
            return true;
        } else{
            return false;
        }
    }

    protected static ?string $slug = 'subject-load';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('school_year_id')
                    ->label('School Year')
                    ->required()
                    ->reactive()
                    ->default(function(){
                        return SchoolYear::where('current', 1)->first()->id ?? null;
                    })
                    ->options(SchoolYear::all()->pluck('sy', 'id')),
                Select::make('teacher_id')
                    ->label('Subject Teacher')
                    ->required()
                    ->options(User::role('Subject Teacher')->pluck('name', 'id')),
                Select::make('class_id')
                    ->label('Class')
                    ->required()
                    ->reactive()
                    ->options(function (callable $get){
                        // $sy = SchoolYear::where('id',$get('school_year_id'))->first()->id;
                        return Classes::where('school_year_id', $get('school_year_id'))->pluck('name', 'id');
                    }),
                Select::make('subject_id')
                    ->label('Subject')
                    ->required()
                    ->reactive()
                    ->disabled(fn (Closure $get) => $get('class_id') == null)
                    ->options(function(callable $get, $record){
                        if($get('class_id')){
                            $subjectsForClass = Classes::where('id', $get('class_id'))->first()->subjects;
                            if($record){
                                $existingSubjects = SubjectLoad::where('class_id', $get('class_id'))->where('school_year_id', $get('school_year_id'))
                                ->pluck('subject_id')
                                ->toArray();
                                $availableSubjects = Subject::whereNotIn('id', $existingSubjects)->whereIn('id', $subjectsForClass)
                                    ->pluck('subject_name', 'id');
                                $availableSubjects[Subject::where('id', $record->subject_id)->first()->id] = Subject::where('id', $record->subject_id)->first()->subject_name;                            
                                return $availableSubjects;    
                            }
                            $existingSubjects = SubjectLoad::where('class_id', $get('class_id'))->where('school_year_id', $get('school_year_id'))
                                ->pluck('subject_id')
                                ->toArray();
                            $availableSubjects = Subject::whereNotIn('id', $existingSubjects)->whereIn('id', $subjectsForClass)
                                ->pluck('subject_name', 'id');
                            return $availableSubjects;
                        }
                        
                    })
                    ->afterStateUpdated(function (Closure $set, $record, callable $get) {
                        if($get('subject_id') != null){
                            $subject = $get('subject_id');
                            $semester = Subject::where('id', $subject)->first()->semester;
                            $set('semester', $semester);
                        }
                    }),
                Select::make('semester')
                ->disabled()
                ->columnSpan(2)
                ->options([
                    '1' => '1st Semester',
                    '2' => '2nd Semester',
                ])->placeholder("This field is auto-generated"),
                
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
                TextColumn::make('school_year_id')->searchable()->sortable()->toggleable()
                ->formatStateUsing(fn ($record) => SchoolYear::where('id',$record->school_year_id)->first()->sy),
                TextColumn::make('teacher_id')->searchable()->sortable()->toggleable()->label('Subject Teacher')
                ->formatStateUsing(fn ($record) => User::where('id',$record->teacher_id)->first()->name),
                TextColumn::make('class_id')->searchable()->sortable()->toggleable()->label('Class')    
                ->formatStateUsing(fn ($record) => Classes::where('id', $record->class_id)->first()->name),
                TextColumn::make('subject_id')->searchable()->sortable()->toggleable()
                ->formatStateUsing(fn ($record) => Subject::where('id',$record->subject_id)->first()->subject_name),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make(auth()->user()->hasRole('Superadmin') ? 'export_class_record' : 'export')
                ->label("ECR.xlsx")
                ->icon('heroicon-o-newspaper')
                ->url(fn (SubjectLoad $record): string => ('/e-class-records/'.$record->id.'/export')),
                // Action::make(auth()->user()->hasRole('Superadmin') ? 'export_class_record-pdf' : 'export-pdf')
                // ->label("ECR.pdf")
                // ->icon('heroicon-o-newspaper')
                // ->url(fn (SubjectLoad $record): string => ('/e-class-records/'.$record->id.'/export-pdf')),
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
            'index' => Pages\ListSubjectLoads::route('/'),
            // 'create' => Pages\CreateSubjectLoad::route('/create'),
            // 'edit' => Pages\EditSubjectLoad::route('/{record}/edit'),
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
