<?php

namespace App\Notifications;

use App\Models\Issue;
use App\Models\Notification as NotificationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IssueStatusUpdatedNotification extends Notification
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
        $statusName = $this->issue->status?->name ?? 'desconocido';

        // Guardar en la tabla personalizada 'notifications'
        NotificationModel::create([
            'user_id' => $notifiable->id,
            'type' => 'status_updated',
            'title' => 'Actualización de Reporte',
            'message' => "El estado de tu reporte '{$this->issue->title}' ha cambiado a: {$statusName}",
            'related_id' => $this->issue->id,
            'is_read' => false,
        ]);

        return [
            'issue_id' => $this->issue->id,
            'status' => $statusName,
        ];
    }
}
