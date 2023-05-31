<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Limaprtmw extends Model
{
    use HasFactory;

    protected $table = 'LIMAPRTMW';

    protected $primaryKey = 'LIMAPRTMW_ID';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'LIMAPRTMW_ID',
        'AIRPORTS_ID',
        'APRTVS_ID'
    ];
}
