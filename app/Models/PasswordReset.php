<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $active
 *
 */
class PasswordReset extends Model
{
    public const STATUS_WAIT = 'wait';
    public const STATUS_ACTIVE = 'active';

    public $table = 'password_resets';

    protected $fillable = [
        'status',
        'email',
        'token',
    ];


    public function isWait(): bool // user status
    {
        return $this->active === self::STATUS_WAIT;
    }
    public function isActive(): bool // user status
    {
        return $this->active === self::STATUS_ACTIVE;
    }
}
