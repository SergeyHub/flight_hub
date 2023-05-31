<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NFormCargo extends Model
{
    use HasFactory;

    protected $table = 'n_form_cargo';

    protected $primaryKey = 'n_form_cargo_id';

    protected $fillable = [
        'n_form_flight_id',
        'type_and_characteristics',
        'cargo_danger_classes_id',
        'weight',
        'charterer_name',
        'charterer_fulladdress',
        'charterer_contact',
        'receiving_party_name',
        'receiving_party_fulladdress',
        'receiving_party_contact',
        'consignor_name',
        'consignor_fulladdress',
        'consignor_contact',
        'consignee_name',
        'consignee_fulladdress',
        'consignee_contact',
        'n_form_cargo_global_id',
    ];

    protected $hidden = [
        'laravel_through_key',
        'created_at',
        'updated_at',
    ];

    /* relations */

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form_cargo',
            'n_form_cargo_id',
            'file_id'
        );
    }
}
