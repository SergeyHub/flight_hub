<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organiz extends Model
{
    protected $table = 'ORGANIZ';

    protected $primaryKey = 'ORGANIZ_ID';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'ORGANIZ_ID',
        'ADR1RUS',
        'ADR2RUS',
        'MAIL',
        'TELEX',
        'ATEL',
        'INTERNET',
        'INN',
        'KPP',
        'OKONH',
        'OKPO',
        'UPDATEDATE',
    ];
}
