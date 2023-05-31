<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileNFormComment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'n_form_comment_id',
        'file_id'
    ];
}
