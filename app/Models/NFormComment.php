<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int n_form_comment_id
 * @property mixed created_at
 */
class NFormComment extends Model
{
    protected $table = 'n_form_comments';

    protected $primaryKey = 'n_form_comment_id';

    protected $casts = [
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $timestamps = true;

    protected $fillable = [
        'n_form_comment_id',
        'n_forms_id',
        'id_pakus',
        'user_id',
        'parent_comment_id',
        'n_form_object_type',
        'n_form_object_id',
        'comment_type_id',
        'text',
        'create_at_version',
        'delete_at_version'
    ];

    /* relations */
    public function author(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(
            CommentType::class,
            'comment_type_id',
            'comment_type_id'
        );
    }

    public function childComments(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_comment_id',
            'n_form_comment_id',
        );
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            File::class,
            'file_n_form_comments',
            'n_form_comment_id',
            'file_id'
        );
    }

    public function nForm(): BelongsTo
    {
        return $this->belongsTo(
            NForm::class,
            'id_pakus',
            'id_pakus'
        );
    }
}
