<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NFormFlightSign extends Model
{
    use HasFactory;
    protected $table = 'n_form_flight_signs';

    protected $casts = [
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $timestamps = true;

    protected $fillable = [
        'name_rus',
        'name_lat',
        'result_rus',
        'result_lat'
    ];

    public function agreementSigns() : HasMany
    {
        return $this->hasMany(
            NFormFlightAgreementSign::class,
            'n_form_flight_sign_id',
            'id'
        );
    }


}
