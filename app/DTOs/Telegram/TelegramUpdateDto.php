<?php

namespace App\DTOs\Telegram;

readonly class TelegramUpdateDto
{
    public function __construct(
        public int $updateId,
        public string $type,
        public ?int $chatId = null,
        public ?int $fromId = null,
        public ?string $fromUsername = null,
        public ?string $text = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $callbackData = null,
        public array $raw = []
    ) {}

    public static function fromPayload(array $payload): self
    {
        $updateId = (int) ($payload['update_id'] ?? 0);
        $callbackQuery = $payload['callback_query'] ?? null;
        $message = $payload['message'] ?? null;

        if ($callbackQuery) {
            return new self(
                updateId: $updateId,
                type: 'callback_query',
                chatId: $callbackQuery['message']['chat']['id'] ?? null,
                fromId: $callbackQuery['from']['id'] ?? null,
                fromUsername: $callbackQuery['from']['username'] ?? null,
                callbackData: $callbackQuery['data'] ?? null,
                raw: $payload
            );
        }

        $type = 'unknown';
        $text = null;
        $lat = null;
        $lng = null;

        if (isset($message['text'])) {
            $type = 'text';
            $text = $message['text'];
        } elseif (isset($message['location'])) {
            $type = 'location';
            $lat = (float) $message['location']['latitude'];
            $lng = (float) $message['location']['longitude'];
        }

        return new self(
            updateId: $updateId,
            type: $type,
            chatId: $message['chat']['id'] ?? null,
            fromId: $message['from']['id'] ?? null,
            fromUsername: $message['from']['username'] ?? null,
            text: $text,
            latitude: $lat,
            longitude: $lng,
            raw: $payload
        );
    }
}
