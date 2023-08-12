<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

Str::macro('capitalizeWords', function ($value) {
    return ucwords($value);
});
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'sem_1_status',
        'sem_2_status',
        'lrn',
        'lname',
        'fname',
        'mname',
        'gender',
        'date_of_birth',
        'age',
        'religion',
        'no_street_purok',
        'barangay',
        'municipality',
        'province',
        'father_name',
        'mother_name',
        'guardian',
        'relationship',
        'contact_number',
        'modality',
        'student_type',
        'remarks',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($student) {
            $existingStudent = self::where('lrn', $student->lrn)->first();

            if ($existingStudent) {
                // Update the existing record
                
                $existingStudent->lrn = $student->lrn;
                $existingStudent->lname = $student->lname;
                $existingStudent->fname = $student->fname;
                $existingStudent->mname = $student->mname;
                $existingStudent->gender = $student->gender;
                $existingStudent->date_of_birth = $student->date_of_birth;
                $existingStudent->religion = $student->religion;
                $existingStudent->no_street_purok = $student->no_street_purok;
                $existingStudent->father_name = $student->father_name;
                $existingStudent->mother_name = $student->mother_name;
                $existingStudent->guardian = $student->guardian;
                $existingStudent->relationship = $student->relationship;
                $existingStudent->contact_number = $student->contact_number;
                $existingStudent->modality = $student->modality;
                $existingStudent->student_type = $student->student_type;
                $existingStudent->remarks = $student->remarks;
                $existingStudent->municipality = ucwords($student->municipality);
                $existingStudent->province = ucwords($student->province);
                $existingStudent->barangay = ucwords($student->barangay);
                $existingStudent->save();

                // Cancel the creation of a new record
                return false;
            }

            // No existing record found, continue with the creation
            $student->municipality = ucwords($student->municipality);
            $student->province = ucwords($student->province);
            $student->barangay = ucwords($student->barangay);
        });
    }

}
