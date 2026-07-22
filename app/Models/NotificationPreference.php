<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id', 'event_type', 'email', 'push', 'in_app',
    ];

    protected $casts = [
        'email' => 'boolean',
        'push' => 'boolean',
        'in_app' => 'boolean',
    ];
}
