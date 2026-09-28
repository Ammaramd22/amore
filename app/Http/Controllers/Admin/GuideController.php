<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InstallerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GuideController extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->get('lang', \App\Models\Setting::get('guide_default_lang', 'en'));
        if (! in_array($lang, ['en', 'ta', 'si'], true)) {
            $lang = 'en';
        }

        $guides = [
            'en' => $this->loadGuide('en'),
            'ta' => $this->loadGuide('ta'),
            'si' => $this->loadGuide('si'),
        ];

        return view('admin.guide.index', compact('lang', 'guides'));
    }

    public function freshStart(Request $request, InstallerService $installer)
    {
        $user = $request->user();
        if (! $user?->isSoftwareOwner() && ! $user?->can('settings.system')) {
            abort(403);
        }

        $data = $request->validate([
            'confirm' => 'required|in:FRESH START',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:6|max:100',
            'business_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'pin' => 'nullable|string|min:4|max:8',
        ]);

        $result = $installer->freshStart([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'business_name' => $data['business_name'],
            'phone' => $data['phone'] ?? null,
            'pin' => $data['pin'] ?? '1111',
        ]);

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Fresh start done. Sign in with the new admin account.');
    }

    public function dismissWelcome(Request $request)
    {
        \App\Models\Setting::set('welcome_show', '0', 'system');

        return response()->json(['ok' => true]);
    }

    /** @return array{title:string,sections:list<array{h:string,p:string}>} */
    protected function loadGuide(string $lang): array
    {
        $path = resource_path("guides/{$lang}.php");
        if (! File::exists($path)) {
            $path = resource_path('guides/en.php');
        }

        /** @var array{title:string,sections:list<array{h:string,p:string}>} $data */
        $data = include $path;

        return $data;
    }
}
