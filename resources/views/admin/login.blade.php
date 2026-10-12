<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Chatbot admin sign in</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-login"><main class="admin-login-card"><p class="admin-eyebrow">RESTRICTED WORKSPACE</p><h1>Chatbot administration</h1><p class="admin-muted">Sign in with your authorized admin account.</p>
@isset($message)<p id="login-error" class="admin-notice error" role="alert">{{ $message }}</p>@endisset
<form method="post" action="/admin/login">@csrf
<div class="admin-field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="username" required @isset($message) aria-describedby="login-error" aria-invalid="true" @endisset></div>
<div class="admin-field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required @isset($message) aria-describedby="login-error" @endisset></div>
<button class="admin-button" type="submit">Sign in</button></form></main></body></html>
