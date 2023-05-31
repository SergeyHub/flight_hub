<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use App\Http\Controllers\Api\V1\Classes\Headers;
use App\Http\Controllers\Api\V1\NFormController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @property mixed comments
 * @property mixed flights
 * @property int n_forms_id
 * @property int id_pakus
 * @property int version
 * @property int is_latest
 * @property int author_id
 * @property int|null source_id
 * @property string permit_num
 * @property Carbon|null original_created_at
 * @method static find($value)
 * @method static create($data)
 */
class NForm extends Model
{
    use HasFactory;

    protected $table = 'n_forms';

    protected $primaryKey = 'n_forms_id';

    protected $fillable = [
        'id_pakus',
        'id_pp',
        'version',
        'story_details_rus',
        'story_details_lat',
        'author_id',
        'source_id',
        'taken_by_id',
        'take_time',
        'form_status_id',
        'is_entire_fleet',
        'remarks',
        'comment',
        'permit_num',
        'selected_role_id',
        'is_latest',
        'original_created_at',
        'is_archive',
        'is_archive_gc'
    ];

    protected $appends = [
        'is_favorite',
        'max_version'
    ];

    /* relations */

    public function nFormAircraft(): HasMany
    {
        return $this->hasMany(
            NFormAircraft::class,
            'n_forms_id',
            'n_forms_id'
        );
    }

    public function aircrafts(): HasMany
    {
        return $this->hasMany(
            NFormAircraft::class,
            'n_forms_id',
            'n_forms_id'
        )
            ->with('parameters', function ($query) {
                $query->select([
                    'n_form_aircrafts_id',
                    'max_takeoff_weight',
                    'max_landing_weight',
                    'empty_equip_weight',
                ]);
            })
            ->with('documents', function ($query) {
                $query->select([
                    'id as document_id',
                    'file_type_id',
                    'file_type_name',
                    'filename as file_name',
                    'path as file_path',
                    'created_at',
                    'other_attributes_json as required_attributes_json',
                ]);
            })
            ->with('aircraftOwner', function ($query) {
                $query->select([
                    'n_form_aircraft_owner_id',
                    'name',
                    'full_address',
                    'contact',
                    'STATES_ID',
                ]);
            });
    }

    public function flights(): HasMany
    {
        return $this->hasMany(
            NFormFlight::class,
            'n_forms_id',
            'n_forms_id'
        )
            ->with('flightInformation', function ($query) {
                $query->select([
                    'n_form_flight_id',
                    'status_change_datetime',
                    'flight_num',
                    'purpose as purpose_is_commercial',
                    'transportation_categories_id',
                    'is_found_departure_airport',
                    'departure_airport_id',
                    'departure_airport_icao',
                    'departure_airport_namelat',
                    'departure_airport_namerus',
                    'departure_platform_name',
                    'departure_platform_coordinates',
                    'departure_time',
                    'is_found_landing_airport',
                    'landing_airport_id',
                    'landing_airport_icao',
                    'landing_airport_namelat',
                    'landing_airport_namerus',
                    'landing_platform_name',
                    'landing_platform_coordinates',
                    'landing_time',
                    'landing_type',
                ]);
            })
            ->with('mainDate')
            ->with('otherDates')
            ->with('periodDates')
            ->with('datesDocuments', function ($query) {
                $query->select([
                    'id as document_id',
                    'file_type_id',
                    'file_type_name',
                    'filename as file_name',
                    'path as file_path',
                    'created_at',
                    'other_attributes_json as required_attributes_json',
                ]);
            })
            ->with('points', function ($query) {
                $query->select([
                    'n_form_flight_id',
                    'n_form_points_id',
                    'name',
                    'is_found_point',
                    'is_coordinates',
                    'departure_time_error',
                    'landing_time_error',
                    'POINTS_ID',
                    'is_rf_border as ISGATEWAY',
                    'ISINOUT',
                    'icao',
                    'time',
                    'coordinates',
                    'name',
                ]);
            })
            ->with('crew')
            ->with('passengers')
            ->with('cargos', function ($query) {
                $query->select([
                    'n_form_cargo_id',
                    'n_form_cargo_global_id',
                    'n_form_flight_id',
                    'type_and_characteristics',
                    'cargo_danger_classes_id',
                    'weight',
                    'charterer_name as cargo_charterer',
                    'charterer_fulladdress as cargo_charterer_fulladdress',
                    'charterer_contact as cargo_charterer_phone',
                    'receiving_party_name as receiving_party',
                    'receiving_party_fulladdress',
                    'receiving_party_contact as receiving_party_phone',
                    'consignor_name as consignor',
                    'consignor_fulladdress',
                    'consignor_contact as consignor_phone',
                    'consignee_name as consignee',
                    'consignee_fulladdress',
                    'consignee_contact as consignee_phone',
                ]);
            })
            ->withSum('cargos', 'weight')
            ->with('status', function ($query) {
                $query->select(['id', 'name_rus', 'name_lat']);
            });
    }

    public function histories(): HasMany
    {
        return $this->hasMany(
            NFormFlightNFormFlightStatus::class,
            'id_pakus',
            'id_pakus',
        );
    }

    public function transportationCategories(): HasManyThrough
    {
        return $this->hasManyThrough(
            FlightCategory::class,
            NFormFlight::class,
            'n_forms_id',
            'CATEGORIES_ID',
            'n_forms_id',
            'transportation_categories_id'
        );
    }

    public function airnavPayer(): HasOne
    {
        return $this->hasOne(
            NFormAirnavPayer::class,
            'n_forms_id',
            'n_forms_id',
        );
    }

    public function departureDates(): HasManyThrough
    {
        return $this->hasManyThrough(
            DepartureDate::class,
            NFormFlight::class,
            'n_forms_id',
            'n_form_flight_id',
            'n_forms_id',
            'n_form_flight_id'
        );
    }

    public function passengersQuantity(): HasOneThrough
    {
        return $this->hasOneThrough(
            NFormPassenger::class,
            NFormFlight::class,
            'n_forms_id',
            'n_form_flight_id',
            'n_forms_id',
            'n_form_flight_id',
        );
    }

    public function weight(): HasManyThrough
    {
        return $this->hasManyThrough(
            NFormCargo::class,
            NFormFlight::class,
            'n_forms_id',
            'n_form_flight_id',
            'n_forms_id',
            'n_form_flight_id',
        );
    }

    public function airline(): HasOne
    {
        return $this->hasOne(
            NFormAirline::class,
            'n_forms_id',
            'n_forms_id',
        )
            ->with('state', function ($query) {
                $query->select([
                    'STATES_ID',
                    'NAMELAT as state_namelat',
                    'NAMERUS as state_namerus',
                ]);
            })
            ->with('documents', function ($query) {
                $query->select([
                    'id as document_id',
                    'file_type_id',
                    'file_type_name',
                    'filename as file_name',
                    'path as file_path',
                    'created_at',
                    'other_attributes_json as required_attributes_json',
                ]);
            })
            ->with('airlineRepresent')
            ->with('russiaRepresent');
    }

    public function airlhists(): HasManyThrough
    {
        return $this->hasManyThrough(
            Airlhist::class,
            NFormAirline::class,
            'n_forms_id',
            'AIRLINES_ID',
            'n_forms_id',
            'AIRLINES_ID',
        );
    }

    public function departureAirport(): HasOneThrough
    {
        return $this->hasOneThrough(
            Aprthist::class,
            NFormFlight::class,
            'n_forms_id',
            'AIRPORTS_ID',
            'n_forms_id',
            'departure_airport_id',
        );
    }

    public function fullNameAirlines(): HasOneThrough
    {
        return $this->hasOneThrough(
            Airline::class,
            NFormAirline::class,
            'n_forms_id',
            'AIRLINES_ID',
            'n_forms_id',
            'AIRLINES_ID',
        );
    }

    public function aircraftLandingType(): HasMany
    {
        return $this->HasMany(
            NFormFlight::class,
            'n_forms_id',
            'n_forms_id',
        );
    }

    public function landingAirport(): HasOneThrough
    {
        return $this->hasOneThrough(
            Aprthist::class,
            NFormFlight::class,
            'n_forms_id',
            'AIRPORTS_ID',
            'n_forms_id',
            'landing_airport_id',
        );
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form',
            'n_forms_id',
            'file_id'
        )
            ->select([
                'id as document_id',
                'file_type_id',
                'file_type_name',
                'filename as file_name',
                'path as file_path',
                'created_at',
                'other_attributes_json',
            ]);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(
            NFormComment::class,
            'id_pakus',
            'id_pakus'
        );
    }

    /* accessors */

    public function getIsFavoriteAttribute(): int
    {
        $isFavorite = 0;
        $idPakus = $this->id_pakus;
        $userId = \Auth::id();

        if ($userId !== null) {
            $rawQuery = \DB::select("SELECT COUNT (*) FROM favorites_user_n_form WHERE user_id = ? AND id_pakus = ?", [$userId, $idPakus]);

            if ($rawQuery[0]->count > 0) {
                $isFavorite = 1;
            }
        }

        return $isFavorite;
    }

    public function getMaxVersionAttribute()
    {
        $rawQuery = \DB::select("SELECT MAX(VERSION) FROM n_forms WHERE id_pakus = ?", [$this->id_pakus]);

        return $rawQuery[0]->max;
    }
}
