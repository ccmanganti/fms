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
use App\Models\StudentOfCLass;
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
                Section::make('Grading Percentages')->schema([
                    TextInput::make('written')
                        ->numeric()
                        ->afterStateUpdated(function (Closure $set, $get){
                            $write = $get('written');
                            $perf = $get('performance');
                            $quart = $get('quarterly');
                            if($write && $perf && $quart){
                                $total = $write+$perf+$quart;
                                $set('total_percentage', $total);
                            }
                        })
                        ->label("Written Works Percentage")
                        ->reactive(),
                    TextInput::make('performance')
                        ->numeric()
                        ->label("Performance Percentage")
                        ->afterStateUpdated(function (Closure $set, $get){
                            $write = $get('written');
                            $perf = $get('performance');
                            $quart = $get('quarterly');
                            if($write && $perf && $quart){
                                $total = $write+$perf+$quart;
                                $set('total_percentage', $total);
                            }
                        })
                        ->reactive(),
                    TextInput::make('quarterly')
                        ->numeric()
                        ->label("Quarterly Assessment")
                        ->afterStateUpdated(function (Closure $set, $get){
                            $write = $get('written');
                            $perf = $get('performance');
                            $quart = $get('quarterly');
                            if($write && $perf && $quart){
                                $total = $write+$perf+$quart;
                                $set('total_percentage', $total);
                            }
                        })
                        ->reactive(),
                    TextInput::make('total_percentage')
                        ->numeric()
                        ->disabled()
                        ->minValue(100)
                        ->maxValue(100)
                        ->label("Total")
                        ->reactive(),
                ])
                ->description("Register your subject's grading system.")
                ->columns(4)->collapsed(),
                Section::make('Score Totals')->schema([
                    Section::make('1st Quarter')->schema([
                        Section::make('Written Works')->schema([
                            TextInput::make('total_written_work_1_1')
                            ->label('Work 1')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_2_1')
                            ->label('Work 2')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_3_1')
                            ->label('Work 3')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_4_1')
                            ->label('Work 4')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_5_1')
                            ->label('Work 5')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_6_1')
                            ->label('Work 6')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_7_1')
                            ->label('Work 7')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_8_1')
                            ->label('Work 8')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_9_1')
                            ->label('Work 9')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_10_1')
                            ->label('Work 10')
                            ->numeric()
                            ->reactive(),
                        ])
                        ->columns(5),
                        Section::make('Performance Tasks')->schema([
                            TextInput::make('total_performance_task_1_1')
                            ->label('Task 1')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_2_1')
                            ->label('Task 2')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_3_1')
                            ->label('Task 3')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_4_1')
                            ->label('Task 4')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_5_1')
                            ->label('Task 5')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_6_1')
                            ->label('Task 6')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_7_1')
                            ->label('Task 7')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_8_1')
                            ->label('Task 8')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_9_1')
                            ->label('Task 9')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_10_1')
                            ->label('Task 10')
                            ->numeric()
                            ->reactive(),
                        ])
                        ->columns(5),
                        TextInput::make('total_quarterly_exam_1')
                            ->label('Quarterly Exam 1 Total')
                            ->numeric()
                            ->reactive(),
                        
                    ])
                    ->disabled(function(Closure $get){
                        if($get('total_percentage') != 100){
                            return true;
                        }
                        return false;
                    })
                    ->columnSpan(4)
                    ->collapsed(),
                    Section::make('2nd Quarter')->schema([
                        Section::make('Written Works')->schema([
                            TextInput::make('total_written_work_1_2')
                            ->label('Work 1')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_2_2')
                            ->label('Work 2')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_3_2')
                            ->label('Work 3')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_4_2')
                            ->label('Work 4')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_5_2')
                            ->label('Work 5')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_6_2')
                            ->label('Work 6')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_7_2')
                            ->label('Work 7')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_8_2')
                            ->label('Work 8')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_9_2')
                            ->label('Work 9')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_written_work_10_2')
                            ->label('Work 10')
                            ->numeric()
                            ->reactive(),
                        ])
                        ->columns(5),
                        Section::make('Performance Tasks')->schema([
                            TextInput::make('total_performance_task_1_2')
                            ->label('Task 1')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_2_2')
                            ->label('Task 2')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_3_2')
                            ->label('Task 3')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_4_2')
                            ->label('Task 4')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_5_2')
                            ->label('Task 5')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_6_2')
                            ->label('Task 6')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_7_2')
                            ->label('Task 7')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_8_2')
                            ->label('Task 8')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_9_2')
                            ->label('Task 9')
                            ->numeric()
                            ->reactive(),
                            TextInput::make('total_performance_task_10_2')
                            ->label('Task 10')
                            ->numeric()
                            ->reactive(),
                        ])
                        ->columns(5),
                        TextInput::make('total_quarterly_exam_2')
                            ->label('Quarterly Exam 2 Total')
                            ->numeric()
                            ->reactive(),
                        
                    ])
                    ->columnSpan(4)
                    ->collapsed(),
                ])
                ->description("Register written works, performance tasks, and quarterly assessment score totals here.")
                ->columns(4)->collapsed(),

                    Repeater::make('student_grades')
                        ->schema([
                            Select::make('name')
                            ->reactive()
                            ->disabled()
                            ->columnSpan(3)
                            ->options(function (callable $get) {
                                if(!$get('../../class_id') || !$get('../../subject_id')){
                                    return [];
                                }
                                $semester = Subject::where('id', $get('../../subject_id'))->first()->semester;
                                $students = StudentOfClass::whereIn('lrn', Classes::where('id', $get('../../class_id'))->first()->students)->where('school_year_id', SchoolYear::where('current', true)->first()->id)->get();
                                return $students->pluck('lname', 'lrn')->map(function ($lname, $lrn) use ($students, $semester) {
                                    $student = $students->where('lrn', $lrn)->first();

                                    $fname = $student->fname;
                                    $mname = $student->mname;
                                    $fullName = $lname.', '.$fname . ' ' . $mname;

                                    if($semester == 1){
                                        if ($student && $student->sem_1_status == 1) {
                                            return '(Dropped)'.' '.$fullName;
                                        }    
                                    } else{
                                        if ($student && $student->sem_1_status == 2) {
                                            return '(Dropped)'.' '.$fullName;
                                        }
                                    }
                                    
                                    return $fullName;
                                });
                            }),
                            Section::make('Grades')->schema([
                                Section::make('1st Quarter')->schema([
                                    Section::make('Written Works')->schema([
                                        TextInput::make('written_work_1_1')
                                        ->label('Work 1')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_1_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_2_1')
                                        ->label('Work 2')
                                        ->numeric()
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_2_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_3_1')
                                        ->label('Work 3')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_3_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_4_1')
                                        ->label('Work 4')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_4_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_5_1')
                                        ->label('Work 5')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_5_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_6_1')
                                        ->label('Work 6')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_6_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_7_1')
                                        ->label('Work 7')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_7_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_8_1')
                                        ->label('Work 8')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_8_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_9_1')
                                        ->label('Work 9')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_9_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_10_1')
                                        ->label('Work 10')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_10_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                    ])
                                    ->columns(5),
                                    Section::make('Performance Tasks')->schema([
                                        TextInput::make('performance_task_1_1')
                                        ->label('Task 1')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_1_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_2_1')
                                        ->label('Task 2')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_2_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_3_1')
                                        ->label('Task 3')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_3_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_4_1')
                                        ->label('Task 4')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_4_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_5_1')
                                        ->label('Task 5')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_5_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_6_1')
                                        ->label('Task 6')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_6_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_7_1')
                                        ->label('Task 7')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_7_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_8_1')
                                        ->label('Task 8')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_8_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_9_1')
                                        ->label('Task 9')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_9_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_10_1')
                                        ->label('Task 10')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_10_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                    ])
                                    ->columns(5),
                                    TextInput::make('quarterly_exam_1')
                                        ->label('Quarterly Exam 1 Score')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_quarterly_exam_1')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                    
                                ])
                                ->columnSpan(3)
                                ->collapsed(),
                                Section::make('2nd Quarter')->schema([
                                    Section::make('Written Works')->schema([
                                        TextInput::make('written_work_1_2')
                                        ->label('Work 1')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_1_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        
                                        ->reactive(),
                                        TextInput::make('written_work_2_2')
                                        ->label('Work 2')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_2_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_3_2')
                                        ->label('Work 3')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_3_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_4_2')
                                        ->label('Work 4')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_4_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_5_2')
                                        ->label('Work 5')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_5_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_6_2')
                                        ->label('Work 6')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_6_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_7_2')
                                        ->label('Work 7')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_7_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_8_2')
                                        ->label('Work 8')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_8_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_9_2')
                                        ->label('Work 9')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_9_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('written_work_10_2')
                                        ->label('Work 10')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_written_work_10_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                    ])
                                    ->columns(5),
                                    Section::make('Performance Tasks')->schema([
                                        TextInput::make('performance_task_1_2')
                                        ->label('Task 1')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_1_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_2_2')
                                        ->label('Task 2')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_2_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_3_2')
                                        ->label('Task 3')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_3_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_4_2')
                                        ->label('Task 4')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_4_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_5_2')
                                        ->label('Task 5')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_5_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_6_2')
                                        ->label('Task 6')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_6_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_7_2')
                                        ->label('Task 7')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_7_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_8_2')
                                        ->label('Task 8')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_8_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_9_2')
                                        ->label('Task 9')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_9_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                        TextInput::make('performance_task_10_2')
                                        ->label('Task 10')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_performance_task_10_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                    ])
                                    ->columns(5),
                                    TextInput::make('quarterly_exam_2')
                                        ->label('Quarterly Exam 2 Score')
                                        ->numeric()
                                        ->disabled(function(Closure $set, $get){
                                            if($get('../../total_quarterly_exam_2')){
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->afterStateUpdated(function(Closure $get, $set){
                                            $written = $get('../../written');
                                            $performance = $get('../../performance');
                                            $quarterly = $get('../../quarterly');
                                            
                                            // FIRST QUARTER
                                            $written_works_1 = array(
                                                $get('written_work_1_1'),
                                                $get('written_work_2_1'),
                                                $get('written_work_3_1'),
                                                $get('written_work_4_1'),
                                                $get('written_work_5_1'),
                                                $get('written_work_6_1'),
                                                $get('written_work_7_1'),
                                                $get('written_work_8_1'),
                                                $get('written_work_9_1'),
                                                $get('written_work_10_1')
                                            );
                                            $performance_tasks_1 = array(
                                                $get('performance_task_1_1'),
                                                $get('performance_task_2_1'),
                                                $get('performance_task_3_1'),
                                                $get('performance_task_4_1'),
                                                $get('performance_task_5_1'),
                                                $get('performance_task_6_1'),
                                                $get('performance_task_7_1'),
                                                $get('performance_task_8_1'),
                                                $get('performance_task_9_1'),
                                                $get('performance_task_10_1')
                                            );
                                            $total_written_works_1 = array(
                                                $get('../../total_written_work_1_1'),
                                                $get('../../total_written_work_2_1'),
                                                $get('../../total_written_work_3_1'),
                                                $get('../../total_written_work_4_1'),
                                                $get('../../total_written_work_5_1'),
                                                $get('../../total_written_work_6_1'),
                                                $get('../../total_written_work_7_1'),
                                                $get('../../total_written_work_8_1'),
                                                $get('../../total_written_work_9_1'),
                                                $get('../../total_written_work_10_1')
                                            );
                                            $total_performance_tasks_1 = array(
                                                $get('../../total_performance_task_1_1'),
                                                $get('../../total_performance_task_2_1'),
                                                $get('../../total_performance_task_3_1'),
                                                $get('../../total_performance_task_4_1'),
                                                $get('../../total_performance_task_5_1'),
                                                $get('../../total_performance_task_6_1'),
                                                $get('../../total_performance_task_7_1'),
                                                $get('../../total_performance_task_8_1'),
                                                $get('../../total_performance_task_9_1'),
                                                $get('../../total_performance_task_10_1')
                                            );
                                            $score_written_works_1_sum = array_sum(array_filter($total_written_works_1, 'is_numeric'));
                                            $score_performance_tasks_1_sum = array_sum(array_filter($total_performance_tasks_1, 'is_numeric'));
                                            $total_quarterly_exam_1 = $get('../../total_quarterly_exam_1');
                                            $quarterly_exam_1 = $get('quarterly_exam_1');
                                            // SECOND QUARTER
                                            $written_works_2 = array(
                                                $get('written_work_1_2'),
                                                $get('written_work_2_2'),
                                                $get('written_work_3_2'),
                                                $get('written_work_4_2'),
                                                $get('written_work_5_2'),
                                                $get('written_work_6_2'),
                                                $get('written_work_7_2'),
                                                $get('written_work_8_2'),
                                                $get('written_work_9_2'),
                                                $get('written_work_10_2')
                                            );
                                            $performance_tasks_2 = array(
                                                $get('performance_task_1_2'),
                                                $get('performance_task_2_2'),
                                                $get('performance_task_3_2'),
                                                $get('performance_task_4_2'),
                                                $get('performance_task_5_2'),
                                                $get('performance_task_6_2'),
                                                $get('performance_task_7_2'),
                                                $get('performance_task_8_2'),
                                                $get('performance_task_9_2'),
                                                $get('performance_task_10_2')
                                            );
                                            $total_written_works_2 = array(
                                                $get('../../total_written_work_1_2'),
                                                $get('../../total_written_work_2_2'),
                                                $get('../../total_written_work_3_2'),
                                                $get('../../total_written_work_4_2'),
                                                $get('../../total_written_work_5_2'),
                                                $get('../../total_written_work_6_2'),
                                                $get('../../total_written_work_7_2'),
                                                $get('../../total_written_work_8_2'),
                                                $get('../../total_written_work_9_2'),
                                                $get('../../total_written_work_10_2')
                                            );
                                            $total_performance_tasks_2 = array(
                                                $get('../../total_performance_task_1_2'),
                                                $get('../../total_performance_task_2_2'),
                                                $get('../../total_performance_task_3_2'),
                                                $get('../../total_performance_task_4_2'),
                                                $get('../../total_performance_task_5_2'),
                                                $get('../../total_performance_task_6_2'),
                                                $get('../../total_performance_task_7_2'),
                                                $get('../../total_performance_task_8_2'),
                                                $get('../../total_performance_task_9_2'),
                                                $get('../../total_performance_task_10_2')
                                            );
                                            $score_written_works_2_sum = array_sum(array_filter($total_written_works_2, 'is_numeric'));
                                            $score_performance_tasks_2_sum = array_sum(array_filter($total_performance_tasks_2, 'is_numeric'));
                                            $total_quarterly_exam_2 = $get('../../total_quarterly_exam_2');
                                            $quarterly_exam_1 = $get('quarterly_exam_2');

                                            $total_written_works_1_sum = 0;
                                            $total_written_works_2_sum = 0;
                                            $total_performance_tasks_1_sum = 0;
                                            $total_performance_tasks_2_sum = 0;



                                            for ($i = 0; $i < count($written_works_1); $i++) {
                                                if (!empty($total_written_works_1[$i]) && !empty($written_works_1[$i])) {
                                                    $total_written_works_1_sum += $written_works_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($written_works_2); $i++) {
                                                if (!empty($total_written_works_2[$i]) && !empty($written_works_2[$i])) {
                                                    $total_written_works_2_sum += $written_works_2[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_1); $i++) {
                                                if (!empty($total_performance_tasks_1[$i]) && !empty($performance_tasks_1[$i])) {
                                                    $total_performance_tasks_1_sum += $performance_tasks_1[$i];
                                                }
                                            }
                                            for ($i = 0; $i < count($performance_tasks_2); $i++) {
                                                if (!empty($total_performance_tasks_2[$i]) && !empty($performance_tasks_2[$i])) {
                                                    $total_performance_tasks_2_sum += $performance_tasks_2[$i];
                                                }
                                            }

                                            if($total_written_works_1_sum != 0 && $score_written_works_1_sum != 0){
                                                $resultWritten1 = ($total_written_works_1_sum / $score_written_works_1_sum) * $written;
                                            } else{
                                                $resultWritten1 = null;
                                            }
                                            if($total_written_works_2_sum != 0 && $score_written_works_2_sum != 0){
                                                $resultWritten2 = ($total_written_works_2_sum / $score_written_works_2_sum) * $written;
                                            } else{
                                                $resultWritten2 = null;
                                            }
                                            if($total_performance_tasks_1_sum != 0 && $score_performance_tasks_1_sum != 0){
                                                $resultPerformance1 = ($total_performance_tasks_1_sum / $score_performance_tasks_1_sum) * $performance;
                                            } else{
                                                $resultPerformance1 = null;
                                            }
                                            if($total_performance_tasks_2_sum != 0 && $score_performance_tasks_2_sum != 0){
                                                $resultPerformance2 = ($total_performance_tasks_2_sum / $score_performance_tasks_2_sum) * $performance;
                                            } else{
                                                $resultPerformance2 = null;
                                            }

                                            if($get('quarterly_exam_1')){
                                                $resultExam1 = ($get('quarterly_exam_1') / $get('../../total_quarterly_exam_1'))* $quarterly;
                                            } else{
                                                $resultExam1 = null;
                                            }
                                            if($get('quarterly_exam_2')){
                                                $resultExam2 = ($get('quarterly_exam_2') / $get('../../total_quarterly_exam_2'))* $quarterly;
                                            } else{
                                                $resultExam2 = null;
                                            }

                                            $average_1 = null;
                                            $average_2 = null;
                                            if($resultWritten1 && $resultPerformance1 && $resultExam1){
                                                $set('1st_quarter_grade', $resultWritten1+$resultPerformance1+$resultExam1);
                                                $average_1 = $resultWritten1+$resultPerformance1+$resultExam1;
                                            }
                                            if($resultWritten2 && $resultPerformance2 && $resultExam2){
                                                $set('2nd_quarter_grade', $resultWritten2+$resultPerformance2+$resultExam2);
                                                $average_2 = $resultWritten2+$resultPerformance2+$resultExam2;
                                            }

                                            if($average_1 && $average_2){
                                                $average_grade = ($average_1+$average_2)/2;
                                                $set('average', $average_grade);
                                                if($average_grade > 74){
                                                    $set('remarks', 'Passed');
                                                } else{
                                                    $set('remarks', 'Failed');
                                                }
                                                if($average_grade > 89){
                                                    $set('description', 'Outstanding');
                                                } else if ($average_grade > 84){
                                                    $set('description', 'Very Satisfactory');
                                                } else if ($average_grade > 79){
                                                    $set('description', 'Satisfactory');
                                                } else if ($average_grade > 74){
                                                    $set('description', 'Fairly Satisfactory');
                                                } else{
                                                    $set('description', 'Did Not Meet Expectations');
                                                }
                                            }
                                            
                                        })
                                        ->reactive(),
                                    
                                ])
                                ->columnSpan(3)
                                ->collapsed(),
                                
                                   
                                TextInput::make('1st_quarter_grade')
                                ->numeric()
                                ->reactive()
                                ->afterStateUpdated(function (Closure $set, $get) {
                                    if($get('2nd_quarter_grade')){
                                        $average = ($get('1st_quarter_grade') + $get('2nd_quarter_grade'))/2;
                                        $set('average', $average);
                                        if($average > 74){
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
                                    if($get('2nd_quarter_grade')){
                                        $average = ((int)$get('1st_quarter_grade') + (int)$get('2nd_quarter_grade'))/2;
                                        $set('average', $average);
                                        if($average > 74){
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
                                    } else{
                                        $set('average', null);
                                        $set('remarks', null);
                                        $set('description', null);
                                    }
                                    
                                }),
                                TextInput::make('average')
                                ->reactive()
                                ->disabled(),
                                Select::make('remarks')
                                ->disabled()
                                ->columnSpan(1)
                                ->placeholder('This field is auto-generated')
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
                            ->collapsed(),

                            
                        ])
                        ->disableItemCreation()
                        ->disableItemDeletion()
                        ->columns(3)
                        ->columnSpan(2)
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
                ->label("ECR.xlsx")
                ->icon('heroicon-o-newspaper')
                ->url(fn (SubjectLoad $record): string => ('/e-class-records/'.$record->id.'/export')),
                Action::make(auth()->user()->hasRole('Superadmin') ? 'export_class_record-pdf' : 'export-pdf')
                ->label("ECR.pdf")
                ->icon('heroicon-o-newspaper')
                ->url(fn (SubjectLoad $record): string => ('/e-class-records/'.$record->id.'/export-pdf')),
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
            'edit' => Pages\EditEClassRecord::route('/{record}/edit'),
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
