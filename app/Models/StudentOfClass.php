<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class StudentOfClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'sem_1_status',
        'sem_2_status',
        'school_year_id',
        'name',
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
}
