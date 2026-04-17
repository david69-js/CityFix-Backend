<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use Illuminate\Support\Facades\Notification;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        $issue = $comment->issue;

        // Gente a notificar: dueño del reporte + otros comentadores
        $commenterIds = $issue->comments()
            ->where('user_id', '!=', $comment->user_id)
            ->pluck('user_id')
            ->toArray();

        // Agregar el dueño del reporte si no es quien comenta
        if ($issue->user_id != $comment->user_id) {
            $commenterIds[] = $issue->user_id;
        }

        $userIdsToNotify = array_unique($commenterIds);

        if (!empty($userIdsToNotify)) {
            $users = User::whereIn('id', $userIdsToNotify)->get();
            Notification::send($users, new NewCommentNotification($comment));
        }
    }

    public function updated(Comment $comment): void
    {
        //
    }

    public function deleted(Comment $comment): void
    {
        //
    }

    public function restored(Comment $comment): void
    {
        //
    }

    public function forceDeleted(Comment $comment): void
    {
        //
    }
}
