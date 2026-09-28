<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * One-click migrate + cache clear for cPanel when SSH/Terminal is unavailable.
 * Visit: /setup/migrate-update?token=YOUR_TOKEN
 *
 * Token (first match wins):
 * 1. SETUP_MIGRATE_TOKEN from .env
 * 2. SETUP_STORAGE_TOKEN from .env
 * 3. first 32 chars of sha256(APP_KEY|migrate-update)
 *
 * Logged-in software_owner may run without a token.
 */
class SetupMigrateController extends Controller
{
    public static function expectedToken(): string
    {
        foreach (['SETUP_MIGRATE_TOKEN', 'SETUP_STORAGE_TOKEN'] as $envKey) {
            $custom = (string) env($envKey, '');
            if ($custom !== '') {
                return $custom;
            }
        }

        return substr(hash('sha256', (string) config('app.key').'|migrate-update'), 0, 32);
    }

    public function __invoke(Request $request)
    {
        $token = (string) $request->query('token', '');
        $user = $request->user();
        $allowed = ($token !== '' && hash_equals(self::expectedToken(), $token))
            || ($user && method_exists($user, 'isSoftwareOwner') && $user->isSoftwareOwner());

        if (! $allowed) {
            $hint = $user
                ? 'Open this URL while logged in as Software Owner, or add ?token=…'
                : 'Invalid or missing token. Log in as Software Owner first, then open /setup/migrate-update — or use ?token=…';

            return response(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Migrate Update</title></head><body style="font-family:system-ui;max-width:640px;margin:40px auto;padding:0 16px">'
                .'<h1>403 — Not authorized</h1>'
                .'<p>'.e($hint).'</p>'
                .'<p><strong>Easiest (no token):</strong></p>'
                .'<ol><li>Log into Admin as <em>Software Owner</em></li>'
                .'<li>Open <code>/setup/migrate-update</code> in the same browser</li></ol>'
                .'<p>Or compute token = first 32 chars of sha256(APP_KEY + "|migrate-update") from your .env APP_KEY.</p>'
                .'</body></html>',
                403
            )->header('Content-Type', 'text/html; charset=utf-8');
        }

        $steps = [];
        $ok = true;

        try {
            DB::connection()->getPdo();
            $steps[] = ['ok' => true, 'label' => 'Database connection', 'detail' => 'OK'];
        } catch (Throwable $e) {
            $ok = false;
            $steps[] = ['ok' => false, 'label' => 'Database connection', 'detail' => $e->getMessage()];

            return $this->htmlResponse($ok, $steps, 'Fix DB credentials in .env first.');
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $out = trim(Artisan::output());
            $steps[] = [
                'ok' => true,
                'label' => 'php artisan migrate --force',
                'detail' => $out !== '' ? $out : 'Nothing to migrate (already up to date).',
            ];
        } catch (Throwable $e) {
            $ok = false;
            $steps[] = ['ok' => false, 'label' => 'php artisan migrate --force', 'detail' => $e->getMessage()];
        }

        try {
            Artisan::call('optimize:hosting', ['--clear' => true]);
            $out = trim(Artisan::output());
            $steps[] = [
                'ok' => true,
                'label' => 'php artisan optimize:hosting --clear',
                'detail' => $out !== '' ? $out : 'Caches cleared.',
            ];
        } catch (Throwable $e) {
            try {
                Artisan::call('optimize:clear');
                $out = trim(Artisan::output());
                if (class_exists(Setting::class)) {
                    Setting::flushCache();
                }
                $steps[] = [
                    'ok' => true,
                    'label' => 'php artisan optimize:clear (fallback)',
                    'detail' => $out !== '' ? $out : 'Caches cleared.',
                ];
            } catch (Throwable $e2) {
                $ok = false;
                $steps[] = ['ok' => false, 'label' => 'Cache clear', 'detail' => $e2->getMessage()];
            }
        }

        // Warm production caches after a successful migrate
        if ($ok) {
            try {
                Artisan::call('optimize:hosting');
                $out = trim(Artisan::output());
                $steps[] = [
                    'ok' => true,
                    'label' => 'php artisan optimize:hosting',
                    'detail' => $out !== '' ? $out : 'Caches rebuilt.',
                ];
            } catch (Throwable $e) {
                $steps[] = [
                    'ok' => true,
                    'label' => 'optimize:hosting (optional)',
                    'detail' => 'Skipped: '.$e->getMessage(),
                ];
            }
        }

        $summary = $ok
            ? 'Update migrations finished. You can close this page. Menu Management → Add-ons / Subcategories should appear after refresh (Software Owner / Admin).'
            : 'Some steps failed — see details below. Fix the error and open this URL again.';

        return $this->htmlResponse($ok, $steps, $summary);
    }

    /** @param  array<int, array{ok:bool,label:string,detail:string}>  $steps */
    private function htmlResponse(bool $ok, array $steps, string $summary)
    {
        $rows = '';
        foreach ($steps as $step) {
            $color = $step['ok'] ? '#166534' : '#b91c1c';
            $badge = $step['ok'] ? 'OK' : 'FAIL';
            $rows .= '<tr><td style="padding:8px;border-bottom:1px solid #e5e7eb;"><strong style="color:'.$color.'">'.$badge.'</strong></td>'
                .'<td style="padding:8px;border-bottom:1px solid #e5e7eb;"><strong>'.e($step['label']).'</strong><pre style="white-space:pre-wrap;margin:6px 0 0;font-size:12px;background:#f8fafc;padding:8px;border-radius:8px;">'
                .e($step['detail']).'</pre></td></tr>';
        }

        $titleColor = $ok ? '#166534' : '#b91c1c';

        return response(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="robots" content="noindex">'
            .'<title>Migrate Update</title></head>'
            .'<body style="font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;color:#1c1917">'
            .'<h1 style="color:'.$titleColor.';">'.($ok ? 'Update complete' : 'Update incomplete').'</h1>'
            .'<p>'.e($summary).'</p>'
            .'<table style="width:100%;border-collapse:collapse;margin-top:16px;">'.$rows.'</table>'
            .'<p style="margin-top:24px;color:#64748b;font-size:13px;">Security tip: remove public access to this URL after use is not required — token / Software Owner gate remains.</p>'
            .'<p><a href="/dashboard">← Back to Dashboard</a></p>'
            .'</body></html>',
            $ok ? 200 : 500
        )->header('Content-Type', 'text/html; charset=utf-8');
    }
}
