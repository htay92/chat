<?php

namespace App\Http\Middleware;

use App\Chatbot\Admin\AdminLimiter;
use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = User::find($request->user()->id);
        abort_unless($user?->is_chatbot_admin, 403);
        $now = now()->timestamp;
        if ($now - $request->session()->get('admin_started', 0) >= 28800 || $now - $request->session()->get('admin_activity', 0) >= 1800) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw new AuthenticationException;
        }
        app(AdminLimiter::class)->acquire('admin-request-user', (string) $user->id, 120);
        $request->session()->put('admin_activity', $now);

        return $next($request);
    }
}
