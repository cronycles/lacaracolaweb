<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;
    private string $apiUrl;

    public function __construct()
    {
        $this->token  = (string) config('services.telegram.token');
        $this->apiUrl = rtrim((string) config('services.telegram.api_url', 'https://api.telegram.org/bot'), '/');
    }

    /**
     * Send a plain-text message to a single Telegram chat.
     */
    public function sendMessage(int|string $chatId, string $text): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}{$this->token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => $text,
            ]);

            if (! $response->successful()) {
                Log::channel('telegram')->error('Telegram sendMessage failed', [
                    'chat_id' => $chatId,
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (ConnectionException $e) {
            Log::channel('telegram')->error('Telegram sendMessage connection error', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a message to all users that have a telegram_chat_id configured.
     */
    public function sendToAllRecipients(string $text): void
    {
        $chatIds = User::whereNotNull('telegram_chat_id')->pluck('telegram_chat_id');

        if ($chatIds->isEmpty()) {
            Log::channel('telegram')->warning('sendToAllRecipients: no recipients configured');

            return;
        }

        foreach ($chatIds as $chatId) {
            $this->sendMessage($chatId, $text);
        }
    }
}
