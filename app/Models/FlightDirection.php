<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FlightDirection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'updated_at' => 'date:d.m.Y',
        'begin_date' => 'date:d.m.Y',
        'end_date' => 'date:d.m.Y',
    ];

    protected $hidden = [
        'pivot',
    ];

    public function fromAirhub()
    {
        return $this->hasOne(
            Airhub::class,
            'id',
            'from_airhub_id',
        );
    }

    public function toAirhub()
    {
        return $this->hasOne(
            Airhub::class,
            'id',
            'to_airhub_id',
        );
    }

    public function airlines()
    {
        return $this
            ->belongsToMany(
                Airline::class,
                AirlineFlightDirection::class,
                'flight_direction_id',
                'airline_id',
                'id',
                'AIRLINES_ID',
            )
            ->withPivot('airline_flight_direction_limit_id')
            ->withPivot('airline_flight_direction_approval_id');
    }

    public function status()
    {
        return $this->belongsTo(FlightDirectionStatus::class);
    }
}
