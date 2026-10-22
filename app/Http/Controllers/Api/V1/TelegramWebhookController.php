<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Telegram\TelegramUpdateDto;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramBotService $telegramBotService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $secret = config('telegram.webhook_secret');
        if (!empty($secret) && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return ApiResponse::error('Unauthorized webhook call', 401);
        }
        $dto = TelegramUpdateDto::fromPayload($request->all());

        $this->telegramBotService->handleWebhookUpdate($dto);

        return ApiResponse::success(null, 'Update processed successfully');
    }
}
