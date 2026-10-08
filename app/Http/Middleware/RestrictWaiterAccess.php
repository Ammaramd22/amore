<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictWaiterAccess
{
    /**
     * Waiters may only use the waiter hub, waiter panel, and print helpers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isWaiter()) {
            return $next($request);
        }

        if ($request->routeIs([
            'dashboard',
            'logout',
            'waiter.*',
            'branches.select',
            'branches.select.store',
            'branches.switch',
            'pos.order-details',
            'pos.network-print',
            'pos.print-kot',
            'pos.print-bot',
            'pos.kitchen-order',
            'pos.kot-details',
        ])) {
            return $next($request);
        }

        return redirect()->route('waiter.dashboard');
    }
}
