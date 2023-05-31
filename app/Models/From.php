<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class From extends Model
{
    use HasFactory;

    protected $table = 'froms';

    protected $primaryKey = 'id_from';

    protected $fillable = [
        'is_create_inside',
        'author_id',
        'create_datetime',
        'taken_by_user_id',
    ];

    /* ---------- Relations ---------- */

    public function user()
    {
        return $this->hasOne(
            User::class,
            'id',
            'author_id',
        );
    }

    public function formN()
    {
        return $this->belongsTo(
            FormN::class,
            'id_from',
            'from_id_from',

        );
    }

    public function files()
    {
        return $this->belongsTo(
            File::class,
            'id_from',
            'id_from'
        );
    }
}
