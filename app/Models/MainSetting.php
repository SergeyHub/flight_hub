<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MainSetting extends Model
{
    public $table = 'main_settings';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /** Relations */

    public function main_setting_parameters(): HasMany
    {
        return $this->hasMany(
            MainSettingParameter::class, 'main_setting_id', 'id'
        );
    }

}
