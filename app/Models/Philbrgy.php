<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Philbrgy extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'brgyCode', 'brgyDesc', 'regCode', 'provCode', 'citymunCode'
    ];
}
