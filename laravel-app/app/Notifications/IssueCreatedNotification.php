<?php

namespace App\Notifications;

use App\Models\Issue;
use App\Models\Notification as NotificationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IssueCreatedNotification extends Notification
{
    use Queueable;

    protected $issue;

    public function __construct(Issue $issue)
    {
        $this->issue = $issue;
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
            'type' => 'issue_created',
            'title' => 'Reporte Nuevo',
            'message' => "Se ha creado un reporte: {$this->issue->title}",
            'related_id' => $this->issue->id,
            'is_read' => false,
        ]);

        return [
            'issue_id' => $this->issue->id,
            'title' => $this->issue->title,
        ];
    }
}
