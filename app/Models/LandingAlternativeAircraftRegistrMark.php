<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingAlternativeAircraftRegistrMark extends Model
{
    protected $table = 'landing_alternative_aircrafts_registr_marks';

    protected $fillable = [
        'id_registr_marks',
        'registr_mark_num',
        'forms_n_id_pakus',
    ];


    /* ---------- Relations ---------- */
}
