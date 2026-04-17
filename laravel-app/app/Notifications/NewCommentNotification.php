<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Notification as NotificationModel;
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

        // Guardar en la tabla personalizada 'notifications'
        NotificationModel::create([
            'user_id' => $notifiable->id,
            'type' => 'new_comment',
            'title' => 'Nuevo Comentario',
            'message' => "{$commenterName} comentó en '{$issueTitle}': " . substr($this->comment->comment, 0, 50) . "...",
            'related_id' => $this->comment->issue_id,
            'is_read' => false,
        ]);

        return [
            'issue_id' => $this->comment->issue_id,
            'comment_id' => $this->comment->id,
        ];
    }
}
