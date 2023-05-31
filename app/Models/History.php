<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $fillable = [
        'id_pakus',
        'change_datetime',
        'status',
        'details',
        'access',
        'user_id',
        'id_from',
    ];


    /* ---------- Relations ---------- */
}
