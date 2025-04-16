<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', env("url_front"), 301);

Route::get('/test-session-config', function () {
    return response()->json([
        'session_cookie' => config('session.cookie'),
        'session_domain' => config('session.domain'),
        'app_url' => config('app.url'),
        'same_site' => config('session.same_site'),
        'secure' => config('session.secure'),
    ]);
});
