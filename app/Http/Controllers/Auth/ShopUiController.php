<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\IceCreamDemoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShopUiController extends Controller
{
    public const SESSION_KEY = 'pos_ui_mode';

    public const MODES = ['restaurant', 'bakery', 'ice_cream'];

    public function selectForm(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        if ($this->shouldSkip($user) || ! self::isPickerEnabled()) {
            return app(LoginController::class)->homeForUser($user);
        }

        $current = $this->currentMode();

        return view('auth.shop-ui-select', [
            'modes' => $this->modeCards(),
            'current' => $current,
        ]);
    }

    public function selectStore(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        if (! self::isPickerEnabled()) {
            return app(LoginController::class)->homeForUser($user);
        }

        $data = $request->validate([
            'pos_ui_mode' => ['required', 'in:restaurant,bakery,ice_cream'],
        ]);

        $mode = $data['pos_ui_mode'];
        session([self::SESSION_KEY => $mode]);

        // Persist so Settings / POS stay in sync for the demo
        Setting::set('pos_ui_mode', $mode, 'pos', 'POS front UI mode', 'string');

        // Ice Cream demo: seed catalog + images (idempotent) so POS looks like local demo
        if ($mode === 'ice_cream') {
            try {
                IceCreamDemoService::ensureSeeded();
            } catch (\Throwable $e) {
                Log::warning('Ice cream demo seed failed', ['error' => $e->getMessage()]);
            }
        }

        return app(LoginController::class)->homeForUser($user);
    }

    /** Software owner can disable the post-login Restaurant / Bakery / Ice Cream screen. */
    public static function isPickerEnabled(): bool
    {
        $val = Setting::get('shop_ui_picker_enabled', true);

        // Form saves "0"/"1"; never use (bool)$val — (bool)"0" is true in PHP.
        if (is_bool($val)) {
            return $val;
        }

        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public static function currentMode(?string $fallback = 'restaurant'): string
    {
        if (self::isPickerEnabled()) {
            $session = session(self::SESSION_KEY);
            if (in_array($session, self::MODES, true)) {
                return $session;
            }
        }

        $stored = Setting::get('pos_ui_mode', $fallback);
        if (in_array($stored, self::MODES, true)) {
            return $stored;
        }

        return $fallback;
    }

    public static function hasSelected(): bool
    {
        return in_array(session(self::SESSION_KEY), self::MODES, true);
    }

    protected function shouldSkip($user): bool
    {
        return $user->isWaiter()
            || $user->isKitchenStaff()
            || $user->isBilliardsStaff();
    }

    protected function modeCards(): array
    {
        return [
            [
                'key' => 'restaurant',
                'title' => 'Restaurant',
                'subtitle' => 'Tables · KOT · Open bills',
                'icon' => 'fa-utensils',
                'accent' => '#f59e0b',
            ],
            [
                'key' => 'bakery',
                'title' => 'Bakery / Cafe',
                'subtitle' => 'Category rail · Quick cart',
                'icon' => 'fa-bread-slice',
                'accent' => '#c49a3c',
            ],
            [
                'key' => 'ice_cream',
                'title' => 'Ice Cream Shop',
                'subtitle' => 'Demo menu + photos · Tablet layout',
                'icon' => 'fa-ice-cream',
                'accent' => '#db2777',
            ],
        ];
    }
}
