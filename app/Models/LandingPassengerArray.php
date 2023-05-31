<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPassengerArray extends Model
{
    protected $table = 'landing_passengers_array';

    protected $fillable = [
        'id_passenger',
        'fullname',
        'citizenship',
        'from_n_landing_idl_pakus',
        'from_n_landing_form_n_id_pakus',
        'landing_passengers_arraycol',
    ];


    /* ---------- Relations ---------- */
}
