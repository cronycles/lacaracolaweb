<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, string $secret): Response
    {
        // Validate the secret path segment to reject unauthorized requests.
        $expectedSecret = (string) config('telegram.webhook_secret');

        if (! hash_equals($expectedSecret, $secret)) {
            return response('', 403);
        }

        $update = $request->all();

        Log::channel('telegram')->info('Telegram webhook update received', $update);

        // Extract and explicitly log chat_id and text for easy discovery.
        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'] ?? null;
            $text = $update['message']['text'] ?? null;

            Log::channel('telegram')->info('Telegram message', [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        }

        return response('', 200);
    }
}
