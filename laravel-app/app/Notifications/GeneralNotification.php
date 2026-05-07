<?php

namespace App\Notifications;

use App\Models\Notification as NotificationModel;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;

    public function __construct(string $title, string $message)
    {
        $this->title = $title;
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Guardar en la tabla personalizada 'notifications'
        NotificationModel::create([
            'user_id' => $notifiable->id,
            'type' => 'campaign',
            'title' => $this->title,
            'message' => $this->message,
            'related_id' => null,
            'is_read' => false,
        ]);

        // Intentar enviar Push si tiene token
        if ($notifiable->fcm_token) {
            FcmService::sendPush($notifiable->fcm_token, $this->title, $this->message);
        }

        return [
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
