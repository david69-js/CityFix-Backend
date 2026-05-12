<?php

namespace App\Services;

class FcmService
{
    public static function sendPush($token, $title, $body, $data = [])
    {
        if (!$token || !class_exists('Kreait\\Laravel\\Firebase\\Facades\\Firebase')) {
            return;
        }

        $firebase = \Kreait\Laravel\Firebase\Facades\Firebase::messaging();
        $messaging = $firebase;

        $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
            ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body))
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
