<?php

namespace App\Services;

use App\DTOs\Restaurant\SearchRestaurantCriteriaDto;
use App\DTOs\Telegram\TelegramUpdateDto;
use App\Models\TelegramUpdate;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotService
{
    protected string $botToken;
    protected string $baseUrl;

    public function __construct(
        protected RestaurantService $restaurantService
    ) {
        $this->botToken = (string) config('telegram.bot_token', '');
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function handleWebhookUpdate(TelegramUpdateDto $update): void
    {
        // 1. Idempotensi: Cek apakah update_id sudah pernah diproses
        $exists = TelegramUpdate::where('update_id', $update->updateId)->first();
        if ($exists) {
            return;
        }

        $log = TelegramUpdate::create([
            'update_id' => $update->updateId,
            'update_type' => $update->type,
            'telegram_user_id' => $update->fromId,
            'status' => TelegramUpdate::STATUS_RECEIVED,
        ]);

        try {
            if ($update->chatId && $update->fromId) {
                TelegramUser::updateOrCreate(
                    ['telegram_user_id' => $update->fromId],
                    [
                        'chat_id' => $update->chatId,
                        'username' => $update->fromUsername,
                        'last_interaction_at' => now(),
                    ]
                );
            }

            if ($update->type === 'callback_query') {
                $this->handleCallbackQuery($update);
            } elseif ($update->type === 'location') {
                $this->handleLocationSearch($update);
            } elseif ($update->type === 'text') {
                $this->handleTextMessage($update);
            }

            $log->update([
                'status' => TelegramUpdate::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => TelegramUpdate::STATUS_FAILED,
                'error_message' => substr($e->getMessage(), 0, 250),
            ]);
            Log::error('Telegram webhook handling failed: ' . $e->getMessage());
        }
    }

    protected function handleTextMessage(TelegramUpdateDto $update): void
    {
        $text = trim((string) $update->text);

        if ($text === '/start') {
            $msg = "👋 *Selamat Datang di Bot Kuliner!*\n\n"
                . "Fitur yang tersedia:\n"
                . "🔍 *Cari Makanan/Resto*: Ketik langsung nama makanan (misal: `Sate`, `Bakso Solo`, `Nasi Goreng`)\n"
                . "📍 *Cari Resto Terdekat*: Kirimkan koordinat lokasi Anda (Share Location 📎)\n"
                . 'ℹ️ *Bantuan*: Ketik /help';
            $this->sendMessage($update->chatId, $msg);
            return;
        }

        if ($text === '/help') {
            $msg = "📖 *Bantuan Penggunaan Bot Kuliner:*\n\n"
                . "1. Ketik nama kuliner untuk mencari resto & alamat.\n"
                . "2. Tekan tombol *[📋 Menu]* pada hasil untuk melihat daftar menu & harga.\n"
                . '3. Tekan tombol *[⭐ Review]* pada hasil untuk membaca ulasan pelanggan.';
            $this->sendMessage($update->chatId, $msg);
            return;
        }

        // Cari Restoran Berdasarkan Kata Kunci
        $results = $this->restaurantService->search(new SearchRestaurantCriteriaDto(
            query: $text,
            limit: 3
        ));

        if (empty($results)) {
            $this->sendMessage($update->chatId, "Maaf, tidak ditemukan restoran dengan kata kunci \"{$text}\". Silakan coba kata kunci lain.");
            return;
        }

        $this->sendMessage($update->chatId, "🍽️ *Rekomendasi Kuliner untuk \"{$text}\":*");

        foreach ($results as $resto) {
            $rating = $resto->rating ? "⭐ {$resto->rating}" : '⭐ Belum ada rating';
            $caption = "🍴 *{$resto->name}* ({$rating})\n📍 _{$resto->address}_";

            // Tombol Inline Keyboard untuk Menu dan Review
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📋 Lihat Menu', 'callback_data' => "menu:{$resto->id}"],
                        ['text' => '⭐ Baca Review', 'callback_data' => "review:{$resto->id}"],
                    ]
                ]
            ];

            $this->sendMessage($update->chatId, $caption, $inlineKeyboard);
        }
    }

    protected function handleLocationSearch(TelegramUpdateDto $update): void
    {
        $results = $this->restaurantService->search(new SearchRestaurantCriteriaDto(
            latitude: $update->latitude,
            longitude: $update->longitude,
            radius: 2000,
            limit: 3
        ));

        if (empty($results)) {
            $this->sendMessage($update->chatId, 'Tidak ditemukan kuliner dalam radius 2 km dari lokasi Anda.');
            return;
        }

        $this->sendMessage($update->chatId, '📍 *Kuliner Terdekat di Sekitar Anda:*');
        foreach ($results as $resto) {
            $caption = "🍴 *{$resto->name}* (⭐ {$resto->rating})\n📍 _{$resto->address}_";
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📋 Lihat Menu', 'callback_data' => "menu:{$resto->id}"],
                        ['text' => '⭐ Baca Review', 'callback_data' => "review:{$resto->id}"],
                    ]
                ]
            ];
            $this->sendMessage($update->chatId, $caption, $inlineKeyboard);
        }
    }

    protected function handleCallbackQuery(TelegramUpdateDto $update): void
    {
        $data = $update->callbackData;
        if (!$data)
            return;

        [$action, $restoId] = explode(':', $data, 2) + [null, null];

        if ($action === 'menu') {
            $menus = $this->restaurantService->getMenu($restoId);
            $msg = "📋 *Daftar Menu & Harga:*\n\n";
            foreach ($menus as $m) {
                $priceFormatted = number_format($m->price, 0, ',', '.');
                $msg .= "• *{$m->name}* - Rp {$priceFormatted}\n  _{$m->description}_\n";
            }
            $this->sendMessage($update->chatId, $msg);
        } elseif ($action === 'review') {
            $reviews = $this->restaurantService->getReviews($restoId);
            $msg = "⭐ *Ulasan Pelanggan:*\n\n";
            foreach ($reviews as $rev) {
                $msg .= "👤 *{$rev->authorName}* (⭐ {$rev->rating})\n\"{$rev->text}\"\n_{$rev->relativeTime}_\n\n";
            }
            $this->sendMessage($update->chatId, $msg);
        }
    }

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): bool
    {
        if (empty($this->botToken))
            return false;

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $res = Http::timeout(10)->post("{$this->baseUrl}/sendMessage", $payload);
        return $res->successful();
    }
}
