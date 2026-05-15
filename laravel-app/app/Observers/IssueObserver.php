<?php

namespace App\Observers;

use App\Models\Issue;
use App\Models\Notification;
use App\Models\User;
use App\Services\FcmService;

class IssueObserver
{
    public function created(Issue $issue): void
    {
        $title = 'Reporte Nuevo';
        $message = "Se ha creado un reporte: {$issue->title}";

        // Notificar a administradores
        $admins = User::whereHas('role', function ($query) {
            $query->where('name', 'Admin');
        })->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'issue_created',
                'title' => $title,
                'message' => $message,
                'related_id' => $issue->id,
                'is_read' => false,
            ]);

            if ($admin->fcm_token) {
                try {
                    FcmService::sendPush($admin->fcm_token, $title, $message, [
                        'issue_id' => $issue->id
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning("FCM push failed for admin {$admin->id}: " . $e->getMessage());
                }
            }
        }
    }

    public function updated(Issue $issue): void
    {
        if (!$issue->isDirty('status_id')) {
            return;
        }

        $statusName = $issue->status->name ?? 'desconocido';
        $title = 'Actualización de Reporte';
        $message = "El estado del reporte '{$issue->title}' ha cambiado a: {$statusName}";

        $notified = [];

        // Notificar al reporter (dueño del issue)
        $reporter = $issue->user;
        if ($reporter) {
            $this->createNotification($reporter, 'status_updated', $title, $message, $issue->id);
            $notified[] = $reporter->id;
        }

        // Notificar a los workers asignados activamente
        $workers = $issue->assignments()
            ->whereHas('status', function ($q) {
                $q->whereIn('name', ['Pending', 'In Progress', 'On Hold']);
            })
            ->with('worker')
            ->get()
            ->pluck('worker')
            ->filter()
            ->unique('id');

        foreach ($workers as $worker) {
            if (!in_array($worker->id, $notified)) {
                $this->createNotification($worker, 'status_updated', $title, $message, $issue->id);
                $notified[] = $worker->id;
            }
        }
    }

    private function createNotification($user, string $type, string $title, string $message, ?int $relatedId): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $relatedId,
            'is_read' => false,
        ]);

        if ($user->fcm_token) {
            try {
                FcmService::sendPush($user->fcm_token, $title, $message, [
                    'issue_id' => $relatedId,
                ]);
            } catch (\Throwable $e) {
                \Log::warning("FCM push failed for user {$user->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Issue "deleted" event.
     */
    public function deleted(Issue $issue): void
    {
        //
    }

    /**
     * Handle the Issue "restored" event.
     */
    public function restored(Issue $issue): void
    {
        //
    }

    /**
     * Handle the Issue "force deleted" event.
     */
    public function forceDeleted(Issue $issue): void
    {
        //
    }
}
