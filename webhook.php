<?php

require_once 'bot.php';

$update = json_decode(file_get_contents('php://input'), true);

try {
    if (isset($update['message'])) {
        $message = $update['message'];
        handleMessage($message);

    } elseif (isset($update['callback_query'])) {
        handleCallbackQuery($update['callback_query']);

    }

} catch (Exception $e) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
}
