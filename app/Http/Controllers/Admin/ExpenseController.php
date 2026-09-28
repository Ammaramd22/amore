<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Expense;
use App\Services\AccountService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(protected AccountService $accounts)
    {
    }

    public function index(Request $request)
    {
        $query = Expense::with(['creator', 'account'])->latest();
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->to);
        }
        $expenses = $query->paginate(20);
        return view('admin.expenses.index', compact('expenses'));
    }

    public function create()
    {
        $accounts = Account::active()->orderBy('name')->get();
        return view('admin.expenses.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
        ]);
        $data['created_by'] = auth()->id();
        $data['account_id'] = $data['account_id'] ?? Account::defaultCash()?->id;

        $expense = Expense::create($data);
        $this->accounts->postExpense($expense);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        $accounts = Account::active()->orderBy('name')->get();
        return view('admin.expenses.edit', compact('expense', 'accounts'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $this->accounts->reverseSource($expense);
        $expense->update($data);
        $this->accounts->postExpense($expense->fresh());

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $this->accounts->reverseSource($expense);
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}
