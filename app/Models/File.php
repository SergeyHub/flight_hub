<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $table = 'files';

    protected $primaryKey = 'id';

    protected $fillable = [
        'owner_id',
        'file_type_id',
        'file_type_name',
        'filename',
        'path',
        'date_start',
        'date_end',
        'other_attributes_json',
        'time'
    ];

    protected $hidden = [
        'laravel_through_key',
        'updated_at',
        'date_start',
        'date_end',
        'time',
        'pivot'
    ];


    /* ---------- Relations ---------- */
}
