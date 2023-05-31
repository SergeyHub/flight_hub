<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileRFormAircraftOwner extends Model
{
    use HasFactory;

    protected $table = 'file_r_form_aircraft_owner';

    public $timestamps = false;

    protected $fillable = [
        'file_id',
        'r_form_aircraft_owner_id',
    ];
}
