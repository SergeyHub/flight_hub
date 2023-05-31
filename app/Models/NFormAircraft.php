<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NFormAircraft extends Model
{
    use HasFactory;

    protected $table = 'n_form_aircrafts';

    protected $primaryKey = 'n_form_aircrafts_id';

    protected $fillable = [
        'n_forms_id',
        'FLEET_ID',
        'is_main',
        'registration_number',
        'aircraft_type_icao', //Тип ВС
        'aircraft_model', //Модель
        'max_takeoff_weight',
        'max_landing_weight',
        'empty_equip_weight',
        'n_form_aircraft_owner_id',
        'n_form_aircrafts_global_id',
        'CRUISINGSPEED',
        'tacft_type', //Тип ВС из ТС
        'passenger_count',
        'akvs_fleet_id',
        'fleet_lock'
    ];

    /* relations */

    public function aircraftOwner(): HasOne
    {
        return $this->hasOne(
            NFormAircraftOwner::class,
            'n_form_aircraft_owner_id',
            'n_form_aircraft_owner_id'
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
            });
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form_aircrafts',
            'n_form_aircrafts_id',
            'file_id'
        );
    }

    public function parameters(): HasOne
    {
        return $this->hasOne(
            NFormAircraft::class,
            'n_form_aircrafts_id',
            'n_form_aircrafts_id'
        );
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(
            Fleet::class,
            'FLEET_ID',
            'FLEET_ID'
        );
    }
}
