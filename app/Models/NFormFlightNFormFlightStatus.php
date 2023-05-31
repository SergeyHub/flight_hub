<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NFormFlightNFormFlightStatus extends Model
{
    public const ROLE_RA = 5; // Roles - Начальник РА
    public const STATUS_ANSWER = 8; // Ответ
    public const STATUS_CANCELED = 10; // Отклонено
    public const STATUS_APPROVED = 11; // Утверждено
    public const STATUS_ADJUSTED = 12; // Запрос информации

    protected $table = 'n_form_flight_n_form_flight_status';

    public $timestamps = false;

    protected $fillable = [
        'id_pakus',
        'version',
        'n_form_flight_id',
        'n_form_flight_status_id',
        'role_id', // User active_role_id
        'comment_id',
        'request_common_info',
        'created_at',
        'updated_at'
    ];

    //********* Relations ********//

    public function flight(): HasOne
    {
        return $this->hasOne(
            NFormFlight::class,
            'n_form_flight_id',
            'n_form_flight_id'
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
                    'departure_platform_coordinates',
                    'departure_time',
                    'is_found_landing_airport',
                    'landing_airport_id',
                    'landing_airport_icao',
                    'landing_airport_namelat',
                    'landing_airport_namerus',
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
            ->with('cargos', function ($query){
                $query->select([
                    'n_form_cargo_id',
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
            ->withSum('cargos','weight')
            ->with('status', function ($query) {
                $query->select(['id', 'name_rus', 'name_lat']);
            });
    }

    public function flights(): BelongsTo
    {
        return $this->belongsTo(
            NFormFlight::class,
            'n_form_flight_id',
            'n_form_flight_id'
        );
    }

    // active_role
    public function role(): BelongsTo
    {
        return $this->belongsTo(
            Role::class,
            'role_id',
            'id'
        );
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(
            NFormComment::class,
            'comment_id',
            'n_form_comment_id'
        );
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(
            NFormFlightStatus::class,
            'n_form_flight_status_id',
            'id'
        )->orderBy('id');
    }

    public static function statusComment(int $status_id): bool
    {
        return $status_id === self::STATUS_ANSWER || $status_id === self::STATUS_ADJUSTED;
    }

    public static function lastSavedRoleStatus($id_pakus, $role_id)
    {
        return static::where('role_id', $role_id)
            ->where('id_pakus', $id_pakus)
            ->latest()
            ->first();
    }
}
