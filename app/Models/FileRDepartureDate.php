<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileRDepartureDate extends Model
{
    use HasFactory;

    protected $table = 'file_r_departure_dates';

    public $timestamps = false;

    protected $fillable = [
        'file_id',
        'departure_dates_id',
    ];
}
