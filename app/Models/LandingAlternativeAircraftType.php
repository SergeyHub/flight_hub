<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingAlternativeAircraftType extends Model
{
    protected $table = 'landing_alternative_aircrafts_types';

    protected $fillable = [
        'from_n_landing_idl_pakus',
        'from_n_landing_form_n_id_pakus',
        'aircraft_type_AIRCRAFT_ID',
    ];


    /* ---------- Relations ---------- */
}
