<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pnthist extends Model
{
    protected $table = 'PNTHIST';

    protected $primaryKey = 'PNTHIST_ID';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'PNTHIST_ID',
        'POINTS_ID',
        'ICAOLAT5',
        'ICAORUS5',
        'ICAOLAT6',
        'ICAORUS6',
        'ISMVL',
        'ISACP',
        'ISGATEWAY',
        'LATITUDE',
        'LONGITUDE',
        'MAGNDEVIATION',
        'ISINOUT',
        'ISINOUTSNG',
        'PNTBEGINDATE',
        'ENDDATE',
        'ISDELETE',
        'UPDATEDATE'
    ];
}
