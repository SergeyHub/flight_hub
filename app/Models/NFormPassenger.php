<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NFormPassenger extends Model
{
    use HasFactory;

    protected $table = 'n_form_passengers';

    protected $primaryKey = 'n_form_passengers_id';

    protected $fillable = [
        'n_form_flight_id',
        'quantity',
    ];

    protected $hidden = [
        'laravel_through_key',
        'created_at',
        'updated_at'
    ];

    /* relations */

    public function passengersPersons(): HasMany
    {
        return $this->hasMany(
            NFormPassengersPerson::class,
            'n_form_passengers_id',
            'n_form_passengers_id',
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
}
