<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FcmService
{
    public static function sendPush($token, $title, $body, $data = [])
    {
        if (!$token) {
            return;
        }

        // Expo push tokens → Expo Push API
        if (str_starts_with($token, 'ExponentPushToken')) {
            self::sendExpoPush($token, $title, $body, $data);
            return;
        }

        // Native FCM tokens → Firebase
        self::sendFcmPush($token, $title, $body, $data);
    }

    public static function sendToMultiple($tokens, $title, $body, $data = [])
    {
        $validTokens = array_filter($tokens);
        if (empty($validTokens)) return;

        foreach ($validTokens as $token) {
            self::sendPush($token, $title, $body, $data);
        }
    }

    private static function sendExpoPush($token, $title, $body, $data = []): void
    {
        try {
            $payload = array_merge([
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ], !empty($data) ? ['data' => $data] : []);

            Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', $payload);
        } catch (\Exception $e) {
            \Log::error("Expo Push Error: " . $e->getMessage());
        }
    }

    private static function sendFcmPush($token, $title, $body, $data = []): void
    {
        if (!class_exists('Kreait\\Laravel\\Firebase\\Facades\\Firebase')) {
            return;
        }

        try {
            $firebase = \Kreait\Laravel\Firebase\Facades\Firebase::messaging();
            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body))
                ->withData($data);
            $firebase->send($message);
        } catch (\Exception $e) {
            \Log::error("FCM Error: " . $e->getMessage());
        }
    }
}
