<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Philprovince extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id', 'psgcCode', 'provDesc', 'regCode', 'provCode'
    ];
}
