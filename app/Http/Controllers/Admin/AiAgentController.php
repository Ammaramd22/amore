<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AiAgentService;
use Illuminate\Http\Request;

class AiAgentController extends Controller
{
    public function index()
    {
        $status = AiAgentService::status();
        $isOwner = (bool) auth()->user()?->isSoftwareOwner();

        if (! $status['available'] && ! $isOwner) {
            abort(403, 'Avenque AI Agent is not available for this restaurant yet.');
        }

        return view('admin.ai.index', [
            'status' => $status,
            'isOwner' => $isOwner,
            'currency' => Setting::get('currency_symbol', 'LKR'),
        ]);
    }

    public function toggle(Request $request)
    {
        $status = AiAgentService::status();
        $isOwner = (bool) $request->user()?->isSoftwareOwner();

        if (! $status['available'] && ! $isOwner) {
            return response()->json(['success' => false, 'message' => 'AI pack not available'], 403);
        }

        if (! $status['available']) {
            return response()->json([
                'success' => false,
                'message' => 'Software owner must turn on “Make Avenque AI Available” first.',
            ], 400);
        }

        $enabled = $request->boolean('enabled');
        Setting::set('avenque_ai_enabled', $enabled ? '1' : '0', 'ai', null, 'boolean');

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'ready' => AiAgentService::isReady(),
            'mode' => AiAgentService::mode(),
            'message' => $enabled
                ? (AiAgentService::hasApiKey() ? 'Avenque AI enabled (Gemini)' : 'Avenque AI enabled (local mode — no API key)')
                : 'Avenque AI Agent disabled',
        ]);
    }

    public function chat(Request $request, AiAgentService $agent)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:4000',
        ]);

        if (! AiAgentService::isAvailable() && ! $request->user()?->isSoftwareOwner()) {
            return response()->json(['success' => false, 'error' => 'AI not available'], 403);
        }

        $result = $agent->chat(
            $request->input('message'),
            $request->input('history', [])
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
