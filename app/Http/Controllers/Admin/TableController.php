<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use App\Models\RestaurantTable;
use App\Models\Setting;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $tables = RestaurantTable::with('floor')->latest()->paginate(20);
        $floors = Floor::active()->get();

        return view('admin.tables.index', compact('tables', 'floors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:20|unique:tables',
            'capacity' => 'required|integer|min:1',
            'shape' => 'required|in:square,round,rectangle',
        ]);
        RestaurantTable::create($data);

        return redirect()->route('tables.index')->with('success', 'Table created.');
    }

    public function update(Request $request, RestaurantTable $table)
    {
        $data = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:20|unique:tables,number,'.$table->id,
            'capacity' => 'required|integer|min:1',
            'shape' => 'required|in:square,round,rectangle',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $table->update($data);

        return redirect()->route('tables.index')->with('success', 'Table updated.');
    }

    public function destroy(RestaurantTable $table)
    {
        $table->delete();

        return redirect()->route('tables.index')->with('success', 'Table deleted.');
    }

    public function regenerateCode(RestaurantTable $table)
    {
        $code = $table->regenerateQrCode();

        return redirect()->route('tables.index')->with('success', $table->name.' code is now '.$code);
    }

    public function qrCard(RestaurantTable $table)
    {
        $table->load('floor');
        if (! $table->qr_code) {
            $table->regenerateQrCode();
        }

        $menuUrl = route('qr.menu', $table);

        return view('admin.tables.qr-card', [
            'table' => $table,
            'companyName' => Setting::get('company_name', 'Restaurant'),
            'menuUrl' => $menuUrl,
            'qrImage' => 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&margin=10&data='.urlencode($menuUrl),
            'whatsappUrl' => 'https://wa.me/?text='.rawurlencode(
                'Scan to order at '.Setting::get('company_name', 'our restaurant').' — '.$table->name.': '.$menuUrl
                .($table->qr_code ? ' (code '.$table->qr_code.')' : '')
            ),
        ]);
    }

    public function sendQrLink(Request $request, RestaurantTable $table)
    {
        $data = $request->validate([
            'phone' => 'required|string|min:9|max:20',
            'channel' => 'nullable|in:whatsapp,sms,both',
        ]);

        if (! $table->qr_code) {
            $table->regenerateQrCode();
        }

        $menuUrl = route('qr.menu', $table);
        $company = Setting::get('company_name', 'Restaurant');
        $msg = $company.' — '.$table->name." menu: {$menuUrl}"
            .($table->qr_code ? ' Code: '.$table->qr_code : '');

        $channel = $data['channel'] ?? 'whatsapp';
        $sent = false;
        $errors = [];

        if (in_array($channel, ['whatsapp', 'both'], true)) {
            if (\App\Services\NotificationService::whatsappConfigured()) {
                $sent = \App\Services\NotificationService::sendWhatsApp($data['phone'], $msg, 'table-qr:'.$table->id) || $sent;
            } else {
                $errors[] = 'WhatsApp not configured in Settings';
            }
        }

        if (in_array($channel, ['sms', 'both'], true)) {
            if (\App\Services\NotificationService::smsConfigured()) {
                $sent = \App\Services\NotificationService::sendSms($data['phone'], $msg, 'table-qr:'.$table->id) || $sent;
            } else {
                $errors[] = 'SMS not configured in Settings';
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $sent,
                'message' => $sent ? 'QR menu link sent' : ('Could not send'.($errors ? ': '.implode(', ', $errors) : '')),
                'menu_url' => $menuUrl,
                'whatsapp_share' => 'https://wa.me/?text='.rawurlencode($msg),
            ], $sent ? 200 : 422);
        }

        return back()->with($sent ? 'success' : 'error', $sent
            ? 'QR menu link sent to '.$data['phone']
            : ('Could not send'.($errors ? ': '.implode(', ', $errors) : '')));
    }
}
