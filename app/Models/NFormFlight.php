<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property mixed comments
 * @property mixed status_id
 */
class NFormFlight extends Model
{
    use HasFactory;

    public const ROLE_RA = 5; // Roles - Начальник РА
    public const STATUS_ANSWER = 8; // Ответ
    public const STATUS_CANCELED = 10; // Отклонено
    public const STATUS_APPROVED = 11; // Утверждено
    public const STATUS_ADJUSTED = 12; // Запрос информации

    protected $table = 'n_form_flight';

    protected $primaryKey = 'n_form_flight_id';

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'status_change_datetime' => 'datetime:Y-m-d H:i:s',
    ];

    public $timestamps = true;

    protected $fillable = [
        'n_forms_id',
        'flight_num',
        'purpose',
        'transportation_categories_id',
        'departure_airport_id',
        'is_found_departure_airport',
        'departure_platform_name',
        'departure_platform_coordinates',
        'departure_time',
        'landing_airport_id',
        'is_found_landing_airport',
        'landing_platform_name',
        'landing_platform_coordinates',
        'landing_time',
        'landing_type',
        'status',
        'update_datetime',
        'departure_airport_icao',
        'departure_airport_namelat',
        'departure_airport_namerus',
        'landing_airport_icao',
        'landing_airport_namelat',
        'landing_airport_namerus',
        'status_id',
        'status_change_datetime',
        'dates_is_repeat',
        'dates_or_periods',
        'n_form_flight_global_id',
    ];

    /* relations */

    public function crew(): HasOne
    {
        return $this->hasOne(
            NFormCrew::class,
            'n_form_flight_id',
            'n_form_flight_id',
        )
            ->with('crewMembers')
            ->with('crewGroups')
            ->withCount('crewMembers as crew_members_count')
            ->withSum('crewGroups as crew_groups_sum_quantity', 'quantity');
    }

    public function departureAirport(): HasMany
    {
        return $this->hasMany(
            Aprthist::class,
            'AIRPORTS_ID',
            'departure_airport_id'
        );
    }

    public function landingAirport(): HasMany
    {
        return $this->hasMany(
            Aprthist::class,
            'AIRPORTS_ID',
            'landing_airport_id'
        );
    }

    public function points(): HasMany
    {
        return $this->hasMany(
            NFormPoint::class,
            'n_form_flight_id',
            'n_form_flight_id',
        )
            ->with('altPoints');
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(
            NFormCargo::class,
            'n_form_flight_id',
            'n_form_flight_id',
        )
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
            });
    }

    public function passengers(): HasOne
    {
        return $this->hasOne(
            NFormPassenger::class,
            'n_form_flight_id',
            'n_form_flight_id',
        )
            ->with('passengersPersons')
            ->withCount('passengersPersons as passengers_count');
    }

    public function status(): HasOne
    {
        return $this->hasOne(
            NFormFlightStatus::class,
            'id',
            'status_id'
        );
    }

    public function mainDate(): HasOne
    {
        return $this->hasOne(
            DepartureDate::class,
            'n_form_flight_id',
            'n_form_flight_id',
        )
            ->where('is_main_date', 1)
            ->select([
                'n_form_flight_id',
                'departure_dates_id',
                'is_main_date',
                'date',
                'landing_date',
                'is_required_dep_slot',
                'dep_slot_id',
                'is_required_land_slot',
                'land_slot_id',
                'from_period',
                'period_date_id',
            ])
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
            });
    }

    public function otherDates(): HasMany
    {
        return $this->hasMany(
            DepartureDate::class,
            'n_form_flight_id',
            'n_form_flight_id',
        )
            ->where('is_main_date', 0)
            ->select([
                'n_form_flight_id',
                'departure_dates_id',
                'date',
                'landing_date',
                'is_required_dep_slot',
                'dep_slot_id',
                'is_required_land_slot',
                'land_slot_id',
                'from_period',
                'period_date_id',
            ]);
    }

    public function datesDocuments(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form_flight',
            'n_form_flight_id',
            'file_id',
        );
    }

    public function flightInformation(): HasOne
    {
        return $this->hasOne(
            NFormFlight::class,
            'n_form_flight_id',
            'n_form_flight_id',
        );
    }

    public function periodDates(): HasMany
    {
        return $this->hasMany(
            PeriodDate::class,
            'n_form_flight_id',
            'n_form_flight_id'
        )
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
            });
    }

    public function nForm(): BelongsTo
    {
        return $this->belongsTo(
            NForm::class,
            'n_forms_id',
            'n_forms_id'
        );
    }

    public function flightHistories(): BelongsToMany
    {
        return $this->belongsToMany(
            NFormFlightStatus::class,
            'n_form_flight_n_form_flight_status',
            'n_form_flight_id',
            'n_form_flight_status_id',
            'n_form_flight_id',
            'id',
        )
            ->withTimestamps()
            ->withPivot('id')
            ->withPivot('role_id')
            ->withPivot('comment_id')
            ->orderByPivot('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(
            NFormComment::class,
            'n_forms_id',
            'n_forms_id'
        );
    }

    // TODO check for it works
    /* from laravelSD */
    //replace table
    public function transportationCategory(): BelongsTo
    {
        return $this->belongsTo(
            FlightCategory::class,
            'transportation_categories_id',
            'CATEGORIES_ID'
        );
    }

    public function departureDates(): HasMany
    {
        return $this->hasMany(
            DepartureDate::class,
            'n_form_flight_id',
            'n_form_flight_id'
        );
    }

    public function cargo(): HasMany
    {
        return $this->hasMany(
            NFormCargo::class,
            'n_form_flight_id',
            'n_form_flight_id'
        );
    }

    public function whoChangedStatus(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_who_changed_status_id',
            'id'
        );
    }

    public function agreementSigns(): HasMany
    {
        return $this->hasMany(
            NFormFlightAgreementSign::class,
            'n_form_flight_id',
            'n_form_flight_id'
        )->with('sign');
    }

    public function updateStatus($status_text, $status_id): void
    {
        $this->update([
            'status' => $status_text,
            'status_id' => $status_id,
            'status_change_datetime' => now()
        ]);
    }

    /* accessors */

    public function getDepartureTimeAttribute($value)
    {
        if ($value === null) return null;

        return substr($value, 0, 5);
    }

    public function getLandingTimeAttribute($value)
    {
        if ($value === null) return null;

        return substr($value, 0, 5);
    }

    /** Other classes */

    public static function lastSavedStatus($n_form_flight_global_id)
    {
        return static::where('n_form_flight_global_id', $n_form_flight_global_id)
            ->latest()
            ->first();
    }

    public static function finalRAStatus(int $n_form_flight_global_id)
    {
        return static::where('n_form_flight_global_id', $n_form_flight_global_id)
            ->where(function ($query)
            {
                $query->where('status_id', self::STATUS_APPROVED)->orWhere('status_id', self::STATUS_CANCELED);
            })
            ->latest()
            ->first();
    }
}
