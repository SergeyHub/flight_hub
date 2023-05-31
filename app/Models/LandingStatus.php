<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingStatus extends Model
{
    protected $table = 'landing_statuses';

    protected $fillable = [
        'name'
    ];

    protected $hidden = [
        'laravel_through_key'
    ];


    /* ---------- Relations ---------- */
}
