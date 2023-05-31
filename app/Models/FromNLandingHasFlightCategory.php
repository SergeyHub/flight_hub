<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FromNLandingHasFlightCategory extends Model
{
    protected $fillable = [
        'from_n_landing_idl_pakus',
        'from_n_landing_form_n_id_pakus',
        'flight_categories_id_flight_category',
    ];


    /* ---------- Relations ---------- */
}
