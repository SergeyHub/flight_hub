<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Point extends Model
{
    use HasFactory;

    protected $table = 'POINTS';

    protected $primaryKey = 'POINTS_ID';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'POINTS_ID',
        'AIRPORTS_ID',
        'TIMEZONE_ID',
        'FIRS_ID',
        'FACILITY_ID',
        'AIRITEM_ID',
        'ISINAIP',
        'NAMELAT',
        'NAMERUS',
        'LATITUDE',
        'LONGITUDE',
        'MAGNDEVIATION',
        'FREQENCYWORK',
        'FREQENCYTYPE',
        'ISRPTONQRY',
        'ISACP',
        'ISINOUT',
        'ISINOUTSNG',
        'ISGATEWAY',
        'ISTRANSFERPOINT',
        'ISTRANSFERPOINT_APRT',
        'ISMVL',
        'ISINARZ',
        'ISOUTARZ',
        'ISPNTRA',
        'ISPNTAIRWAY',
        'BEGINDATE',
        'ENDDATE',
        'UPDATEDATE',
    ];

    protected $hidden = [
        'laravel_through_key'
    ];

    /* ---------- Relations ---------- */

    public function pnthist(): HasOne
    {
        return $this->hasOne(
            Pnthist::class,
            'POINTS_ID',
            'POINTS_ID',
        );
    }
}
