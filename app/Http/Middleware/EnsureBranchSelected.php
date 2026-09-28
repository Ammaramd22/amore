<?php

namespace App\Http\Middleware;

use App\Services\BranchService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Always allow auth / picker routes
        if ($request->routeIs([
            'login',
            'login.pin',
            'logout',
            'branches.select',
            'branches.select.store',
            'branches.switch',
            'shop-ui.select',
            'shop-ui.select.store',
            'password.*',
            'install.*',
        ])) {
            return $next($request);
        }

        if (BranchService::enabled()) {
            $current = BranchService::currentId();
            $allowed = BranchService::accessibleBranches($user)->pluck('id')->map(fn ($id) => (int) $id);

            if ($current && $allowed->isNotEmpty() && ! $allowed->contains($current)) {
                session()->forget(BranchService::SESSION_KEY);
                $current = null;
            }

            if (! $current) {
                if ($allowed->count() === 1) {
                    BranchService::setCurrent($allowed->first(), $user);
                } elseif ($allowed->count() > 1 || BranchService::needsBranchSelection($user)) {
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'message' => 'Please select a branch to continue.',
                            'redirect' => route('branches.select'),
                        ], 409);
                    }

                    return redirect()->route('branches.select');
                } else {
                    BranchService::resolveAfterLogin($user);
                }
            }
        }

        // Demo: require shop UI pick when enabled (skip kitchen/waiter/billiards)
        if (\App\Http\Controllers\Auth\ShopUiController::isPickerEnabled()
            && ! $user->isWaiter()
            && ! $user->isKitchenStaff()
            && ! $user->isBilliardsStaff()
            && ! \App\Http\Controllers\Auth\ShopUiController::hasSelected()
        ) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Please select a shop UI to continue.',
                    'redirect' => route('shop-ui.select'),
                ], 409);
            }

            return redirect()->route('shop-ui.select');
        }

        return $next($request);
    }
}
