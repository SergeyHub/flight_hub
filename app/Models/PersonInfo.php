<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonInfo extends Model
{
    use HasFactory;

    protected $table = 'person_info';

    protected $primaryKey = 'person_info_id';

    protected $fillable = [
        'fio',
        'position',
        'email',
        'tel',
        'fax',
        'sita',
        'aftn',
        'akvs_airlines_id',
        'represent_type',
        'EMPLOYEE_ID',
    ];

    public function contacts():HasMany
    {
        return $this->hasMany(
            PersonInfoContact::class,
            'person_info_id',
            'person_info_id'
        );
    }
}
