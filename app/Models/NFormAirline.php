<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NFormAirline extends Model
{
    use HasFactory;

    protected $table = 'n_form_airlines';

    protected $primaryKey = 'n_form_airlines_id';

    protected $fillable = [
        'n_forms_id',
        'AIRLINES_ID',
        'STATES_ID',
        'airline_represent_id',
        'russia_represent_id',
        'AIRLINE_ICAO',
        'airline_namelat',
        'airline_namerus',
        'ano_is_paid',
        'akvs_airlines_id',
        'airline_lock'
    ];

    /* relations */
    public function state(): HasOne
    {
        return $this->hasOne(
            State::class,
            'STATES_ID',
            'STATES_ID',
        );
    }

    public function airlineRepresent(): HasOne
    {
        return $this->hasOne(
            PersonInfo::class,
            'person_info_id',
            'airline_represent_id'
        );
    }

    public function russiaRepresent(): HasOne
    {
        return $this->hasOne(
            PersonInfo::class,
            'person_info_id',
            'russia_represent_id'
        );
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form_airlines',
            'n_form_airlines_id',
            'file_id',
        );
    }
}
