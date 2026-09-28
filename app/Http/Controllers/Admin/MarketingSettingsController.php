<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingAd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarketingSettingsController extends Controller
{
    public function index()
    {
        $settings = \DB::table('marketing_settings')->pluck('value', 'key');
        $ads = MarketingAd::query()->orderBy('sort_order')->orderByDesc('id')->get();

        return view('admin.marketing.index', [
            'settings' => $settings,
            'ads' => $ads,
            'todayCount' => count(MarketingAd::todayPosterUrls((int) ($settings['ads_per_day'] ?? 3))),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'welcome_text' => 'nullable|string|max:255',
            'idle_timeout' => 'nullable|integer|min:10|max:300',
            'slide_interval' => 'nullable|integer|min:3|max:60',
            'ads_per_day' => 'nullable|integer|min:1|max:20',
            'marketing_enabled' => 'boolean',
        ]);

        $this->upsert('welcome_text', $validated['welcome_text'] ?? 'Welcome! Order at the counter', 'text');
        $this->upsert('idle_timeout', (string) ($validated['idle_timeout'] ?? 30), 'text');
        $this->upsert('slide_interval', (string) ($validated['slide_interval'] ?? 6), 'text');
        $this->upsert('ads_per_day', (string) ($validated['ads_per_day'] ?? 3), 'integer');
        $this->upsert('marketing_enabled', $request->boolean('marketing_enabled') ? '1' : '0', 'boolean');

        $this->syncLegacyPosterJson();

        return redirect()->route('admin.marketing.index')->with('success', 'Marketing settings saved.');
    }

    public function storeAd(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'show_on_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $file = $request->file('image');
        $filename = 'ad_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('marketing', $filename, 'public');

        $maxOrder = (int) MarketingAd::query()->max('sort_order');

        MarketingAd::create([
            'title' => $validated['title'] ?: 'Ad',
            'image_path' => $path,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $maxOrder + 1,
            'show_on_date' => $validated['show_on_date'] ?? null,
        ]);

        $this->syncLegacyPosterJson();

        return redirect()->route('admin.marketing.index')->with('success', 'Ad added.');
    }

    public function toggleAd(MarketingAd $ad)
    {
        $ad->is_active = ! $ad->is_active;
        $ad->save();
        $this->syncLegacyPosterJson();

        return redirect()->route('admin.marketing.index')->with('success', $ad->is_active ? 'Ad enabled.' : 'Ad disabled.');
    }

    public function destroyAd(MarketingAd $ad)
    {
        if ($ad->image_path && Storage::disk('public')->exists($ad->image_path)) {
            Storage::disk('public')->delete($ad->image_path);
        }
        $ad->delete();
        $this->syncLegacyPosterJson();

        return redirect()->route('admin.marketing.index')->with('success', 'Ad removed.');
    }

    public function getSettingsApi()
    {
        $settings = \DB::table('marketing_settings')->pluck('value', 'key');
        $limit = max(1, (int) ($settings['ads_per_day'] ?? 3));
        $enabled = ($settings['marketing_enabled'] ?? '1') === '1';

        $posters = $enabled ? MarketingAd::todayPosterUrls($limit) : [];

        // Fallback to legacy JSON if ads table empty
        if ($enabled && $posters === []) {
            $posters = $this->legacyPosterList($settings);
            $posters = array_slice($posters, 0, $limit);
        }

        return response()->json([
            'welcome_text' => $settings['welcome_text'] ?? 'Welcome! Order at the counter',
            'poster_url' => $posters[0] ?? null,
            'posters' => $posters,
            'ads_per_day' => $limit,
            'idle_timeout' => (int) ($settings['idle_timeout'] ?? 30),
            'slide_interval' => (int) ($settings['slide_interval'] ?? 6),
            'marketing_enabled' => $enabled,
        ]);
    }

    /** Keep old poster_images key in sync for any leftover consumers. */
    protected function syncLegacyPosterJson(): void
    {
        $urls = MarketingAd::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (MarketingAd $a) => $a->imageUrl() ? strtok($a->imageUrl(), '?') : null)
            ->filter()
            ->values()
            ->all();

        $this->upsert('poster_images', json_encode($urls), 'json');
        $this->upsert('poster_image', $urls[0] ?? null, 'image');
    }

    protected function legacyPosterList($settings): array
    {
        $raw = $settings['poster_images'] ?? null;
        $list = [];
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $list = $decoded;
            }
        }
        if (! count($list) && ! empty($settings['poster_image'])) {
            $list = [$settings['poster_image']];
        }

        $out = [];
        foreach ($list as $url) {
            $path = strtok(ltrim((string) $url, '/'), '?') ?: '';
            $relative = preg_replace('#^storage/#', '', $path) ?: $path;
            if ($relative && Storage::disk('public')->exists($relative)) {
                try {
                    $out[] = '/storage/'.$relative.'?v='.Storage::disk('public')->lastModified($relative);
                } catch (\Throwable) {
                    $out[] = '/storage/'.$relative;
                }
            }
        }

        return array_values(array_unique($out));
    }

    private function upsert(string $key, ?string $value, string $type = 'text'): void
    {
        \DB::table('marketing_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
