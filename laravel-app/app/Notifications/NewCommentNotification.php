<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Notification as NotificationModel;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification
{
    use Queueable;

    protected $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $commenterName = $this->comment->user?->first_name ?? 'Alguien';
        $issueTitle = $this->comment->issue?->title ?? 'un reporte';
        $title = 'Nuevo Comentario';
        $message = "{$commenterName} comentó en '{$issueTitle}': " . substr($this->comment->comment, 0, 50) . "...";

        // Guardar en la tabla personalizada 'notifications'
        NotificationModel::create([
            'user_id' => $notifiable->id,
            'type' => 'new_comment',
            'title' => $title,
            'message' => $message,
            'related_id' => $this->comment->issue_id,
            'is_read' => false,
        ]);

        // Enviar Push
        if ($notifiable->fcm_token) {
            FcmService::sendPush($notifiable->fcm_token, $title, $message, [
                'issue_id' => $this->comment->issue_id,
                'comment_id' => $this->comment->id
            ]);
        }

        return [
            'issue_id' => $this->comment->issue_id,
            'comment_id' => $this->comment->id,
        ];
    }
}
