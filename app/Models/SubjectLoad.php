<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Classes;
use App\Models\EClassRecord;

class SubjectLoad extends Model
{
    use HasFactory;

    protected $fillable = [
        // To be determined in Loads: Subject, Classss
        'school_year_id',
        'teacher_id',
        'class_id',
        'subject_id',
        'semester',
        'written',
        'performance',
        'quarterly',
        'total_percentage',
        'total_written_work_1_1',
        'total_written_work_2_1',
        'total_written_work_3_1',
        'total_written_work_4_1',
        'total_written_work_5_1',
        'total_written_work_6_1',
        'total_written_work_7_1',
        'total_written_work_8_1',
        'total_written_work_9_1',
        'total_written_work_10_1',
        'total_performance_task_1_1',
        'total_performance_task_2_1',
        'total_performance_task_3_1',
        'total_performance_task_4_1',
        'total_performance_task_5_1',
        'total_performance_task_6_1',
        'total_performance_task_7_1',
        'total_performance_task_8_1',
        'total_performance_task_9_1',
        'total_performance_task_10_1',
        'total_quarterly_exam_1',
        'total_written_work_1_2',
        'total_written_work_2_2',
        'total_written_work_3_2',
        'total_written_work_4_2',
        'total_written_work_5_2',
        'total_written_work_6_2',
        'total_written_work_7_2',
        'total_written_work_8_2',
        'total_written_work_9_2',
        'total_written_work_10_2',
        'total_performance_task_1_2',
        'total_performance_task_2_2',
        'total_performance_task_3_2',
        'total_performance_task_4_2',
        'total_performance_task_5_2',
        'total_performance_task_6_2',
        'total_performance_task_7_2',
        'total_performance_task_8_2',
        'total_performance_task_9_2',
        'total_performance_task_10_2',
        'total_quarterly_exam_2',
        'student_grades',
    ];

    protected $casts = [
        'student_grades' => 'array',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->student_grades = $model->getFormattedStudentsAttribute();
        });

        // static::updating(function ($model) {
        //     $previousRecord = $model->fresh();
    
        //     if (
        //         $model->school_year_id === $previousRecord->school_year_id &&
        //         $model->class_id === $previousRecord->class_id &&
        //         $model->subject_id === $previousRecord->subject_id &&
        //         $model->semester === $previousRecord->semester
        //     ) {
        //         // The relevant fields are the same, so skip updating student_grades
        //         return;
        //     }
    
        //     $model->student_grades = $model->getFormattedStudentsAttribute();
        // });
    }


    public function getFormattedStudentsAttribute()
    {
        // Get the students from the associated Class model
        $class = Classes::find($this->class_id);
        $students = $class->students;

        // Initialize the formatted students array
        $formattedStudents = [];

        foreach ($students as $student) {
            $formattedStudents[] = [
                'name' => $student,
                'written_work_1_1'=> null,
                'written_work_2_1'=> null,
                'written_work_3_1'=> null,
                'written_work_4_1'=> null,
                'written_work_5_1'=> null,
                'written_work_6_1'=> null,
                'written_work_7_1'=> null,
                'written_work_8_1'=> null,
                'written_work_9_1'=> null,
                'written_work_10_1'=> null,
                'performance_task_1_1'=> null,
                'performance_task_2_1'=> null,
                'performance_task_3_1'=> null,
                'performance_task_4_1'=> null,
                'performance_task_5_1'=> null,
                'performance_task_6_1'=> null,
                'performance_task_7_1'=> null,
                'performance_task_8_1'=> null,
                'performance_task_9_1'=> null,
                'performance_task_10_1'=> null,
                'quarterly_exam_1'=> null,
                'written_work_1_2'=> null,
                'written_work_2_2'=> null,
                'written_work_3_2'=> null,
                'written_work_4_2'=> null,
                'written_work_5_2'=> null,
                'written_work_6_2'=> null,
                'written_work_7_2'=> null,
                'written_work_8_2'=> null,
                'written_work_9_2'=> null,
                'written_work_10_2'=> null,
                'performance_task_1_2'=> null,
                'performance_task_2_2'=> null,
                'performance_task_3_2'=> null,
                'performance_task_4_2'=> null,
                'performance_task_5_2'=> null,
                'performance_task_6_2'=> null,
                'performance_task_7_2'=> null,
                'performance_task_8_2'=> null,
                'performance_task_9_2'=> null,
                'performance_task_10_2'=> null,
                '1st_quarter_grade' => null,
                '2nd_quarter_grade' => null,
                'quarterly_exam_2'=> null,
                'average' => null,
                'remarks' => null,
                'description' => null,
            ];
        }
        return $formattedStudents;
    }

}
