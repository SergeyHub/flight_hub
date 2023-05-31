<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileRFormAirline extends Model
{
    use HasFactory;

    protected $table = 'file_r_form_airlines';

    public $timestamps = false;

    protected $fillable = [
        'file_id',
        'r_form_airlines_id',
    ];
}
