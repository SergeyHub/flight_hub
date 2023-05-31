<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NFormPassengersPerson extends Model
{
    use HasFactory;

    protected $table = 'n_form_passengers_persons';

    protected $primaryKey = 'n_form_passengers_persons_id';

    protected $fillable = [
        'n_form_passengers_id',
        'fio',
        'STATES_ID',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    /* relations */

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form_passengers_persons',
            'n_form_passengers_persons_id',
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
