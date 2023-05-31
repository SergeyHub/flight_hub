<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NFormAirnavPayer extends Model
{
    use HasFactory;

    protected $table = 'n_form_airnav_payer';

    protected $primaryKey = 'n_form_airnav_payer_id';

    protected $fillable = [
        'n_forms_id',
        'contact_person',
        'fio',
        'organization',
        'tel',
        'email',
        'aftn',
        'address',
        'remarks',
        'is_paid',

    ];
}
