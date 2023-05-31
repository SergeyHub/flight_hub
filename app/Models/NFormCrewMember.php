<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NFormCrewMember extends Model
{
    use HasFactory;

    protected $table = 'n_form_crew_member';

    protected $primaryKey = 'n_form_crew_member_id';

    protected $fillable = [
        'n_form_crew_id',
        'fio',
        'STATES_ID',
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

    public function documents() :BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form_crew_member',
            'n_form_crew_member_id',
            'file_id'
        );
    }
}
