<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUpdate extends Model
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'update_id',
        'update_type',
        'telegram_user_id',
        'status',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
