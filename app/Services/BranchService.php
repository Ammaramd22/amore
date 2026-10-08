<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BranchService
{
    public const SESSION_KEY = 'current_branch_id';

    public static function enabled(): bool
    {
        return (bool) Setting::get('multi_branch_enabled', false);
    }

    /**
     * Resolve report/dashboard branch filter from request.
     * @return array{enabled:bool,key:string,id:?int,branches:\Illuminate\Support\Collection,label:string}
     */
    public static function resolveFilter(?string $raw = null): array
    {
        $branches = static::accessibleBranches();
        $enabled = static::enabled() && $branches->isNotEmpty();
        $raw = $raw !== null && $raw !== '' ? (string) $raw : 'all';

        if (! $enabled) {
            return [
                'enabled' => false,
                'key' => 'all',
                'id' => null,
                'branches' => $branches,
                'label' => 'All branches',
            ];
        }

        if ($raw === 'all') {
            return [
                'enabled' => true,
                'key' => 'all',
                'id' => null,
                'branches' => $branches,
                'label' => 'All branches',
            ];
        }

        if ($raw === 'main') {
            $main = $branches->firstWhere('is_main', true) ?? Branch::query()->where('is_main', true)->first();
            return [
                'enabled' => true,
                'key' => 'main',
                'id' => $main?->id ? (int) $main->id : null,
                'branches' => $branches,
                'label' => $main ? 'Main · '.$main->name : 'Main branch',
            ];
        }

        $id = (int) $raw;
        $match = $branches->firstWhere('id', $id);
        if (! $match) {
            return [
                'enabled' => true,
                'key' => 'all',
                'id' => null,
                'branches' => $branches,
                'label' => 'All branches',
            ];
        }

        return [
            'enabled' => true,
            'key' => (string) $id,
            'id' => $id,
            'branches' => $branches,
            'label' => $match->name,
        ];
    }

    /** Apply branch_id scope when filter has a concrete id. */
    public static function scopeOrders($query, ?int $branchId)
    {
        return $query->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public static function maxBranches(): int
    {
        return max(1, (int) Setting::get('max_branches', 2));
    }

    public static function canCreateMore(): bool
    {
        if (! static::enabled()) {
            return false;
        }

        return Branch::query()->count() < static::maxBranches();
    }

    public static function remainingSlots(): int
    {
        return max(0, static::maxBranches() - Branch::query()->count());
    }

    /** @return Collection<int, Branch> */
    public static function accessibleBranches(?User $user = null): Collection
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return collect();
        }

        if ($user->isSoftwareOwner() || $user->canAccessAllBranches()) {
            return Branch::query()->active()->ordered()->get();
        }

        return $user->accessibleBranches();
    }

    public static function needsBranchSelection(?User $user = null): bool
    {
        if (! static::enabled()) {
            return false;
        }

        $user = $user ?? auth()->user();
        if (! $user) {
            return false;
        }

        return static::accessibleBranches($user)->count() > 1;
    }

    public static function currentId(): ?int
    {
        if (! static::enabled()) {
            $main = Branch::query()->where('is_main', true)->value('id')
                ?? Branch::query()->value('id');

            return $main ? (int) $main : null;
        }

        $id = session(static::SESSION_KEY);
        if ($id) {
            return (int) $id;
        }

        return null;
    }

    public static function current(): ?Branch
    {
        $id = static::currentId();
        if (! $id) {
            return null;
        }

        return Branch::query()->find($id);
    }

    public static function setCurrent(?int $branchId, ?User $user = null): bool
    {
        if (! $branchId) {
            session()->forget(static::SESSION_KEY);

            return false;
        }

        $user = $user ?? auth()->user();
        $allowed = static::accessibleBranches($user)->pluck('id')->map(fn ($id) => (int) $id);
        if (! $allowed->contains((int) $branchId)) {
            return false;
        }

        session([static::SESSION_KEY => (int) $branchId]);

        return true;
    }

    /**
     * After login: auto-select single branch, or leave unset for picker.
     * Returns true if a branch is ready (or module off).
     */
    public static function resolveAfterLogin(User $user): bool
    {
        if (! static::enabled()) {
            $main = Branch::query()->where('is_main', true)->value('id')
                ?? Branch::query()->value('id');
            if ($main) {
                session([static::SESSION_KEY => (int) $main]);
            }

            return true;
        }

        $branches = static::accessibleBranches($user);
        if ($branches->isEmpty()) {
            $fallback = Branch::query()->active()->where('is_main', true)->first()
                ?? Branch::query()->active()->ordered()->first();
            if ($fallback) {
                session([static::SESSION_KEY => $fallback->id]);
            }

            return true;
        }

        if ($branches->count() === 1) {
            session([static::SESSION_KEY => $branches->first()->id]);

            return true;
        }

        // Multiple branches → always ask which one
        session()->forget(static::SESSION_KEY);

        return false;
    }

    /** Business / invoice settings for a branch (falls back to global). */
    public static function invoiceSettings(?Branch $branch = null): array
    {
        $branch = $branch ?? static::current();
        $business = Setting::getGroup('business');

        $companyName = $branch?->company_name ?: ($business['company_name'] ?? 'Restaurant');
        $address = $branch?->address ?: ($business['company_address'] ?? '');
        $phone = $branch?->phone ?: ($business['company_phone'] ?? '');
        $email = $branch?->email ?: ($business['company_email'] ?? '');
        $footer = $branch?->receipt_footer ?: ($business['receipt_footer'] ?? 'Thank you!');

        $logoSrc = null;
        if ($branch?->invoice_logo) {
            $logoSrc = static::branchLogoDataUri($branch) ?? static::branchLogoUrl($branch);
        }
        $logoSrc = $logoSrc ?: Setting::invoiceLogoDataUri() ?: Setting::invoiceLogoUrl();

        return array_merge($business, [
            'company_name' => $companyName,
            'company_address' => $address,
            'company_phone' => $phone,
            'company_email' => $email,
            'receipt_footer' => $footer,
            'invoice_logo_src' => $logoSrc,
            'branch_name' => $branch?->name,
            'branch_code' => $branch?->code,
        ]);
    }

    public static function branchLogoUrl(?Branch $branch): ?string
    {
        if (! $branch?->invoice_logo) {
            return null;
        }
        $logo = $branch->invoice_logo;
        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, '//')) {
            return $logo;
        }

        return asset('storage/'.ltrim(str_replace('storage/', '', $logo), '/'));
    }

    public static function branchLogoDataUri(?Branch $branch, int $maxWidth = 240): ?string
    {
        if (! $branch?->invoice_logo) {
            return null;
        }

        $relative = ltrim(str_replace('storage/', '', $branch->invoice_logo), '/');
        $path = storage_path('app/public/'.$relative);
        if (! is_file($path)) {
            return null;
        }

        // Reuse Setting resize via temporary call pattern
        $mime = @mime_content_type($path) ?: 'image/png';
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        // Prefer resized when large
        if (function_exists('getimagesize')) {
            $info = @getimagesize($path);
            if ($info && ($info[0] ?? 0) > $maxWidth && method_exists(Setting::class, 'invoiceLogoDataUri')) {
                // Inline small resize (same approach as Setting)
                $src = match (true) {
                    str_contains($mime, 'png') => @imagecreatefrompng($path),
                    str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($path),
                    default => null,
                };
                if ($src) {
                    $w = $info[0];
                    $h = $info[1];
                    $nw = $maxWidth;
                    $nh = max(1, (int) round($h * ($maxWidth / $w)));
                    $dst = imagecreatetruecolor($nw, $nh);
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                    imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    imagedestroy($src);
                    ob_start();
                    imagepng($dst, null, 6);
                    $png = ob_get_clean();
                    imagedestroy($dst);
                    if ($png) {
                        return 'data:image/png;base64,'.base64_encode($png);
                    }
                }
            }
        }

        return 'data:'.$mime.';base64,'.base64_encode($data);
    }

    public static function storeLogo(Branch $branch, $uploadedFile): string
    {
        $path = $uploadedFile->store('logos/branches/'.$branch->id, 'public');

        return $path;
    }

    public static function deleteLogoFile(?string $path): void
    {
        if (! $path) {
            return;
        }
        $relative = ltrim(str_replace('storage/', '', $path), '/');
        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
