<?php

namespace App\Http\Middleware;

use App\Models\Guest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class IdentifyGuest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guestId = $request->cookie('guest_id');

        if (!$guestId) {
            $guestId = Str::uuid()->toString();
            cookie()->queue(cookie('guest_id', $guestId, 60 * 24 * 30)); // 30 days
        }

        Guest::updateOrCreate(
            ['uuid' => $guestId],
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_seen' => now(),
                'expires_at' => now()->addDays(30),
            ]
        );

        return $next($request);
    }
}
