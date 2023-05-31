<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlightCategory extends Model
{
    protected $primaryKey = 'CATEGORIES_ID';

    protected $table = 'flight_categories';

    protected $hidden = ['pivot'];

    protected $fillable = [
        'NAMELAT',
        'NAMERUS',
        'is_commercial',
    ];


    /* ---------- Relations ---------- */
}
