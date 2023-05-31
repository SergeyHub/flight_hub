<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileType extends Model
{
    use HasFactory;

    protected $table = 'file_types';

    public $timestamps = false;

    protected $fillable = [
        'name_rus',
        'name_lat',
        'required_attributes_json',
        'doc_object',
    ];
}
