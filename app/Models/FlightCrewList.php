<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlightCrewList extends Model
{
    protected $fillable = [
        'id_flight_crewmate',
        'fullname',
        'citizenship',
        'forms_n_id_pakus',
    ];


    /* ---------- Relations ---------- */
}
