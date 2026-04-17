<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FcmService
{
    public static function sendPush($token, $title, $body, $data = [])
    {
        if (!$token) return;

        $messaging = Firebase::messaging();

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $messaging->send($message);
        } catch (\Exception $e) {
            \Log::error("FCM Error: " . $e->getMessage());
        }
    }

    public static function sendToMultiple($tokens, $title, $body, $data = [])
    {
        $validTokens = array_filter($tokens);
        if (empty($validTokens)) return;

        foreach ($validTokens as $token) {
            self::sendPush($token, $title, $body, $data);
        }
    }
}
