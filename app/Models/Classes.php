<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StudentOfClass;
use App\Models\Student;


class Classes extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_year_id',
        'completion',
        'name',
        'grade_level',
        'section',
        'track_course',
        'adviser_id',
        'subjects',
        'students',
    ];

    protected $casts = [
        'subjects' => 'array',
        'students' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Set the ID value on the model
            $students = $model->students;
            foreach($students as $student){
                $student = Student::where('lrn', $student)->first();
                $newStudent = new StudentOfClass();
                $newStudent->school_year_id = $model->school_year_id;
                $newStudent->name = $model->name;
                $newStudent->lrn = $student->lrn;
                $newStudent->lname = $student->lname;
                $newStudent->fname = $student->fname;
                $newStudent->mname = $student->mname;
                $newStudent->gender = $student->gender;
                $newStudent->date_of_birth = $student->date_of_birth;
                $newStudent->religion = $student->religion;
                $newStudent->no_street_purok = $student->no_street_purok;
                $newStudent->father_name = $student->father_name;
                $newStudent->mother_name = $student->mother_name;
                $newStudent->guardian = $student->guardian;
                $newStudent->relationship = $student->relationship;
                $newStudent->contact_number = $student->contact_number;
                $newStudent->modality = $student->modality;
                $newStudent->student_type = $student->student_type;
                $newStudent->remarks = $student->remarks;
                $newStudent->municipality = $student->municipality;
                $newStudent->province = $student->province;
                $newStudent->barangay = $student->barangay;
                $newStudent->save();
            }
        });

        static::deleting(function ($model) {
            // Delete associated students
            $students = $model->students;
            foreach ($students as $studentLrn) {
                StudentOfClass::where('lrn', $studentLrn)->where('school_year_id', $model->school_year_id)
                ->where('name', $model->name)
                ->delete();
            }
            SubjectLoad::where('class_id',$model->getKey())->delete();
        });

        // static::updating(function ($model) {

        //     // Set the ID value on the model
        //     $model->school_year_id = SchoolYear::where('sy', $model->school_year)->first()->id;
        //     $model->adviser_id = User::where('name', $model->adviser)->first()->id;
        // });
    }
    
}
