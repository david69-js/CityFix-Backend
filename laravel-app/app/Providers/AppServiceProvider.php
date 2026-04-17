<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Issue;
use App\Models\Comment;
use App\Observers\IssueObserver;
use App\Observers\CommentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Issue::observe(IssueObserver::class);
        Comment::observe(CommentObserver::class);
    }
}
