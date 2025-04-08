<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyWebhookToken
{
    public function handle(Request $request, Closure $next)
    {
        $secret = env('WEBHOOK_SECRET');

        if ($request->header('X-Webhook-Token') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
