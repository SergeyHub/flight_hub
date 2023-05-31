<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FromNLanding extends Model
{
    use HasFactory;

    protected $table = 'from_n_landing';

    protected $primaryKey = 'idl_pakus';

    protected $fillable = [
        'idl_pakus',
        'form_n_id_pakus',
        'idl_pp',
        'landing_version',
        'landing_status_id',
        'landing_type_id',
        'flight_num',
        'arrivial_point_AIRPORT_ID',
        'arrival_datetime',
        'departure_datetime',
        'flight_purpose_is_commercial',
        'passengers_quantity',
        'cargo_mass',
        'cargo_mass_unit',
        'route_details',
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
        'cargo_charterer_fulladdress_contact',
        'cargo_receiving_party',
        'cargo_receiving_party_fulladdress_contact',
        'main_aircraft_type_icao_id',
        'main_aircraft_registration_mark',
        'main_aircraft_max_takeoff_mass',
        'main_aircraft_max_takeoff_mass_unit',
        'main_aircraft_max_landing_mass',
        'main_aircraft_max_landing_mass_unit',
        'main_aircraft_zero_fuel_weight',
        'main_aircraft_zero_fuel_weight_unit',
        'other_aircrafts_alternative_entire_fleet',
    ];


    /* ---------- Relations ---------- */

    public function airport()
    {
        return $this->hasOne(
            Airport::class,
            'AIRPORTS_ID',
            'arrivial_point_AIRPORT_ID'
        );
    }

    public function landingStatus()
    {
        return $this->hasOne(
            LandingStatus::class,
            'id',
            'landing_status_id'
        );
    }

    public function landingType()
    {
        return $this->hasOne(
            LandingType::class,
            'id',
            'landing_type_id'
        );
    }
}
