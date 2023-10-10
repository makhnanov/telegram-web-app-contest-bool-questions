<?php

use Dotenv\Dotenv;
use TelegramBot\Api\BotApi;

require __DIR__ . '/vendor/autoload.php';

Dotenv::createUnsafeMutable(__DIR__)->load();
$token = getenv('TOKEN');

$bot = new BotApi($token);
$latestUpdate = 0;

do {
    try {
        foreach ($bot->getUpdates($latestUpdate, 100, 8) as $update) {
            /** @var \TelegramBot\Api\Types\Update $update */
            $latestUpdate = $update->getUpdateId();
            $latestUpdate++;
            try {
                $message = $update->getMessage();
                $bot->sendMessage($message->getChat()->getId(), <<<TEXT
Welcome to The Bool Questions!
You can Answer Yes or No or Ask any Question!

For continue, please, open Web App 
via Menu Button with "Ask / Answer" text.
TEXT
);
            } catch (Throwable $throwable) {
                // Silence
            }
        }
    } catch (Throwable $e) {
        // Silence
    }
    sleep(5);
} while (true);
