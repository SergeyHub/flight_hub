<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NFormCrewGroup extends Model
{
    use HasFactory;

    protected $table = 'n_form_crew_group';

    protected $primaryKey = 'n_form_crew_group_id';

    protected $fillable = [
        'n_form_crew_id',
        'quantity',
        'STATES_ID',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    /* relations */

    public function state() :HasOne
    {
        return $this->hasOne(
            State::class,
            'STATES_ID',
            'STATES_ID',
        );
    }
}
