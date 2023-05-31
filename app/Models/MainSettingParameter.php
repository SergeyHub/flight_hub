<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainSettingParameter extends Model
{
    public $table = 'main_setting_parameters';

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /** Relations */

    public function main_setting()
    {
        return $this->belongsTo(
            MainSetting::class, 'main_setting_id', 'id'
        );
    }

    /**  Other functions  */
    public function increasePermitNumber(int $value): void
    {
        self::update(['value' => $value]);
    }

    public function freshPermitNumber(): void
    {
        self::update(['value' => 0]);
    }
}
