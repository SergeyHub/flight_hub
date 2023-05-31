<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NFormAlternativePoint extends Model
{
    use HasFactory;

    protected $table = 'n_form_alternative_points';

    protected $primaryKey = 'n_form_alternative_points_id';

    protected $fillable = [
        'n_form_points_id',
        'POINTS_ID',
        'icao',
        'name',
        'is_found_point',
        'is_coordinates',
        'ISINOUT',
        'ISGATEWAY',
        'coordinates',
    ];
}
