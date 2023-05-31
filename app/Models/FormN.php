<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class FormN extends Model
{
    use HasFactory;

    protected $table = 'forms_n';

    protected $primaryKey = 'id_pakus';

    protected $fillable = [
        'from_id_from',
        'id_pp',
        'request_version',
        'status_id',
        'access',
        'permit_num',
        'airline_id',
        'registry_state_id',
        'flight_num',
        'indebtedness',
        'remarks',
        'main_aircraft_type_icao_id',
        'main_aircraft_registration_mark',
        'main_aircraft_max_takeoff_mass',
        'main_aircraft_max_takeoff_mass_unit',
        'main_aircraft_max_landing_mass',
        'main_aircraft_max_landing_mass_unit',
        'main_aircraft_zero_fuel_weight',
        'main_aircraft_zero_fuel_weight_unit',
        'other_aircrafts_alternative_entire_fleet',
        'aircraft_owner_name',
        'aircraft_owner_contacts',
        'aircraft_owner_state_fulladdress',
        'flight_purpose_is_commercial',
        'flight_crew_by_fpl',
        'flight_crew_citizenship',
        'flight_crew_quantity',
        'cargo_danger_classes_icao_id',
        'cargo_type_character',
        'cargo_consignor',
        'cargo_consignor_fulladdress_contact',
        'cargo_consignee',
        'cargo_consignee_fulladdress_contact',
        'cargo_charterer',
        'cargo_charterer_fulladdress_contac',
        'cargo_receiving_party',
        'cargo_receiving_party_fulladdress_contact',
        'route_departure_datetime',
        'route_departure_airport_id',
        'route_flight_num',
        'route_passengers_quantity',
        'route_cargo_mass',
        'route_cargo_mass_unit',
        'route_details',
        'route_arrival_datetime',
        'route_arrival_airport',
        'payment_is_cashless',
        'payment_payer_address',
        'payment_bank',
        'payment_account_num',
        'payment_remarks',
    ];

    /* ---------- Relations ---------- */

    public function aircrafts(): BelongsToMany
    {
        return $this->belongsToMany(
            Aircraft::class,
            'alternative_aircrafts_types',
            'forms_n_id_pakus',
            'aircraft_type_AIRCRAFT_ID'
        );
    }

    public function flightCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            FlightCategory::class,
            'forms_n_has_flight_categories',
            'forms_n_id_pakus',
            'flight_categories_id_flight_category',
            'id_pakus',
            'id_flight_category'
        );
    }

    public function departureAirports(): HasMany
    {
        return $this->hasMany(
            Airport::class,
            'AIRPORTS_ID',
            'route_departure_airport_id'
        );
    }

    public function arrivalAirports(): HasMany
    {
        return $this->hasMany(
            Airport::class,
            'AIRPORTS_ID',
            'route_arrival_airport'
        );
    }

    public function landings(): HasMany
    {
        return $this->hasMany(
            FromNLanding::class
        );
    }

    public function airport(): HasOneThrough
    {
        return $this->hasOneThrough(
            Airport::class,
            FromNLanding::class,
            'form_n_id_pakus',
            'AIRPORTS_ID',
            'id_pakus',
            'arrivial_point_AIRPORT_ID'
        );
    }

    public function landingStatus(): HasOneThrough
    {
        return $this->hasOneThrough(
            LandingStatus::class,
            FromNLanding::class,
            'form_n_id_pakus',
            'id',
            'id_pakus',
            'landing_status_id'
        );
    }

    public function landingType(): HasOneThrough
    {
        return $this->hasOneThrough(
            LandingType::class,
            FromNLanding::class,
            'form_n_id_pakus',
            'id',
            'id_pakus',
            'landing_type_id'
        );
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(
            From::class
        );
    }

    public function routeEntryExitPoints(): HasMany
    {
        return $this->hasMany(
            RouteEntryExitPoint::class,
            'forms_n_id_pakus'
        );
    }

    public function point(): HasOneThrough
    {
        return $this->hasOneThrough(
            Point::class,
            RouteEntryExitPoint::class,
            'forms_n_id_pakus',
            'POINTS_ID',
            'id_pakus',
            'POINTS_POINTS_ID'
        );
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(
            Status::class,
            'id',
            'status_id'
        );
    }

    public function airlines(): HasMany
    {
        return $this->hasMany(
            Airline::class,
            'AIRLINES_ID',
            'airline_id'
        );
    }

    public function files(): HasManyThrough
    {
        return $this->hasManyThrough(
            File::class,
            From::class,
            'id_from',
            'id_from',
            'from_id_from',
            'id_from'
        );
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            From::class,
            'id_from',
            'id',
            'from_id_from',
            'author_id'
        );
    }

    public function flights(): HasMany
    {
        return $this->hasMany(
            NFormFlight::class,
            'id_pakus',
            'id_pakus'
        );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(
            NFormComment::class,
            'id_pakus',
            'id_pakus'
        )->with('childComments');
    }

    /* --------------- Other --------------- */



}
