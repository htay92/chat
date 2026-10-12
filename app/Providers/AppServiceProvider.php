<?php

namespace App\Providers;

use App\Chatbot\Admin\FileRestorationSink;
use App\Chatbot\Admin\RestorationSink;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RestorationSink::class, FileRestorationSink::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-chatbot-admin', fn (User $user) => (bool) $user->fresh()?->is_chatbot_admin);
    }
}
