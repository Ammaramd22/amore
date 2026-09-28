<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::latest();
        if ($request->filled('q')) {
            $q = "%{$request->q}%";
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', $q)
                      ->orWhere('phone', 'like', $q)
                      ->orWhere('city', 'like', $q)
                      ->orWhere('contact_person', 'like', $q);
            });
        }
        $suppliers = $query->paginate(20)->withQueryString();
        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('admin.suppliers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:50',
        ]);
        $supplier = Supplier::create($data);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'supplier' => $supplier]);
        }
        return redirect()->route('suppliers.index')->with('success', 'Supplier created.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('purchases.items');
        return view('admin.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        $supplier->update($data);
        return redirect()->route('suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }
}
