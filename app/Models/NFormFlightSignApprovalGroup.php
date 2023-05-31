<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NFormFlightSignApprovalGroup extends Model
{
    use HasFactory;
    public $table = 'n_form_flight_sign_approval_group';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'approval_group_id',
        'n_form_flight_sign_id'
    ];


    /* ---------- Relations ---------- */

}
