<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectForUser(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        $login = trim($data['login']);

        $user = User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($login) {
                $q->where('email', $login);
                // username column is optional on older DBs until migration finishes
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'username')) {
                    $q->orWhere('username', $login);
                }
            })
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors([
                'login' => 'Invalid email/username or password.',
            ])->onlyInput('login');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->touchLogin($user, $request);
        Log::info('User logged in', ['user_id' => $user->id, 'login' => $login, 'method' => 'account']);

        return $this->redirectForUser($user);
    }

    public function loginPin(Request $request)
    {
        $data = $request->validate([
            'login_code' => ['required', 'regex:/^\d{4}$/'],
            'pin' => ['required', 'regex:/^\d{4}$/'],
        ]);

        $key = 'pin-login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'pin' => "Too many PIN attempts. Try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 60);

        $user = null;
        $method = 'pin';

        // Preferred: Staff ID + PIN (multi-branch / multi-cashier)
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'login_code')) {
            $byCode = User::query()
                ->where('is_active', true)
                ->where('login_code', $data['login_code'])
                ->whereNotNull('pin')
                ->where('pin', '!=', '')
                ->first();

            if ($byCode && $byCode->verifyPin($data['pin'])) {
                $user = $byCode;
                $method = 'staff_id';
            }
        }

        // Legacy fallback (pre Staff ID update): match by PIN only.
        // Software owners / older installs often had a PIN without a login_code.
        if (! $user) {
            $candidates = User::query()
                ->where('is_active', true)
                ->whereNotNull('pin')
                ->where('pin', '!=', '')
                ->get();

            foreach ($candidates as $candidate) {
                if ($candidate->verifyPin($data['pin'])) {
                    $user = $candidate;
                    $method = 'pin_legacy';
                    break;
                }
            }
        }

        if (! $user) {
            throw ValidationException::withMessages([
                'pin' => 'Invalid staff ID or PIN. Or use the Account tab (email + password).',
            ]);
        }

        RateLimiter::clear($key);
        Auth::login($user, false);
        $request->session()->regenerate();
        $this->touchLogin($user, $request);
        Log::info('User logged in', [
            'user_id' => $user->id,
            'login_code' => $data['login_code'],
            'method' => $method,
        ]);

        return $this->redirectForUser($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function touchLogin(User $user, Request $request): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
    }

    /** After auth: branch pick if needed, then shop UI, else role home. */
    public function redirectForUser(User $user)
    {
        if (\App\Services\BranchService::enabled()) {
            if (! \App\Services\BranchService::resolveAfterLogin($user)) {
                return redirect()->route('branches.select');
            }
        }

        return $this->afterBranchReady($user);
    }

    /** After branch is ready: ask shop UI (demo), then role home. */
    public function afterBranchReady(User $user)
    {
        if (! $this->shouldSkipShopUi($user) && ! \App\Http\Controllers\Auth\ShopUiController::hasSelected()) {
            return redirect()->route('shop-ui.select');
        }

        return $this->homeForUser($user);
    }

    protected function shouldSkipShopUi(User $user): bool
    {
        if (! \App\Http\Controllers\Auth\ShopUiController::isPickerEnabled()) {
            return true;
        }

        return $user->isWaiter()
            || $user->isKitchenStaff()
            || $user->isBilliardsStaff();
    }

    public function homeForUser(User $user)
    {
        if ($user->isWaiter()) {
            return redirect()->intended(route('waiter.dashboard'));
        }
        if ($user->isKitchenStaff()) {
            return redirect()->intended(route('kds.index'));
        }
        if ($user->isBilliardsStaff()) {
            return redirect()->intended(route('billiards.desk'));
        }
        if ($user->user_type === 'cashier' || $user->hasRole('cashier')) {
            return redirect()->intended(route('pos.index'));
        }

        return redirect()->intended(route('dashboard'));
    }
}
