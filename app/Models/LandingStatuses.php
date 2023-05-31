<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingStatuses extends Model
{
    protected $table = 'lending_statuses';

    protected $fillable = [
        'name'
    ];
}
