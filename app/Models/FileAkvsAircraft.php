<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileAkvsAircraft extends Model
{
    use HasFactory;

    protected $table = 'file_akvs_aircrafts';

    public $timestamps = false;

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = [
        'file_id',
        'akvs_fleet_id',
    ];
}
