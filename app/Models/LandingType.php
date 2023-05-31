<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingType extends Model
{
    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'laravel_through_key'
    ];


    /* ---------- Relations ---------- */
}
