<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * One-click storage:link for cPanel when SSH is unavailable.
 * Visit: /setup/storage-link?token=YOUR_TOKEN
 *
 * Fixes broken Mac absolute symlinks after upload so /storage/logos
 * and /storage/products images work again.
 */
class StorageLinkController extends Controller
{
    public static function expectedToken(): string
    {
        $custom = (string) env('SETUP_STORAGE_TOKEN', '');
        if ($custom !== '') {
            return $custom;
        }

        return substr(hash('sha256', (string) config('app.key').'|storage-link'), 0, 32);
    }

    public function __invoke(Request $request)
    {
        $token = (string) $request->query('token', '');
        $user = $request->user();
        $allowed = hash_equals(self::expectedToken(), $token)
            || ($user && method_exists($user, 'isSoftwareOwner') && $user->isSoftwareOwner());

        if (! $allowed) {
            abort(403, 'Invalid or missing token. Use /setup/storage-link?token=…');
        }

        $link = public_path('storage');
        $target = storage_path('app/public');
        $messages = [];
        $ok = false;

        File::ensureDirectoryExists($target);
        foreach (['logos', 'products', 'categories', 'marketing'] as $dir) {
            File::ensureDirectoryExists($target.DIRECTORY_SEPARATOR.$dir);
        }

        // Detect broken / absolute foreign symlink (common after uploading from Mac)
        $broken = false;
        if (is_link($link)) {
            $resolved = @readlink($link);
            $messages[] = 'Current link target: '.($resolved ?: '(unknown)');
            if (! is_dir($link)) {
                $broken = true;
                $messages[] = 'Link is broken (target folder missing on this server).';
            } elseif (is_string($resolved) && str_contains($resolved, '/Users/')) {
                $broken = true;
                $messages[] = 'Link points to a Mac path — must be recreated on the server.';
            }
        }

        if ($broken || is_link($link)) {
            @unlink($link);
            $messages[] = 'Removed old public/storage link.';
        } elseif (file_exists($link) && ! is_dir($link)) {
            @unlink($link);
            $messages[] = 'Removed invalid public/storage file.';
        }

        // Try artisan, then relative symlink
        if (! file_exists($link)) {
            try {
                Artisan::call('storage:link');
                $out = trim(Artisan::output());
                if ($out !== '') {
                    $messages[] = $out;
                }
            } catch (\Throwable $e) {
                $messages[] = 'artisan storage:link: '.$e->getMessage();
            }
        }

        if (! file_exists($link) || (is_link($link) && ! is_dir($link))) {
            @unlink($link);
            $relative = '../storage/app/public';
            if (@symlink($relative, $link)) {
                $messages[] = 'Created relative symlink: public/storage → '.$relative;
                $ok = is_dir($link);
            }
        }

        // Last resort: copy folders into public/storage (hosts that block symlink)
        if (! is_dir($link)) {
            @unlink($link);
            File::ensureDirectoryExists($link);
            $copied = 0;
            foreach (['logos', 'products', 'categories', 'marketing'] as $dir) {
                $from = $target.DIRECTORY_SEPARATOR.$dir;
                $to = $link.DIRECTORY_SEPARATOR.$dir;
                if (! is_dir($from)) {
                    continue;
                }
                File::ensureDirectoryExists($to);
                foreach (File::files($from) as $file) {
                    $dest = $to.DIRECTORY_SEPARATOR.$file->getFilename();
                    if (! is_file($dest)) {
                        File::copy($file->getPathname(), $dest);
                        $copied++;
                    }
                }
            }
            $messages[] = "Copied {$copied} media file(s) into public/storage (symlink not available).";
            $ok = true;
        } else {
            $ok = true;
            if (is_link($link)) {
                $messages[] = 'Link OK: public/storage → '.readlink($link);
            } else {
                $messages[] = 'public/storage folder is ready.';
            }
        }

        $productFiles = is_dir($target.'/products') ? count(File::files($target.'/products')) : 0;
        $logoFiles = is_dir($target.'/logos') ? count(File::files($target.'/logos')) : 0;
        $messages[] = "Files on disk — products: {$productFiles}, logos: {$logoFiles}";

        if ($productFiles === 0) {
            $messages[] = '⚠ No product images in storage/app/public/products — upload the products folder from your local storage/app/public/products via FTP.';
        }

        $sampleProduct = null;
        if ($productFiles > 0) {
            $files = File::files($target.'/products');
            $sampleProduct = '/storage/products/'.$files[0]->getFilename();
        }
        $logoUrl = \App\Models\Setting::logoUrl();

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Storage link</title>
<style>
body{font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;background:#fafaf9;color:#1c1917}
.card{background:#fff;border:1px solid #e7e5e4;border-radius:14px;padding:20px}
.ok{color:#047857}.bad{color:#b91c1c}
code{background:#f5f5f4;padding:2px 6px;border-radius:6px;word-break:break-all}
ul{line-height:1.65}
.grid{display:flex;gap:16px;flex-wrap:wrap;margin-top:12px}
.grid img{width:96px;height:96px;object-fit:cover;border-radius:12px;border:1px solid #e7e5e4;background:#f5f5f4}
</style></head><body><div class="card">';
        $html .= '<h1>'.($ok ? '✅ Storage ready' : '⚠️ Storage setup').'</h1>';
        $html .= '<p>This fixes <strong>logo</strong> and <strong>product images</strong> on POS.</p><ul>';
        foreach ($messages as $m) {
            $html .= '<li>'.e($m).'</li>';
        }
        $html .= '</ul><div class="grid">';
        if ($logoUrl) {
            $html .= '<div><div>Logo</div><img src="'.e($logoUrl).'" alt="logo" onerror="this.insertAdjacentHTML(\'afterend\',\'<div class=bad>Logo 404</div>\')"></div>';
        }
        if ($sampleProduct) {
            $html .= '<div><div>Sample product</div><img src="'.e($sampleProduct).'" alt="product" onerror="this.insertAdjacentHTML(\'afterend\',\'<div class=bad>Product image 404</div>\')"></div>';
        }
        $html .= '</div>';
        $html .= '<p style="margin-top:16px"><a href="'.e(url('/pos')).'">Open POS</a> · <a href="'.e(url('/')).'">Dashboard</a></p>';
        $html .= '</div></body></html>';

        return response($html, $ok ? 200 : 500)->header('Content-Type', 'text/html; charset=utf-8');
    }
}
