<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileNFormAircraft extends Model
{
    use HasFactory;

    protected $table = 'file_n_form_aircrafts';

    protected $fillable = [
        'file_id',
        'n_form_aircrafts_id',
    ];

}
