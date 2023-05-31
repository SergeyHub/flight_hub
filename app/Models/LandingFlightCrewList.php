<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingFlightCrewList extends Model
{
    protected $fillable = [
        'id_flight_crewmate',
        'fullname',
        'citizenship',
        'from_n_landing_idl_pakus',
        'from_n_landing_form_n_id_pakus',
    ];


    /* ---------- Relations ---------- */
}
