<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileNFormCargo extends Model
{
    use HasFactory;

    protected $table = 'file_n_form_cargo';

    protected $primaryKey = 'file_id';

    public $timestamps = false;

    protected $fillable = [
        'n_form_cargo_id'
    ];
}
