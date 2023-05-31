<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileAkvsAirline extends Model
{
    use HasFactory;

    protected $table = 'file_akvs_airlines';

    public $timestamps = false;

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = [
        'file_id',
        'akvs_airlines_id',
    ];

    public function files():HasMany
    {
        return $this->hasMany(
            File::class,
            'id',
            'file_id'
        );
    }
}
