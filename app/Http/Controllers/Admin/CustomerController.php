<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::latest();
        if ($request->filled('q')) {
            $query->where('name', 'like', "%{$request->q}%")
                ->orWhere('phone', 'like', "%{$request->q}%");
        }
        $customers = $query->paginate(20);
        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'anniversary_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'loyalty_points' => 'nullable|integer|min:0',
            'allow_credit' => 'boolean',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);
        $customer = Customer::create($data);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'customer' => $customer]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer created.');
    }

    public function show(Customer $customer)
    {
        $customer->load('orders');
        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'anniversary_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'loyalty_points' => 'nullable|integer|min:0',
            'allow_credit' => 'boolean',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $customer->update($data);
        return redirect()->route('customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
