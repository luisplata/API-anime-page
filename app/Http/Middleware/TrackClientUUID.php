<?php

namespace App\Http\Middleware;

use App\Models\AnonymousClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackClientUUID
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next)
    {
        $uuid = $request->header('X-Client-UUID');

        Log::info("UUID: $uuid");

        if ($uuid) {
            // Buscar o crear el cliente anónimo
            $client = AnonymousClient::firstOrCreate(
                ['id' => $uuid],
                [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            // Crear log del request
            $client->logs()->create([
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'query_params' => $request->query(),
            ]);
        }

        return $next($request);
    }
}
