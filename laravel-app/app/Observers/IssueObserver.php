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
        // Detectar si el status_id cambió
        if ($issue->isDirty('status_id')) {
            $reporter = $issue->user;
            if ($reporter) {
                $statusName = $issue->status->name ?? 'desconocido';
                $title = 'Actualización de Reporte';
                $message = "El estado de tu reporte '{$issue->title}' ha cambiado a: {$statusName}";

                Notification::create([
                    'user_id' => $reporter->id,
                    'type' => 'status_updated',
                    'title' => $title,
                    'message' => $message,
                    'related_id' => $issue->id,
                    'is_read' => false,
                ]);

                if ($reporter->fcm_token) {
                    try {
                        FcmService::sendPush($reporter->fcm_token, $title, $message, [
                            'issue_id' => $issue->id
                        ]);
                    } catch (\Throwable $e) {
                        \Log::warning("FCM push failed for user {$reporter->id}: " . $e->getMessage());
                    }
                }
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
