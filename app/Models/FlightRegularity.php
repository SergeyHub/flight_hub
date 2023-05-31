<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlightRegularity extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function airline()
    {
        return $this->belongsTo(
            Airline::class,
            'airline_id',
            'AIRLINES_ID',
        );
    }
}
