<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessCode extends Model
{
    protected $fillable = ['code', 'class_name', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
