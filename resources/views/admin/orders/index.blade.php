@extends('layouts.admin')
@section('title', 'Orders')
@section('page_title', 'Orders')

@section('content')
@php $currency = \App\Models\Setting::get('currency_symbol', 'LKR'); @endphp

<form method="GET" class="row g-2 align-items-end index-filter-form">
    <div class="col-12 col-md-3">
        <label class="form-label" for="filter_q">Search</label>
        <input type="text" id="filter_q" name="q" class="form-control form-control-sm"
               placeholder="Invoice, table, customer, phone…"
               value="{{ request('q') }}">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label" for="filter_type">Type</label>
        <select id="filter_type" name="type" class="form-select form-select-sm">
            <option value="">All Types</option>
            <option value="dine_in" {{ request('type')=='dine_in'?'selected':'' }}>Dine-in</option>
            <option value="takeaway" {{ request('type')=='takeaway'?'selected':'' }}>Takeaway</option>
            <option value="delivery" {{ request('type')=='delivery'?'selected':'' }}>Delivery</option>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label" for="filter_status">Status</label>
        <select id="filter_status" name="status" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach(['pending','preparing','ready','served','completed','cancelled'] as $st)
            <option value="{{ $st }}" {{ request('status')==$st?'selected':'' }}>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label" for="filter_from">From</label>
        <input type="date" id="filter_from" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
    </div>
    <div class="col-6 col-md-1">
        <label class="form-label" for="filter_to">To</label>
        <input type="date" id="filter_to" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
    </div>
    <div class="col-12 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-search me-1"></i>Search</button>
        @if(request()->hasAny(['q','type','status','from','to']))
        <a href="{{ route('orders.index') }}" class="btn btn-secondary btn-sm flex-fill" title="Clear"><i class="fas fa-undo"></i></a>
        @endif
    </div>
</form>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Orders</h3>
        <span class="badge-soft">{{ $orders->total() }} total</span>
    </div>
    <div class="card-body p-0">
        {{-- Desktop table --}}
        <div class="table-responsive d-none d-md-block">
            <table class="bulk-table table mb-0 orders-table" data-resource="orders" data-export="orders" data-can-delete="1">
                <thead>
                    <tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th>
                        <th>Order #</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $order->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration) }}</td>
                        <td>
                            <button type="button" class="order-link" onclick="openOrderModal({{ $order->id }})">
                                {{ $order->order_number }}
                            </button>
                        </td>
                        <td>
                            <span class="badge bg-{{ $order->order_type=='dine_in'?'success':($order->order_type=='takeaway'?'info':'warning') }}">
                                {{ ucfirst(str_replace('_',' ',$order->order_type)) }}
                            </span>
                        </td>
                        <td>{{ $order->table?->name ?? $order->customer?->name ?? 'Walk-in' }}</td>
                        <td class="fw-bold">{{ $currency }} {{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            <span class="badge-soft {{ $order->payment_status=='paid'?'success':'' }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-soft {{ $order->status=='completed'?'success':($order->status=='cancelled'?'danger':'') }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="text-muted" style="white-space:nowrap;">{{ \App\Models\Setting::formatDateTime($order->created_at, 'Y-m-d H:i') }}</td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><button type="button" class="dropdown-item" onclick="openOrderModal({{ $order->id }})"><i class="fas fa-eye"></i> View</button></li>
                                <li><a class="dropdown-item" href="{{ route('orders.edit', $order) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('orders.destroy', $order) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10"><div class="empty-state"><i class="fas fa-receipt"></i>No orders found</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="orders-mobile d-md-none">
            @forelse($orders as $order)
            <button type="button" class="order-card-mobile" onclick="openOrderModal({{ $order->id }})">
                <div class="om-top">
                    <span class="om-number">{{ $order->order_number }}</span>
                    <span class="badge bg-{{ $order->order_type=='dine_in'?'success':($order->order_type=='takeaway'?'info':'warning') }}">
                        {{ ucfirst(str_replace('_',' ',$order->order_type)) }}
                    </span>
                </div>
                <div class="om-mid">
                    <span>{{ $order->table?->name ?? $order->customer?->name ?? 'Walk-in' }}</span>
                    <strong>{{ $currency }} {{ number_format($order->total_amount, 2) }}</strong>
                </div>
                <div class="om-bottom">
                    <span class="badge-soft {{ $order->payment_status=='paid'?'success':'' }}">{{ ucfirst($order->payment_status) }}</span>
                    <span class="badge-soft {{ $order->status=='completed'?'success':($order->status=='cancelled'?'danger':'') }}">{{ ucfirst($order->status) }}</span>
                    <span class="om-time">{{ \App\Models\Setting::formatDateTime($order->created_at, 'Y-m-d H:i') }}</span>
                </div>
            </button>
            @empty
            <div class="empty-state"><i class="fas fa-receipt"></i>No orders found</div>
            @endforelse
        </div>
    </div>
    <div class="card-footer">{{ $orders->withQueryString()->links() }}</div>
</div>

{{-- Order detail modal --}}
<div class="modal fade" id="orderViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header respos">
                <div>
                    <h5 class="modal-title mb-0" id="ovTitle">Order</h5>
                    <small class="text-white-50" id="ovMeta"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ovBody">
                <div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer flex-wrap gap-2" id="ovFooter"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .order-link {
        border: 0; background: transparent; padding: 0;
        font-weight: 750; color: #c2410c; text-decoration: none;
        letter-spacing: -0.01em; cursor: pointer;
    }
    .order-link:hover { color: #ea580c; text-decoration: underline; text-underline-offset: 3px; }
    .orders-mobile { padding: 0.75rem; display: grid; gap: 0.65rem; }
    .order-card-mobile {
        width: 100%; text-align: left; border: 1px solid #e7e5e4; border-radius: 14px;
        background: #fff; padding: 0.9rem 1rem; cursor: pointer;
        box-shadow: 0 1px 2px rgba(28,25,23,0.04);
        transition: 0.15s ease;
    }
    .order-card-mobile:active { transform: scale(0.99); background: #fff7ed; }
    .om-top { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-bottom: 0.45rem; }
    .om-number { font-weight: 800; color: #c2410c; font-size: 0.95rem; }
    .om-mid { display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; margin-bottom: 0.55rem; color: #44403c; font-size: 0.9rem; }
    .om-mid strong { color: #1c1917; font-size: 1rem; }
    .om-bottom { display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem; }
    .om-time { margin-left: auto; font-size: 0.75rem; color: #a8a29e; }
    .ov-section-title { font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #a8a29e; margin: 1rem 0 0.5rem; }
    .ov-row { display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0; font-size: 0.92rem; }
    .ov-row.total { font-weight: 800; font-size: 1.05rem; border-top: 1px dashed #e7e5e4; margin-top: 0.4rem; padding-top: 0.65rem; color: #c2410c; }
    .ov-item { display: flex; justify-content: space-between; gap: 0.75rem; padding: 0.55rem 0; border-bottom: 1px solid #f5f5f4; }
    .ov-item:last-child { border-bottom: 0; }
    .ov-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.75rem; }
</style>
@endpush

@push('scripts')
<script>
const money = (n, c) => `${c || 'LKR'} ${Number(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

function openOrderModal(id) {
    const modalEl = document.getElementById('orderViewModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    document.getElementById('ovTitle').textContent = 'Loading…';
    document.getElementById('ovMeta').textContent = '';
    document.getElementById('ovBody').innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    document.getElementById('ovFooter').innerHTML = '';
    modal.show();

    fetch(`/orders/${id}?format=json`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => {
        if (!r.ok) throw new Error('Failed to load order');
        return r.json();
    })
    .then(o => renderOrderModal(o))
    .catch(err => {
        document.getElementById('ovBody').innerHTML = `<div class="alert alert-danger mb-0">${err.message || 'Could not load order'}</div>`;
    });
}

function renderOrderModal(o) {
    const cur = o.currency || 'LKR';
    document.getElementById('ovTitle').textContent = o.order_number;
    document.getElementById('ovMeta').textContent = `${o.created_at || ''} · ${o.order_type_label || ''}`;

    const itemsHtml = (o.items || []).length
        ? o.items.map(i => `
            <div class="ov-item">
                <div>
                    <div class="fw-semibold">${esc(i.name)}</div>
                    <small class="text-muted">${i.quantity} × ${money(i.unit_price, cur)}</small>
                </div>
                <div class="fw-bold">${money(i.total_price, cur)}</div>
            </div>
        `).join('')
        : '<div class="text-muted py-2">No items</div>';

    const paymentsHtml = (o.payments || []).length
        ? o.payments.map(p => `
            <div class="ov-row">
                <span>${esc(p.method)}${p.reference ? ` · ${esc(p.reference)}` : ''}</span>
                <strong>${money(p.amount, cur)}</strong>
            </div>
        `).join('')
        : '<div class="text-muted">No payments</div>';

    document.getElementById('ovBody').innerHTML = `
        <div class="ov-chips">
            <span class="badge bg-info">${esc(o.order_type_label || '')}</span>
            <span class="badge-soft ${o.payment_status === 'paid' ? 'success' : ''}">${esc(o.payment_status || '')}</span>
            <span class="badge-soft ${o.status === 'completed' ? 'success' : (o.status === 'cancelled' ? 'danger' : '')}">${esc(o.status || '')}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="ov-section-title">Items</div>
                ${itemsHtml}
                <div class="ov-section-title">Payments</div>
                ${paymentsHtml}
            </div>
            <div class="col-md-5">
                <div class="ov-section-title">Summary</div>
                <div class="ov-row"><span>Subtotal</span><span>${money(o.subtotal, cur)}</span></div>
                ${o.discount_amount > 0 ? `<div class="ov-row"><span>Discount</span><span class="text-danger">-${money(o.discount_amount, cur)}</span></div>` : ''}
                ${o.tax_enabled ? `<div class="ov-row"><span>${esc(o.tax_name || 'Tax')}</span><span>${money(o.tax_amount, cur)}</span></div>` : ''}
                ${o.service_charge > 0 ? `<div class="ov-row"><span>Service</span><span>${money(o.service_charge, cur)}</span></div>` : ''}
                ${o.delivery_charge > 0 ? `<div class="ov-row"><span>Delivery</span><span>${money(o.delivery_charge, cur)}</span></div>` : ''}
                <div class="ov-row total"><span>Total</span><span>${money(o.total_amount, cur)}</span></div>
                <div class="ov-row"><span>Paid</span><span>${money(o.paid_amount, cur)}</span></div>
                <div class="ov-section-title">Details</div>
                <div class="ov-row"><span>Customer</span><span>${esc(o.customer || 'Walk-in')}</span></div>
                <div class="ov-row"><span>Table</span><span>${esc(o.table || '—')}</span></div>
                <div class="ov-row"><span>Waiter</span><span>${esc(o.waiter || '—')}</span></div>
                <div class="ov-row"><span>Cashier</span><span>${esc(o.cashier || '—')}</span></div>
                ${o.notes ? `<div class="mt-2"><small class="text-muted">Notes</small><div>${esc(o.notes)}</div></div>` : ''}
            </div>
        </div>
    `;

    document.getElementById('ovFooter').innerHTML = `
        <a href="${o.print_receipt_url}" target="_blank" class="btn btn-secondary"><i class="fas fa-print me-1"></i>Receipt</a>
        <a href="${o.print_kot_url}" target="_blank" class="btn btn-secondary"><i class="fas fa-utensils me-1"></i>KOT</a>
        <a href="${o.edit_url}" class="btn btn-primary"><i class="fas fa-edit me-1"></i>Edit</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    `;
}

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
</script>
@endpush
