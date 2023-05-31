<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassengerArray extends Model
{
    protected $table = 'passengers_array';

    protected $fillable = [
        'id_passenger',
        'fullname',
        'citizenship',
        'forms_n_id_pakus',
    ];


    /* ---------- Relations ---------- */
}
