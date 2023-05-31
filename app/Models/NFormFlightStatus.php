<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NFormFlightStatus extends Model
{
    protected $table = 'n_form_flight_statuses';

    protected $casts = [
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $timestamps = false;

    protected $fillable = [
        'name_rus',
        'name_lat',
    ];

    /*************** Relations ***************/


    /** Согласование */

    public function n_form_flights(): BelongsToMany
    {
        return $this->belongsToMany(
            NFormFlight::class,
            'n_form_flight_n_form_flight_status',
            'n_form_flight_id',
            'n_form_flight_status_id',
            'id',
            'n_form_flight_id'
        );
    }

    public function role(): HasOneThrough
    {
        return $this->hasOneThrough(
            Role::class,
            NFormFlightNFormFlightStatus::class,
            'n_form_flight_status_id',
            'id',
            'id',
            'role_id',
        );
    }

    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(
            NFormComment::class,
            NFormFlightNFormFlightStatus::class,
            'n_form_flight_status_id',
            'n_form_comment_id',
            'id',
            'comment_id',
        );
    }

    public function flight_status():HasMany
    {
        return $this->hasMany(
            NFormFlightNFormFlightStatus::class,
            'n_form_flight_status_id',
            'id',
        );
    }

    public function flights():HasMany
    {
        return $this->hasMany(
            NFormFlight::class,
            'status_id',
            'id',
            );
    }

    public function nForm(): HasOneThrough
    {
        return $this->hasOneThrough(
            NForm::class,
            NFormFlightNFormFlightStatus::class,
            'n_form_flight_status_id',
            'n_forms_id',
            'id',
            'id_pakus',
        );
    }
}
