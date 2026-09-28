@extends('layouts.admin')
@section('title', 'Delivery Partners')
@section('page_title', 'Delivery Partners')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Partners</h3>
        <a href="{{ route('delivery-partners.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Partner</a>
        <a href="{{ route('delivery-partners.ledger') }}" class="btn btn-warning btn-sm"><i class="fas fa-book me-1"></i>Partner Ledger</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table mb-0" data-resource="delivery-partners" data-export="delivery-partners" data-can-delete="1">
                <thead>
                    <tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th>
                        <th>Partner</th>
                        <th>Code</th>
                        <th>Contact</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th>Orders</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partners as $partner)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $partner->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($partners->currentPage() - 1) * $partners->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $partner->name }}</td>
                        <td><code>{{ $partner->code }}</code></td>
                        <td>
                            @if($partner->phone)
                                <div><i class="fas fa-phone text-muted me-1"></i>{{ $partner->phone }}</div>
                            @endif
                            @if($partner->email)
                                <div><i class="fas fa-envelope text-muted me-1"></i>{{ $partner->email }}</div>
                            @endif
                            @if(!$partner->phone && !$partner->email)
                                —
                            @endif
                        </td>
                        <td>
                            {{ number_format($partner->commission_rate, 2) }}
                            {{ $partner->commission_type === 'percentage' ? '%' : 'LKR' }}
                            <div class="small text-muted">
                                {{ ($partner->collection_type ?? 'partner') === 'own' ? 'Our delivery' : 'Partner remits' }}
                                · {{ str_replace('_', ' ', $partner->settlement_cycle ?? 'weekly') }}
                            </div>
                        </td>
                        <td>
                            <span class="badge-soft {{ $partner->is_active ? 'success' : 'muted' }}">
                                {{ $partner->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $partner->orders()->count() }}</td>
                        <td class="text-end">
                            <x-row-actions>
                                <li>
                                    <a class="dropdown-item" href="{{ route('delivery-partners.ledger', ['partner_id' => $partner->id]) }}">
                                        <i class="fas fa-book"></i> Open Ledger
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('delivery-partners.toggle', $partner) }}">
                                        <i class="fas fa-{{ $partner->is_active ? 'toggle-on' : 'toggle-off' }}"></i> {{ $partner->is_active ? 'Deactivate' : 'Activate' }}
                                    </a>
                                </li>
                                <li><a class="dropdown-item" href="{{ route('delivery-partners.edit', $partner) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('delivery-partners.destroy', $partner) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9"><div class="empty-state"><i class="fas fa-motorcycle"></i>No delivery partners yet</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($partners->hasPages())
    <div class="card-footer">{{ $partners->links() }}</div>
    @endif
</div>
@endsection
