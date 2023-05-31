<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileRFormAircraft extends Model
{
    use HasFactory;

    protected $table = 'file_r_form_aircrafts';

    protected $fillable = [
        'file_id',
        'r_form_aircrafts_id',
    ];

}
