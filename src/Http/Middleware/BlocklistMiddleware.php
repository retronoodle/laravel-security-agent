<?php

namespace Timmonaghan\SecurityAgent\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlocklistMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $blocked = DB::table('lsa_ip_blocklist')
            ->where('ip_address', $request->ip())
            ->where('expires_at', '>', Carbon::now())
            ->exists();

        if ($blocked) {
            abort(403, 'Your IP has been blocked due to suspicious activity.');
        }

        return $next($request);
    }
}
