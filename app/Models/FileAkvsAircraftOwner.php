<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileAkvsAircraftOwner extends Model
{
    use HasFactory;

    protected $table = 'file_akvs_aircrafts_owner';

    protected $fillable = [
        'file_id',
        'akvs_aircraft_owner_id',
    ];
}
