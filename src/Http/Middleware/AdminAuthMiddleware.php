<?php

namespace Timmonaghan\SecurityAgent\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $password = config('lsa.admin.password');

        if (! $password) {
            Log::warning('SecurityAgent: LSA admin panel accessed but no password is configured (lsa.admin.password). Access denied.');
            abort(403, 'Admin panel is not configured. Set lsa.admin.password to enable access.');
        }

        if ($request->session()->get('lsa_admin_authenticated')) {
            return $next($request);
        }

        $path = config('lsa.admin.path', 'lsa-admin');
        return redirect("/{$path}/login");
    }
}
