<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Services\AccountService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(protected AccountService $accounts)
    {
    }

    public function index()
    {
        $accounts = Account::query()->orderBy('type')->orderBy('name')->get();
        $totals = [
            'cash' => $accounts->where('type', 'cash')->sum('current_balance'),
            'bank' => $accounts->where('type', 'bank')->sum('current_balance'),
            'all' => $accounts->sum('current_balance'),
        ];

        return view('admin.accounts.index', compact('accounts', 'totals'));
    }

    public function create()
    {
        return view('admin.accounts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['current_balance'] = $data['opening_balance'] ?? 0;
        $data['payment_methods'] = $this->normalizeMethods($request);

        Account::create($data);

        return redirect()->route('accounts.index')->with('success', 'Account created.');
    }

    public function show(Request $request, Account $account)
    {
        $query = $account->transactions()->with('creator')->latest('transacted_at')->latest('id');

        if ($request->filled('from')) {
            $query->whereDate('transacted_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('transacted_at', '<=', $request->to);
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        $transactions = $query->paginate(30)->withQueryString();
        $otherAccounts = Account::active()->where('id', '!=', $account->id)->orderBy('name')->get();

        return view('admin.accounts.show', compact('account', 'transactions', 'otherAccounts'));
    }

    public function edit(Account $account)
    {
        return view('admin.accounts.edit', compact('account'));
    }

    public function update(Request $request, Account $account)
    {
        $data = $this->validated($request, $account);
        $data['payment_methods'] = $this->normalizeMethods($request);

        $openingChanged = array_key_exists('opening_balance', $data)
            && round((float) $data['opening_balance'], 2) !== round((float) $account->opening_balance, 2);

        $account->update(collect($data)->except('opening_balance')->all());

        if ($openingChanged) {
            $this->accounts->setOpeningBalance($account, (float) $data['opening_balance']);
        }

        return redirect()->route('accounts.show', $account)->with('success', 'Account updated.');
    }

    public function destroy(Account $account)
    {
        if ($account->is_system) {
            return back()->with('error', 'System accounts cannot be deleted.');
        }
        if ($account->transactions()->exists()) {
            return back()->with('error', 'Account has transactions. Deactivate it instead.');
        }

        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Account deleted.');
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'from_account_id' => 'required|integer|exists:accounts,id',
            'to_account_id' => 'required|integer|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $from = Account::findOrFail($data['from_account_id']);
        $to = Account::findOrFail($data['to_account_id']);

        try {
            $this->accounts->transfer($from, $to, (float) $data['amount'], $data['description'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer recorded.');
    }

    public function storeTransaction(Request $request, Account $account)
    {
        $data = $request->validate([
            'direction' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
        ]);

        $this->accounts->postManual(
            $account,
            $data['direction'],
            (float) $data['amount'],
            $data['description'],
            $data['reference'] ?? null
        );

        return back()->with('success', 'Transaction recorded.');
    }

    protected function validated(Request $request, ?Account $account = null): array
    {
        return $request->validate([
            'code' => 'required|string|max:30|unique:accounts,code,'.($account?->id ?? 'NULL').',id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,other',
            'opening_balance' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]) + [
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    protected function normalizeMethods(Request $request): array
    {
        $methods = $request->input('payment_methods', []);
        if (! is_array($methods)) {
            $methods = [];
        }

        $allowed = ['cash', 'card', 'bank_transfer', 'online'];

        return array_values(array_intersect($methods, $allowed));
    }
}
