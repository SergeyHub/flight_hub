<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteUserNForm extends Model
{
    use HasFactory;

    protected $table = 'favorites_user_n_form';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'id_pakus'
    ];
}
