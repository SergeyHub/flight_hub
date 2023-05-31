<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NFormPoint extends Model
{
    use HasFactory;

    protected $table = 'n_form_points';

    protected $primaryKey = 'n_form_points_id';

    protected $fillable = [
        'n_form_flight_id',
        'POINTS_ID',
        'is_found_point',
        'icao',
        'coordinates',
        'is_coordinates',
        'is_rf_border',
        'ISINOUT',
        'time',
        'name',
        'departure_time_error',
        'landing_time_error',
    ];

    /* relations */

    public function altPoints(): HasMany
    {
        return $this->hasMany(
            NFormAlternativePoint::class,
            'n_form_points_id',
            'n_form_points_id',
        );
    }

    /* accessors */

    /**
     * Accessor for change time format from 03:28:45 to 0328
     *
     * @param $value
     * @return false|string
     */
    public function getTimeAttribute($value)
    {
        $value = str_replace(':', '', $value);

        return substr($value, 0, 4);
    }
}
