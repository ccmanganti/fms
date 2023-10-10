<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SchoolYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'sy',
        'sydate',
        'completion',
        'current',
        'principal',
        'position',
        'signature',
        'sds',
        'signature_sds',
    ];

    public function handleSettingCurrentYear()
    {
        // If the model is being created (primary key is null) or is_current is set to true, proceed with setting the current academic year
        if ($this->getKey() === null || $this->current) {
            // Update the existing current academic year and set its is_current value to false
            DB::table('school_years')->where('current', true)->update(['current' => false]);

            // Set the current academic year instance as the new current one
            $this->current = true;
        }
    }


    protected static function boot()
    {
        parent::boot();

        // Register the creating event listener
        static::creating(function ($academicYear) {
            // Check if the is_current column is explicitly set to true
            if ($academicYear->current) {
                // Update the existing current academic year and set its is_current value to false
                DB::table('school_years')->where('current', true)->update(['current' => false]);
            } else {
                // Set is_current to false for the new entry being created
                $academicYear->current = false;
            }
        });

        static::updating(function ($academicYear) {
            // Retrieve the existing academic year instance from the database
            $existingAcademicYear = self::find($academicYear->getKey());
    
            // Check if the is_current column is toggled to true
            if ($academicYear->current && !$existingAcademicYear->current) {
                // Update the existing current academic year and set its is_current value to false
                DB::table('school_years')->where('current', true)->update(['current' => false]);
    
                // Set the current academic year instance as the new current one
                $academicYear->current = true;
            }
        });
    }
}
