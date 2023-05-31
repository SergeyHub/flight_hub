<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fleet extends Model
{
    protected $table = 'FLEET';

    protected $primaryKey = 'FLEET_ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'FLEET_ID',
        'ACFTMOD_ID',
        'REGNO',
        'RSST_TYPE_ID',
        'REGNO_OLD',
        'SERIALNO',
        'REGISTRNO',
        'PRODDATE',
        'TOTALFLIGHTTIME',
        'TOTALFLIGHTYEAR',
        'MAXIMUMWEIGHT',
        'ACFTOWNER',
        'ACFTFACTORY',
        'ISINTERFLIGHT',
        'SPECIALREMARK',
        'ACFTCOMMENT',
        'MAXIMUMWEIGHT_ORG',
        'OBO',
        'ACFTFUNCTION',
        'CERTIFACFTNO',
        'CERTIFACFTENDDATE',
        'REGISTRDATE',
        'BEGINDATE',
        'ENDDATE',
        'ISDELETE',
        'UPDATEDATE',
    ];

    /* ---------- Relations ---------- */

    public function airlflt(): HasOne
    {
        return $this->hasOne(
            Airlflt::class,
            'FLEET_ID',
            'FLEET_ID',
        );
    }

    public function aircraft()
    {
        return $this->hasOneThrough(
            Aircraft::class,
            Acftmod::class,
            'ACFTMOD_ID',
            'AIRCRAFT_ID',
            'ACFTMOD_ID',
            'AIRCRAFT_ID',
        )->with('acfthist', function ($query) {
            $query->select('ACFTHIST.AIRCRAFT_ID','ACFTHIST_ID','ICAOLAT4', 'TACFT_TYPE');
        });
    }

    public function acftmod()
    {
        return $this->hasOne(
            Acftmod::class,
            'ACFTMOD_ID',
            'ACFTMOD_ID',
        );
    }
}
