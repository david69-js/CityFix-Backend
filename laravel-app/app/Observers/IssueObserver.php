<?php

namespace App\Observers;

use App\Models\Issue;
use App\Models\User;
use App\Notifications\IssueCreatedNotification;
use App\Notifications\IssueStatusUpdatedNotification;
use Illuminate\Support\Facades\Notification;

class IssueObserver
{
    public function created(Issue $issue): void
    {
        // Notificar a administradores
        $admins = User::whereHas('role', function ($query) {
            $query->where('name', 'Admin');
        })->get();

        Notification::send($admins, new IssueCreatedNotification($issue));
    }

    public function updated(Issue $issue): void
    {
        // Detectar si el status_id cambió
        if ($issue->isDirty('status_id')) {
            // Notificar al dueño del reporte
            $reporter = $issue->user;
            if ($reporter) {
                $reporter->notify(new IssueStatusUpdatedNotification($issue));
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
