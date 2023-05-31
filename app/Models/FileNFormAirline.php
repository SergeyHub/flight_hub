<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileNFormAirline extends Model
{
    use HasFactory;

    protected $table = 'file_n_form_airlines';

    public $timestamps = false;

    protected $fillable = [
        'file_id',
        'n_form_airlines_id',
    ];
}
