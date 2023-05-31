<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int n_form_flight_sign_id
 * @property int role_id
 * @property int sign_id
 */
class NFormFlightAgreementSign extends Model
{
    public const SIGN_REQUEST = 13;
    public const SIGN_ANSWER = 8;

    protected $table = 'n_form_flight_agreement_signs';

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $timestamps = true;

    protected $fillable = [
        'n_form_flight_id',
        'role_id',
        'approval_group_id',
        'n_form_flight_sign_id'
    ];

    /* relations */

    public function approval_group(): BelongsTo
    {
        return $this->belongsTo(
            ApprovalGroup::class,
            'approval_group_id',
            'id'
        );
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(
            NFormFlightStatus::class,
            'n_form_flight_sign_id',
            'id'
        );
    }

    public function sign(): BelongsTo
    {
        return $this->belongsTo(
            NFormFlightSign::class,
            'n_form_flight_sign_id',
            'id'
        );
    }

    public function flights() : BelongsTo
    {
        return $this->belongsTo(
            NFormFlight::class,
            'n_form_flight_id', // Foreign key on the cars table...
            'n_form_flight_id', // Foreign key on the owners table...
        );
    }

    /** Other functions */

    public function saveSign($sign_id, $role_id): void
    {
        $this->n_form_flight_sign_id = $sign_id;
        $this->role_id = $role_id;
        $this->save();
    }

    public function checkAnswerRequestSign(int $sign_id): bool
    {
        return $sign_id === self::SIGN_REQUEST || $sign_id === self::SIGN_ANSWER;
    }
}
