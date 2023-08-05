<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($model) {

    //         // Set the ID value on the model
    //         $model->school_year_id = SchoolYear::where('sy', $model->school_year)->first()->id;
    //         $model->adviser_id = User::where('name', $model->adviser)->first()->id;
    //     });

    //     static::updating(function ($model) {

    //         // Set the ID value on the model
    //         $model->school_year_id = SchoolYear::where('sy', $model->school_year)->first()->id;
    //         $model->adviser_id = User::where('name', $model->adviser)->first()->id;
    //     });
    // }
    
}
