<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'created_at',
        'class_name',
        'telegram_id',
        'type',
        'category',
        'text',
        'urgency',
    ];

    public $timestamps = false;
}
