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
        // To be determined in Loads: Subject, Class
        'school_year_id',
        'teacher_id',
        'class_id',
        'subject_id',
        'semester',
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

        static::updating(function ($model) {
            $previousRecord = $model->fresh();
    
            if (
                $model->school_year_id === $previousRecord->school_year_id &&
                $model->class_id === $previousRecord->class_id &&
                $model->subject_id === $previousRecord->subject_id &&
                $model->semester === $previousRecord->semester
            ) {
                // The relevant fields are the same, so skip updating student_grades
                return;
            }
    
            $model->student_grades = $model->getFormattedStudentsAttribute();
        });
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
                '1st_quarter_grade' => null,
                '2nd_quarter_grade' => null,
                'average' => null,
                'remarks' => null,
                'description' => null,
            ];
        }
        return $formattedStudents;
    }

}
