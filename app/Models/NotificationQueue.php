<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationQueue extends Model
{
    use HasFactory;

    protected $table = 'notification_queue';

    protected $fillable = [
        'event_type',
        'recipient_email',
        'recipient_name',
        'subject',
        'body',
        'context',
        'attachments',
        'model_type',
        'model_id',
        'status',
        'attempts',
        'max_attempts',
        'processing_since',
        'error',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'context' => 'array',
        'attachments' => 'array',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'model_id' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'processing_since' => 'datetime',
    ];
}
