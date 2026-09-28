@extends('layouts.admin')
@section('title', 'New Billiards Booking')
@section('page_title', 'New Booking')

@section('content')
@include('admin.billiards._styles')
@php $selected = (int) ($selectedTableId ?? 0); @endphp
<div class="bil-shell">
    <div class="bil-inner">
        <div class="bil-page">
            <div class="bil-top" style="margin-bottom:1rem">
                <div class="bil-brand">
                    <div class="bil-mark" aria-hidden="true"></div>
                    <div>
                        <h1>New booking</h1>
                        <p>Pick a table · hours price auto-fills</p>
                    </div>
                </div>
                <div class="bil-actions">
                    <a href="{{ route('billiards.desk') }}" class="bil-btn bil-btn--ghost">← Desk</a>
                </div>
            </div>

            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

            <div class="bil-card">
                <form method="post" action="{{ route('billiards.bookings.store') }}" id="bilBookingForm">
                    @csrf
                    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="table_id">Table</label>
                            <select name="billiard_table_id" id="table_id" class="form-select" required>
                                <option value="">Select…</option>
                                @foreach($tables as $t)
                                    <option value="{{ $t->id }}" data-rate="{{ $t->hourly_rate }}"
                                        @selected(old('billiard_table_id', $selected)==$t->id)>
                                        {{ $t->name }} ({{ $t->typeLabel() }}) — {{ $currency }} {{ number_format((float)$t->hourly_rate,0) }}/hr
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="hours">Hours</label>
                            <input type="number" step="0.25" min="0.25" max="24" name="hours" id="hours" class="form-control" value="{{ old('hours', 1) }}" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="hourly_rate">Hourly rate</label>
                            <input type="number" step="0.01" min="0" name="hourly_rate" id="hourly_rate" class="form-control" value="{{ old('hourly_rate') }}" placeholder="Auto">
                        </div>
                        <div class="col-12">
                            <div class="bil-price">Total <span id="priceOut">{{ $currency }} 0.00</span></div>
                        </div>
                        <div class="col-md-6">
                            <label>Start</label>
                            <input type="datetime-local" name="scheduled_start" class="form-control" value="{{ old('scheduled_start', now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label>Source</label>
                            <select name="source" class="form-select" required>
                                @foreach(['walk_in'=>'Walk-in','phone'=>'Phone','online'=>'Online','admin'=>'Admin'] as $k=>$l)
                                    <option value="{{ $k }}" @selected(old('source','walk_in')===$k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 position-relative">
                            <label for="custSearch">Find restaurant customer</label>
                            <input type="search" id="custSearch" class="form-control" placeholder="Search name or phone…" autocomplete="off">
                            <div class="cust-results" id="custResults"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="customer_name">Customer name</label>
                            <input name="customer_name" id="customer_name" class="form-control" value="{{ old('customer_name') }}" placeholder="New or existing">
                        </div>
                        <div class="col-md-6">
                            <label for="customer_phone">Phone</label>
                            <input name="customer_phone" id="customer_phone" class="form-control" value="{{ old('customer_phone') }}" placeholder="07…">
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold" style="font-weight:600"><input type="checkbox" name="create_customer" value="1" checked> Save as restaurant customer if new</label>
                        </div>
                        <div class="col-12">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold" style="font-weight:600"><input type="checkbox" name="start_now" value="1"> Start session now</label>
                        </div>
                        <div class="col-12">
                            <button class="bil-btn bil-btn--primary w-100" style="min-height:48px;font-size:1rem">Save booking</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    const cur = @json($currency);
    const table = document.getElementById('table_id');
    const hours = document.getElementById('hours');
    const rate = document.getElementById('hourly_rate');
    const out = document.getElementById('priceOut');
    function syncRate(){
        const opt = table.selectedOptions[0];
        if(opt && opt.dataset.rate && !rate.dataset.manual){ rate.value = opt.dataset.rate; }
        calc();
    }
    function calc(){
        const h = parseFloat(hours.value||0)||0;
        const r = parseFloat(rate.value||0)||0;
        out.textContent = cur + ' ' + (h*r).toFixed(2);
    }
    table.addEventListener('change', ()=>{ rate.dataset.manual=''; syncRate(); });
    rate.addEventListener('input', ()=>{ rate.dataset.manual='1'; calc(); });
    hours.addEventListener('input', calc);
    syncRate();

    const search = document.getElementById('custSearch');
    const results = document.getElementById('custResults');
    let t=null;
    search.addEventListener('input', ()=>{
        clearTimeout(t);
        const q = search.value.trim();
        if(q.length<2){ results.style.display='none'; return; }
        t=setTimeout(async()=>{
            const r = await fetch(@json(route('billiards.customers.lookup'))+'?q='+encodeURIComponent(q));
            const rows = await r.json();
            results.innerHTML = rows.map(c=>`<button type="button" data-id="${c.id}" data-name="${c.name||''}" data-phone="${c.phone||''}"><strong>${c.name||'—'}</strong><br><small>${c.phone||''}</small></button>`).join('') || '<div class="p-2 text-muted">No matches</div>';
            results.style.display='block';
        },250);
    });
    results.addEventListener('click', e=>{
        const btn = e.target.closest('button[data-id]');
        if(!btn) return;
        document.getElementById('customer_id').value = btn.dataset.id;
        document.getElementById('customer_name').value = btn.dataset.name;
        document.getElementById('customer_phone').value = btn.dataset.phone;
        results.style.display='none';
        search.value = btn.dataset.name;
    });
})();
</script>
@endsection
