<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Self Order</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; padding-bottom: 80px; }
        .product-card { border-radius: 8px; background: #fff; border: 1px solid #dee2e6; }
        .product-card img { height: 120px; object-fit: cover; border-radius: 8px 8px 0 0; }
        .cart-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 1000; padding: 10px; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-success sticky-top">
    <div class="container"><span class="navbar-brand mb-0 h1">Self Order</span></div>
</nav>

<div class="container mt-3">
    <div class="row g-2" id="productsGrid">
        @foreach($categories as $cat)
            @foreach($cat->products as $product)
            <div class="col-6 col-md-4 product-item">
                <div class="card product-card h-100" onclick="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->final_price }})">
                    <img src="{{ $product->imageUrl() }}" class="card-img-top" alt="{{ $product->name }}"
                         onerror="this.onerror=null;this.src='/images/product-placeholder.svg'">
                    <div class="card-body p-2 text-center">
                        <h6 class="card-title text-truncate" style="font-size:0.85rem;">{{ $product->name }}</h6>
                        <span class="badge bg-success">LKR {{ number_format($product->final_price, 2) }}</span>
                        <div class="mt-1"><span class="badge bg-secondary" id="qty-{{ $product->id }}">0</span></div>
                    </div>
                </div>
            </div>
            @endforeach
        @endforeach
    </div>
</div>

<div class="cart-bar">
    <div class="container d-flex justify-content-between align-items-center">
        <div><strong id="cartCount">0 items</strong> - <span id="cartTotal">LKR 0.00</span></div>
        <button class="btn btn-success" onclick="checkout()">Checkout</button>
    </div>
</div>

<script>
let cart = {};
function addToCart(id, name, price) {
    cart[id] = cart[id] || { name, price, quantity: 0 };
    cart[id].quantity++;
    document.getElementById('qty-' + id).textContent = cart[id].quantity;
    updateCartBar();
}
function updateCartBar() {
    let count = 0, total = 0;
    Object.values(cart).forEach(i => { count += i.quantity; total += i.price * i.quantity; });
    document.getElementById('cartCount').textContent = count + ' items';
    document.getElementById('cartTotal').textContent = 'LKR ' + total.toFixed(2);
}
function checkout() {
    if (!Object.keys(cart).length) { alert('Cart is empty'); return; }
    const items = Object.entries(cart).map(([id, item]) => ({ product_id: id, name: item.name, price: item.price, quantity: item.quantity }));
    fetch('{{ route('self-order.checkout') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ items })
    }).then(r => r.json()).then(data => {
        if (data.success) { alert('Order placed! Your number: ' + data.order_number); cart = {}; document.querySelectorAll('[id^=qty-]').forEach(e => e.textContent = '0'); updateCartBar(); }
        else { alert('Error: ' + data.message); }
    });
}
</script>
</body>
</html>
