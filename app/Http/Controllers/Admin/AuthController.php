<?php

namespace App\Http\Controllers\Admin;

use App\Chatbot\Admin\AdminInput;
use App\Chatbot\Admin\AdminLimiter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function show()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        app(AdminLimiter::class)->login(is_string($request->input('email')) ? $request->input('email') : '', $request->ip());
        $data = AdminInput::fields($request, ['email' => 'required|string|email|max:255', 'password' => 'required|string|max:128']);
        $user = User::where('email', $data['email'])->first();
        // A fixed valid dummy hash keeps unknown-user attempts on the password-check path.
        $valid = Hash::check($data['password'], $user?->password ?? '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (! $valid || ! $user?->is_chatbot_admin) {
            throw ValidationException::withMessages(['email' => 'Unable to sign in with those credentials.']);
        }
        Auth::login($user, false);
        $request->session()->regenerate();
        $request->session()->forget('admin_password_confirmed');
        $request->session()->put(['admin_started' => now()->timestamp, 'admin_activity' => now()->timestamp]);

        return $request->expectsJson() ? response()->json(['redirect' => '/admin/chatbot']) : redirect('/admin/chatbot');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson() ? response()->noContent() : redirect('/admin/login');
    }

    public function confirm(Request $request)
    {
        app(AdminLimiter::class)->login($request->user()->email, $request->ip());
        $data = AdminInput::fields($request, ['password' => 'required|string|max:128']);
        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Unable to confirm password.']);
        }
        $request->session()->put('admin_password_confirmed', now()->timestamp);

        return response()->noContent();
    }
}
