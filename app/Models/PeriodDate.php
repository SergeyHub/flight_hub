<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PeriodDate extends Model
{
    use HasFactory;

    protected $table = 'period_dates';

    protected $primaryKey = 'period_date_id';

    protected $fillable = [
        'n_form_flight_id',
        'start_date',
        'end_date',
        'days_of_week',
        'days_of_week_objects'
    ];

    protected $hidden = [
        'pivot',
    ];

    /* relations */

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form_period_dates',
            'period_date_id',
            'file_id'
        );
    }

    /* accessors */

    /**
     * Accessor for cast string like "[1,2,3]" into array
     *
     * @param $value
     * @return false|string[]
     */
    public function getDaysOfWeekAttribute($value)
    {
        $value = json_decode($value);

        if (is_array($value)) {
            return $value;
        }

        $valuesArray = explode(',', str_replace(['[', ']'], '', $value));

        return array_map('intval', $valuesArray);
    }
}
