<?php

namespace App\Services;

use App\Models\Setting;

class PwaIconService
{
    /** Regenerate waiter PWA icons from the configured app logo (or software logo). */
    public function regenerateWaiterIcons(): bool
    {
        $srcPath = Setting::logoPathFor('pwa_app_logo')
            ?? Setting::logoPathFor('company_logo');

        if (! $srcPath || ! function_exists('imagecreatetruecolor')) {
            return false;
        }

        $img = $this->loadImage($srcPath);
        if (! $img) {
            return false;
        }

        $outDir = public_path('pwa/waiter');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $sw = imagesx($img);
        $sh = imagesy($img);

        $this->writeIcon($img, $sw, $sh, 96, $outDir.'/icon-96.png', 0.08);
        $this->writeIcon($img, $sw, $sh, 192, $outDir.'/icon-192.png', 0.08);
        $this->writeIcon($img, $sw, $sh, 512, $outDir.'/icon-512.png', 0.08);
        $this->writeIcon($img, $sw, $sh, 180, $outDir.'/apple-touch-icon.png', 0.08);
        $this->writeIcon($img, $sw, $sh, 512, $outDir.'/maskable-512.png', 0.18);

        imagedestroy($img);

        return true;
    }

    private function loadImage(string $path)
    {
        $mime = @mime_content_type($path) ?: '';

        return match (true) {
            str_contains($mime, 'png') || str_ends_with(strtolower($path), '.png') => @imagecreatefrompng($path),
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') || preg_match('/\.jpe?g$/i', $path) => @imagecreatefromjpeg($path),
            str_contains($mime, 'webp') && function_exists('imagecreatefromwebp') => @imagecreatefromwebp($path),
            str_contains($mime, 'gif') => @imagecreatefromgif($path),
            default => @imagecreatefrompng($path) ?: @imagecreatefromjpeg($path),
        };
    }

    private function writeIcon($srcImg, int $sw, int $sh, int $size, string $path, float $padRatio): void
    {
        $dst = imagecreatetruecolor($size, $size);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);
        imagealphablending($dst, true);

        $bgHex = (string) Setting::get('pwa_background_color', '#1c1410');
        [$r, $g, $b] = $this->hexToRgb($bgHex);
        $bg = imagecolorallocate($dst, $r, $g, $b);
        imagefilledrectangle($dst, 0, 0, $size, $size, $bg);

        $pad = (int) round($size * $padRatio);
        $box = max(1, $size - ($pad * 2));
        $scale = min($box / max(1, $sw), $box / max(1, $sh));
        $nw = (int) round($sw * $scale);
        $nh = (int) round($sh * $scale);
        $dx = (int) (($size - $nw) / 2);
        $dy = (int) (($size - $nh) / 2);
        imagecopyresampled($dst, $srcImg, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);
        imagepng($dst, $path, 8);
        imagedestroy($dst);
    }

    /** @return array{0:int,1:int,2:int} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [28, 20, 16];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
