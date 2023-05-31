<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'route',
        'title',
        'meta_d',
        'meta_k',
        'content'
    ];


    /* ---------- Relations ---------- */

    /* Accessors & Mutators */
    public function setMeta ($to)
    {
        $this->title  = $to->title;
        $this->meta_d = $to->meta_d;
        $this->meta_k = $to->meta_k;
    }

    public function getH1Attribute ($h1)
    {
        return $h1 ?: $this->title;
    }

    /* Accessors & Mutators (save) */

    /* Query Scopes */
    public function scopeSettings ($query, $route)
    {
        return $query->where('route', $route)
            ->firstOrFail();
    }
}
