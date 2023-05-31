<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonInfoContact extends Model
{
    use HasFactory;

    protected $table = 'person_info_contacts';

    protected $primaryKey = 'person_info_contacts_id';

    protected $fillable = [
        'akvs_person_info_id',
        'contact_type',
        'cbd_id',
        'value',
        'is_main',
    ];
}
