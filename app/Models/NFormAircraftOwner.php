<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NFormAircraftOwner extends Model
{
    use HasFactory;

    protected $table = 'n_form_aircraft_owner';

    protected $primaryKey = 'n_form_aircraft_owner_id';

    protected $fillable = [
        'name',
        'full_address',
        'contact',
        'STATES_ID',
    ];

    protected $hidden = [
        'laravel_through_key'
    ];

    /* relations */

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form_aircraft_owner',
            'n_form_aircraft_owner_id',
            'file_id'
        );
    }

    public function state():HasOne
    {
        return $this->hasOne(
            State::class,
            'STATES_ID',
            'STATES_ID',
        );
    }
}
