@extends('layouts.admin')
@section('title', 'Recipe Details')
@section('page_title', $recipe->product?->name . ' Recipe')
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Ingredients</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Ingredient</th><th>Quantity</th><th>Unit</th><th>Cost</th></tr></thead>
                    <tbody>
                        @foreach($recipe->items as $item)
                        <tr><td>{{ $item->ingredient?->name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->unit }}</td><td>LKR {{ number_format($item->cost, 4) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if($recipe->instructions)
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Instructions</h3></div>
            <div class="card-body"><pre>{{ $recipe->instructions }}</pre></div>
        </div>
        @endif
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Costing</h3></div>
            <div class="card-body">
                <p><strong>Product:</strong> {{ $recipe->product?->name }}</p>
                <p><strong>Selling Price:</strong> LKR {{ number_format($recipe->product?->selling_price ?? 0, 2) }}</p>
                <p><strong>Total Cost:</strong> LKR {{ number_format($recipe->total_cost, 4) }}</p>
                <p><strong>Wastage:</strong> {{ $recipe->wastage_percentage }}%</p>
                <p><strong>Yield:</strong> {{ $recipe->yield_quantity }}</p>
                <p><strong>Profit Margin:</strong> {{ number_format($recipe->profit_margin, 2) }}%</p>
            </div>
        </div>
    </div>
</div>
@endsection
