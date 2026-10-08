<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictBilliardsAccess
{
    /**
     * Billiards desk staff may only use billiards routes (+ logout / profile basics).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isBilliardsStaff()) {
            return $next($request);
        }

        if ($request->routeIs([
            'logout',
            'billiards.*',
            'branches.select',
            'branches.select.store',
            'branches.switch',
            'login',
        ])) {
            return $next($request);
        }

        return redirect()->route('billiards.desk');
    }
}
