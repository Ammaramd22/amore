<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use Illuminate\Http\Request;

class DeliveryPartnerController extends Controller
{
    public function index()
    {
        $partners = DeliveryPartner::orderBy('name')->paginate(20);
        return view('admin.delivery-partners.index', compact('partners'));
    }

    public function create()
    {
        return view('admin.delivery-partners.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:delivery_partners',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_type' => 'required|in:percentage,fixed',
            'settlement_cycle' => 'nullable|in:weekly,on_receive,per_order,daily',
            'collection_type' => 'nullable|in:own,partner',
            'tracks_settlement' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['tracks_settlement'] = $request->boolean('tracks_settlement', true);
        $validated['settlement_cycle'] = $validated['settlement_cycle'] ?? 'weekly';
        $validated['collection_type'] = $validated['collection_type'] ?? 'partner';

        DeliveryPartner::create($validated);

        return redirect()->route('delivery-partners.index')
            ->with('success', 'Delivery partner created successfully');
    }

    public function edit(DeliveryPartner $deliveryPartner)
    {
        return view('admin.delivery-partners.edit', compact('deliveryPartner'));
    }

    public function update(Request $request, DeliveryPartner $deliveryPartner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:delivery_partners,code,' . $deliveryPartner->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_type' => 'required|in:percentage,fixed',
            'settlement_cycle' => 'nullable|in:weekly,on_receive,per_order,daily',
            'collection_type' => 'nullable|in:own,partner',
            'tracks_settlement' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['tracks_settlement'] = $request->boolean('tracks_settlement', true);
        $validated['settlement_cycle'] = $validated['settlement_cycle'] ?? 'weekly';
        $validated['collection_type'] = $validated['collection_type'] ?? 'partner';

        $deliveryPartner->update($validated);

        return redirect()->route('delivery-partners.index')
            ->with('success', 'Delivery partner updated successfully');
    }

    public function destroy(DeliveryPartner $deliveryPartner)
    {
        $deliveryPartner->delete();
        return redirect()->route('delivery-partners.index')
            ->with('success', 'Delivery partner deleted successfully');
    }

    public function toggle(DeliveryPartner $deliveryPartner)
    {
        $deliveryPartner->update(['is_active' => ! $deliveryPartner->is_active]);

        return redirect()->route('delivery-partners.index')
            ->with('success', $deliveryPartner->is_active ? 'Partner activated.' : 'Partner deactivated.');
    }
}
