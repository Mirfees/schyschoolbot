<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AccessCode extends Model
{
    protected $fillable = ['code', 'class_name', 'is_active', 'linked_user'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user', 'id');
    }
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
