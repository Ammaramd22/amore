<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Cheque;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\AccountService;
use App\Services\ChequeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChequeController extends Controller
{
    public function __construct(protected ChequeService $cheques) {}

    protected function ensureEnabled()
    {
        if (! ChequeService::enabled()) {
            abort(403, 'Cheque management is disabled. Ask the software owner to enable it in Settings.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureEnabled();

        $query = Cheque::with(['supplier', 'purchase', 'account']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier')) {
            $query->where('supplier_id', $request->supplier);
        }
        if ($request->filled('q')) {
            $q = '%'.$request->q.'%';
            $query->where(function ($inner) use ($q) {
                $inner->where('cheque_number', 'like', $q)
                    ->orWhere('bank_name', 'like', $q)
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $q));
            });
        }
        if ($request->filled('from')) {
            $query->whereDate('due_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('due_date', '<=', $request->to);
        }

        $pendingTotal = (clone $query)->where('status', 'pending')->sum('amount');
        $overdueCount = (clone $query)->where('status', 'pending')->whereDate('due_date', '<', today())->count();
        $cheques = $query->latest('due_date')->paginate(20)->withQueryString();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $accounts = Account::active()->where('type', 'bank')->orderBy('name')->get();
        $purchases = Purchase::with('supplier')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->latest()
            ->limit(100)
            ->get();
        $currency = Setting::get('currency_symbol', 'LKR');
        $openCreateModal = $request->boolean('create') || session('errors');

        return view('admin.cheques.index', compact(
            'cheques',
            'suppliers',
            'accounts',
            'purchases',
            'pendingTotal',
            'overdueCount',
            'currency',
            'openCreateModal'
        ));
    }

    public function create(Request $request)
    {
        $this->ensureEnabled();

        return redirect()->route('cheques.index', ['create' => 1]);
    }

    public function store(Request $request)
    {
        $this->ensureEnabled();

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'account_id' => 'nullable|exists:accounts,id',
            'cheque_number' => 'required|string|max:100',
            'bank_name' => 'nullable|string|max:150',
            'branch_name' => 'nullable|string|max:150',
            'amount' => 'required|numeric|min:0.01',
            'cheque_date' => 'nullable|date',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
            'apply_to_purchase' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $payment = null;
            $purchaseId = $data['purchase_id'] ?? null;

            if ($purchaseId && $request->boolean('apply_to_purchase', true)) {
                $purchase = Purchase::findOrFail($purchaseId);
                if ((int) $purchase->supplier_id !== (int) $data['supplier_id']) {
                    throw new \RuntimeException('Purchase does not belong to this supplier.');
                }
                $balance = max(0, (float) $purchase->total_amount - (float) $purchase->paid_amount);
                $amount = min((float) $data['amount'], $balance ?: (float) $data['amount']);

                $payment = SupplierPayment::create([
                    'supplier_id' => $data['supplier_id'],
                    'purchase_id' => $purchase->id,
                    'method' => 'cheque',
                    'amount' => $amount,
                    'reference_number' => $data['cheque_number'],
                    'payment_date' => $data['cheque_date'] ?? $data['due_date'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                $newPaid = (float) $purchase->paid_amount + $amount;
                $paymentStatus = $newPaid >= (float) $purchase->total_amount ? 'paid' : ($newPaid > 0 ? 'partial' : 'unpaid');
                $purchase->update(['paid_amount' => $newPaid, 'payment_status' => $paymentStatus]);

                $data['amount'] = $amount;
            }

            $cheque = Cheque::create([
                'supplier_id' => $data['supplier_id'],
                'purchase_id' => $purchaseId,
                'supplier_payment_id' => $payment?->id,
                'account_id' => $data['account_id'] ?? Account::defaultBank()?->id,
                'cheque_number' => $data['cheque_number'],
                'bank_name' => $data['bank_name'] ?? null,
                'branch_name' => $data['branch_name'] ?? null,
                'amount' => $data['amount'],
                'cheque_date' => $data['cheque_date'] ?? null,
                'due_date' => $data['due_date'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('cheques.show', $cheque)->with('success', 'Cheque recorded as pending.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('cheques.index', ['create' => 1])
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Cheque $cheque)
    {
        $this->ensureEnabled();
        $cheque->load(['supplier', 'purchase', 'account', 'payment', 'creator']);
        $accounts = Account::active()->where('type', 'bank')->orderBy('name')->get();
        $currency = Setting::get('currency_symbol', 'LKR');

        return view('admin.cheques.show', compact('cheque', 'accounts', 'currency'));
    }

    public function edit(Cheque $cheque)
    {
        $this->ensureEnabled();
        if ($cheque->status !== 'pending') {
            return redirect()->route('cheques.show', $cheque)->with('error', 'Only pending cheques can be edited.');
        }

        return redirect()->route('cheques.show', $cheque)->with('open_edit', true);
    }

    public function update(Request $request, Cheque $cheque)
    {
        $this->ensureEnabled();
        if ($cheque->status !== 'pending') {
            return back()->with('error', 'Only pending cheques can be edited.');
        }

        $data = $request->validate([
            'cheque_number' => 'required|string|max:100',
            'bank_name' => 'nullable|string|max:150',
            'branch_name' => 'nullable|string|max:150',
            'account_id' => 'nullable|exists:accounts,id',
            'cheque_date' => 'nullable|date',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $cheque->update($data);
        if ($cheque->payment) {
            $cheque->payment->update([
                'reference_number' => $data['cheque_number'],
                'payment_date' => $data['cheque_date'] ?? $cheque->payment->payment_date,
            ]);
        }

        return redirect()->route('cheques.show', $cheque)->with('success', 'Cheque updated.');
    }

    public function destroy(Cheque $cheque)
    {
        $this->ensureEnabled();
        if ($cheque->status === 'cleared') {
            return back()->with('error', 'Cleared cheques cannot be deleted. Return or keep for records.');
        }

        if ($cheque->status === 'pending') {
            $this->cheques->cancel($cheque);
        }

        $cheque->delete();

        return redirect()->route('cheques.index')->with('success', 'Cheque removed.');
    }

    public function clear(Request $request, Cheque $cheque)
    {
        $this->ensureEnabled();
        $data = $request->validate([
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        try {
            $this->cheques->clear($cheque, $data['account_id'] ?? null);

            return redirect()->route('cheques.index')->with('success', 'Cheque cleared and posted to accounts.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function returnCheque(Request $request, Cheque $cheque)
    {
        $this->ensureEnabled();
        $data = $request->validate([
            'returned_reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->cheques->returnCheque($cheque, $data['returned_reason'] ?? null);

            return redirect()->route('cheques.index')->with('success', 'Cheque marked as returned.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
