<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileNFormAircraftOwner extends Model
{
    use HasFactory;

    protected $table = 'file_n_form_aircraft_owner';

    public $timestamps = false;

    protected $fillable = [
        'file_id',
        'n_form_aircraft_owner_id',
    ];
}
