<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NFormFileComment extends Model
{
    use HasFactory;
    public $table = 'file_n_form_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'file_id',
        'n_form_comment_id'
    ];


    /* ---------- Relations ---------- */
}
