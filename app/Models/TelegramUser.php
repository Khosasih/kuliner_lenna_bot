<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'last_interaction_at',
    ];

    protected function casts(): array
    {
        return [
            'last_interaction_at' => 'datetime',
        ];
    }
}
