<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS') - ResPOS</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) ?: 1 }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) ?: 1 }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v={{ @filemtime(public_path('favicon-32.png')) ?: 1 }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.min.css">
    @stack('styles')
    <style>
        * { box-sizing: border-box; }
        body { background: #f5f3f0; margin: 0; padding: 0; overflow-x: hidden; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .pos-topbar { background: #1c1917; color: #fff; padding: 10px 16px; display: flex; justify-content: flex-start; align-items: center; box-shadow: 0 2px 16px rgba(0,0,0,0.25); gap: 12px; border-bottom: 2px solid #f59e0b; position: relative; z-index: 1050; overflow: visible; }
        .pos-topbar .brand { font-size: 1.35rem; font-weight: 800; display: flex; align-items: center; gap: 10px; letter-spacing: -0.5px; color: #fbbf24; flex-shrink: 0; }
        .pos-topbar .brand i { color: #f59e0b; }
        .pos-topbar .brand img { width: 34px; height: 34px; border-radius: 10px; object-fit: cover; }
        .pos-topbar-mid {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1 1 auto;
            min-width: 0;
            max-width: calc(100% - 540px);
            overflow-x: auto;
            overflow-y: visible;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 2px 0;
        }
        .pos-topbar-mid::-webkit-scrollbar { display: none; }
        .pos-header-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            min-width: max-content;
        }
        .pos-header-tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid rgba(255,255,255,0.14);
            background: rgba(255,255,255,0.08);
            color: #fafaf9;
            border-radius: 10px;
            padding: 7px 10px;
            font-size: 0.74rem;
            font-weight: 700;
            min-height: 36px;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .pos-header-tool-btn i { color: #fbbf24; font-size: 0.85rem; }
        .pos-header-tool-btn:hover {
            background: rgba(245, 158, 11, 0.22);
            border-color: rgba(245, 158, 11, 0.45);
            color: #fff;
        }
        .pos-header-tool-btn .badge { font-size: 0.62rem; }
        .pos-header-last-sale {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0,0,0,0.35);
            color: #fafaf9;
            border-radius: 10px;
            padding: 7px 12px;
            font-size: 0.74rem;
            cursor: pointer;
            border: 1px solid rgba(251, 191, 36, 0.25);
            flex-shrink: 0;
            white-space: nowrap;
            min-height: 36px;
        }
        .pos-header-last-sale:hover {
            background: rgba(245, 158, 11, 0.18);
            border-color: rgba(245, 158, 11, 0.45);
        }
        .pos-header-last-sale .phls-label {
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #a8a29e;
        }
        .pos-header-last-sale strong { color: #fbbf24; }
        .pos-header-last-sale .phls-change { color: #d6d3d1; }
        .pos-header-last-sale .phls-change strong { color: #86efac; }
        .pos-topbar-actions { display: flex; align-items: center; gap: 8px; overflow-x: auto; overflow-y: visible; scrollbar-width: none; -ms-overflow-style: none; padding-bottom: 2px; flex: 0 0 auto; flex-shrink: 0; margin-left: auto; z-index: 2; }
        .pos-topbar-actions::-webkit-scrollbar { display: none; }
        .pos-topbar-bill-btn {
            position: relative;
            padding-right: 14px !important;
        }
        .pos-topbar-bill-btn .pos-topbar-badge {
            position: absolute;
            top: -6px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            font-size: 0.65rem;
            font-weight: 800;
            line-height: 18px;
            border-radius: 999px;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.25);
            z-index: 3;
        }
        .pos-topbar-bill-btn .pos-topbar-badge.is-visible { display: inline-flex; }
        .pos-topbar-bill-btn .pos-topbar-badge.bg-open { background: #fbbf24; color: #1c1917; }
        .pos-topbar-bill-btn .pos-topbar-badge.bg-pay { background: #ef4444; color: #fff; }
        .pos-topbar-bill-btn .pos-topbar-badge.bg-kot { background: #fb923c; color: #1c1917; }
        .pos-header-tool-btn.pos-header-bill-btn { padding-right: 12px; position: relative; }
        .pos-header-tool-btn .pos-topbar-badge {
            position: absolute;
            top: -5px;
            right: -3px;
            min-width: 17px;
            height: 17px;
            padding: 0 4px;
            font-size: 0.62rem;
            font-weight: 800;
            line-height: 17px;
            border-radius: 999px;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            z-index: 2;
        }
        .pos-header-tool-btn .pos-topbar-badge.is-visible { display: inline-flex; }
        .pos-header-tool-btn .pos-topbar-badge.bg-open { background: #fbbf24; color: #1c1917; }
        .pos-header-tool-btn .pos-topbar-badge.bg-pay { background: #ef4444; color: #fff; }
        .pos-header-tool-btn .pos-topbar-badge.bg-kot { background: #fb923c; color: #1c1917; }
        .pos-topbar .btn-sm { white-space: nowrap; flex-shrink: 0; padding: 11px 18px !important; font-size: 0.95rem !important; font-weight: 600; border-radius: 12px !important; background: rgba(255,255,255,0.08) !important; border: 1px solid rgba(255,255,255,0.12) !important; transition: all 0.15s ease; }
        .pos-topbar .btn-sm:hover { background: rgba(245,158,11,0.25) !important; border-color: rgba(245,158,11,0.5) !important; }
        .pos-topbar .btn-sm i { font-size: 1.05rem; }
        #notifyPanel { position: fixed !important; z-index: 1060 !important; }
        .pos-container { padding: 16px; max-width: 100%; }
        body.pos-mode-bakery .pos-container { max-width: 100%; }
        body.pos-mode-ice-cream .pos-container { max-width: 100%; }

        /* Desktop restaurant/bakery POS: lock page to viewport so columns scroll internally (cart stays visible) */
        @media (min-width: 992px) {
            body.pos-mode-restaurant,
            body.pos-mode-bakery {
                display: flex;
                flex-direction: column;
                height: 100vh;
                max-height: 100vh;
                overflow: hidden;
            }
            body.pos-mode-restaurant .pos-topbar,
            body.pos-mode-bakery .pos-topbar {
                flex: 0 0 auto;
            }
            body.pos-mode-restaurant .pos-container,
            body.pos-mode-bakery .pos-container {
                flex: 1 1 auto;
                min-height: 0;
                /* Keep overflow visible so POS modals inside this root are not clipped */
                display: flex;
                flex-direction: column;
                padding: 8px 12px;
                overflow: hidden;
            }
            body.pos-mode-bakery .bakery-workspace {
                flex: 1 1 auto;
                min-height: 0;
                height: auto !important;
                max-height: 100%;
                overflow: hidden;
            }
        }
        body.pos-mode-ice-cream #productsGrid.product-grid {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        }
        @media (max-width: 991.98px) {
            body.pos-mode-ice-cream #productsGrid.product-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }
        }
        @media (max-width: 575.98px) {
            body.pos-mode-ice-cream #productsGrid.product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        .pos-btn { min-height: 56px; font-size: 1rem; font-weight: 600; border-radius: 12px; border: none; }
        .touch-btn {
            min-height: 0;
            aspect-ratio: 1 / 1;
            width: 100%;
            font-size: 1.35rem;
            font-weight: 900;
            border-radius: 16px;
            border: none;
            color: #0f172a;
            background: linear-gradient(160deg, #ffffff 0%, #f8fafc 50%, #e2e8f0 100%);
            box-shadow:
                inset 0 2px 0 rgba(255,255,255,0.95),
                inset 0 -2px 5px rgba(15,23,42,0.08),
                0 5px 0 #94a3b8,
                0 8px 14px rgba(15,23,42,0.12);
        }
        .touch-btn:hover { background: linear-gradient(160deg, #ffffff, #f1f5f9); }
        .touch-btn:active {
            transform: translateY(4px);
            box-shadow:
                inset 0 3px 8px rgba(15,23,42,0.18),
                0 1px 0 #64748b;
        }
        .touch-btn.warning {
            background: linear-gradient(160deg, #fff7ed, #f59e0b);
            color: #7c2d12;
            box-shadow:
                inset 0 2px 0 rgba(255,255,255,0.7),
                0 5px 0 #d97706,
                0 8px 14px rgba(217,119,6,0.28);
        }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
        .product-card { background: #fff; border-radius: 16px; overflow: hidden; cursor: pointer; transition: all 0.2s ease; border: 2px solid transparent; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); border-color: #f59e0b; }
        .product-card:active { transform: translateY(-1px) scale(0.98); }
        .product-img { width: 100%; height: 90px; object-fit: cover; background: #f8fafc; display: block; }
        .product-img i { font-size: 2rem; color: #cbd5e1; }
        div.product-img { display: flex; align-items: center; justify-content: center; }
        .product-info { padding: 12px; text-align: center; }
        .product-name { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 6px; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.4em; }
        .product-price { display: inline-block; background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
        .product-meta { font-size: 0.7rem; color: #94a3b8; margin-top: 4px; }
        .category-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .category-btn { padding: 8px 16px; border-radius: 20px; border: 1px solid #e2e8f0; background: #fff; font-size: 0.85rem; font-weight: 500; color: #64748b; cursor: pointer; transition: all 0.2s; }
        .category-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .category-btn.active { background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; border-color: transparent; }
        .order-type-btn { flex: 1; padding: 14px 8px; border-radius: 12px; border: 2px solid #e2e8f0; background: #fff; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 6px; }
        .order-type-btn i { font-size: 1.3rem; }
        .order-type-btn:hover { border-color: #cbd5e1; background: #f8fafc; }
        .order-type-btn.active.dine-in { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; border-color: transparent; }
        .order-type-btn.active.takeaway { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border-color: transparent; }
        .order-type-btn.active.delivery { background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; border-color: transparent; }
        .order-type-btn.active.express { background: linear-gradient(135deg, #10b981, #059669); color: #fff; border-color: transparent; }
        .cart-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); display: flex; flex-direction: column; height: auto; }
        .cart-header { padding: 16px 20px; background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; border-radius: 16px 16px 0 0; }
        .cart-body { flex: 0 0 auto; min-height: 120px; max-height: calc(100vh - 360px); overflow-y: auto; padding: 0; }
        .cart-footer { padding: 16px; background: #f8fafc; border-radius: 0 0 16px 16px; }
        .cart-item { display: flex; align-items: center; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
        .cart-item:hover { background: #f8fafc; }
        .qty-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: 600; }
        .qty-btn:hover { background: #f1f5f9; }
        .qty-btn-edit { color: #d97706; border-color: #fde68a; background: #fffbeb; }
        .qty-btn-edit:hover { background: #fef3c7; color: #b45309; }
        .quick-pay-btn { padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; font-size: 0.85rem; font-weight: 600; cursor: pointer; }
        .quick-pay-btn:hover { background: #f0fdf4; border-color: #86efac; }
        .pay-btn { background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: none; }
        .pay-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.4); }
        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .search-input { border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; font-size: 1rem; width: 100%; background: #fff; }
        .search-input:focus { outline: none; border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.1); }
        .form-select-modern { border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; background: #fff; font-size: 0.9rem; }
        .total-display { font-size: 1.5rem; font-weight: 700; color: #f59e0b; }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Mobile responsive adjustments */
        @media (max-width: 991.98px) {
            .pos-topbar { padding: 10px 12px; }
            .pos-topbar .brand { font-size: 1.1rem; }
            .pos-container { padding: 12px; }
            .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .product-card { border-radius: 14px; }
            .product-img { height: 72px; }
            .product-info { padding: 10px; }
            .product-name { font-size: 0.8rem; min-height: 2.2em; }
            .product-price { font-size: 0.75rem; padding: 3px 10px; }
            .order-type-btn { padding: 10px 4px; font-size: 0.72rem; border-radius: 10px; }
            .order-type-btn i { font-size: 1.1rem; }
            .category-tabs { gap: 6px; }
            .category-btn { padding: 6px 12px; font-size: 0.8rem; }
            .touch-btn { font-size: 1.2rem; }
            .pos-btn { min-height: 52px; font-size: 0.95rem; }
            .modal-dialog { margin: 0.5rem; }
            .modal-content { border-radius: 14px; }
            .cart-card { border-radius: 14px; }
            .cart-header { padding: 14px 16px; border-radius: 14px 14px 0 0; }
            .cart-footer { padding: 14px; border-radius: 0 0 14px 14px; }
            .cart-item { padding: 10px 14px; }
        }

        @media (max-width: 575.98px) {
            .product-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .product-img { height: 64px; }
            .product-name { font-size: 0.78rem; }
            .product-price { font-size: 0.72rem; }
            .order-type-btn { padding: 8px 2px; font-size: 0.68rem; }
            .order-type-btn i { font-size: 1rem; }
            .modal-dialog { margin: 0.25rem; }
            .modal-content { border-radius: 12px; }
            .modal-body { padding: 16px; }
        }

        /* Mobile cart drawer */
        .mobile-cart-drawer {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
            z-index: 1050;
            max-height: 85vh;
            flex-direction: column;
        }
        .mobile-cart-drawer.open { display: flex; }
        .mobile-cart-drawer .drawer-handle {
            width: 100%;
            padding: 12px 16px 6px;
            display: flex;
            justify-content: center;
            cursor: pointer;
        }
        .mobile-cart-drawer .drawer-handle::before {
            content: '';
            width: 48px;
            height: 5px;
            background: #cbd5e1;
            border-radius: 3px;
        }
        .mobile-cart-drawer .drawer-header {
            padding: 8px 16px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .mobile-cart-drawer .drawer-body {
            flex: 0 0 auto;
            min-height: 120px;
            max-height: 50vh;
            overflow-y: auto;
            padding: 0 16px;
        }
        .mobile-cart-drawer .drawer-footer {
            padding: 16px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
            border-radius: 0 0 20px 20px;
        }
        .mobile-cart-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 1049;
        }
        .mobile-cart-backdrop.open { display: block; }

        @media (min-width: 992px) {
            .mobile-cart-toggle, .mobile-cart-drawer, .mobile-cart-backdrop { display: none !important; }
        }

        .mobile-cart-toggle {
            position: fixed;
            bottom: 16px;
            left: 16px;
            right: 16px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: none;
            border-radius: 16px;
            padding: 14px 18px;
            font-size: 0.95rem;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.35);
            z-index: 1040;
            display: none;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .mobile-cart-toggle:active { transform: scale(0.98); }
        @media (max-width: 991.98px) {
            .mobile-cart-toggle { display: block; }
        }

        /* Ice cream POS — force QBakery-style 4×4 grid (must be last) */
        body.pos-mode-ice-cream #productsGrid,
        body.pos-mode-ice-cream #productsGrid.product-grid,
        body.pos-mode-ice-cream #productsGrid.ice-products-4x4 {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            grid-template-rows: none !important;
            grid-auto-rows: calc((100% - 24px) / 4) !important;
            gap: 8px !important;
            align-content: start !important;
            height: 100% !important;
            min-height: 0 !important;
            overflow-y: auto !important;
        }
        body.pos-mode-ice-cream #productsGrid > .product-item {
            min-width: 0 !important;
            min-height: 0 !important;
            height: 100% !important;
            display: flex !important;
        }
        body.pos-mode-ice-cream #productsGrid > .product-item.is-filtered-out {
            display: none !important;
        }
        body.pos-mode-ice-cream #productsGrid .product-card {
            position: relative !important;
            height: 100% !important;
            width: 100% !important;
            overflow: hidden !important;
            border-radius: 14px !important;
            background: #9d174d !important;
            border: 2px solid #fbcfe8 !important;
            box-shadow: 0 2px 8px rgba(157,23,77,.1) !important;
        }
        body.pos-mode-ice-cream #productsGrid .product-img {
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            display: block !important;
        }
        body.pos-mode-ice-cream #productsGrid .product-info {
            position: absolute !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 2 !important;
            padding: 16px 8px 8px !important;
            margin: 0 !important;
            background: linear-gradient(180deg, transparent, rgba(76,5,40,.92)) !important;
            text-align: left !important;
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: flex-end !important;
            gap: 4px !important;
        }
        body.pos-mode-ice-cream #productsGrid .product-name {
            flex: 1 1 100% !important;
            color: #fff !important;
            font-size: 12px !important;
            font-weight: 800 !important;
            margin: 0 !important;
            min-height: 0 !important;
            -webkit-line-clamp: 2 !important;
        }
        body.pos-mode-ice-cream #productsGrid .product-price {
            background: rgba(0,0,0,.45) !important;
            color: #fff !important;
            font-size: 11px !important;
            padding: 3px 8px !important;
            border-radius: 999px !important;
        }
        body.pos-mode-ice-cream .pos-shell--ice-cream .keypad,
        body.pos-mode-ice-cream .mobile-cart-drawer .keypad {
            display: none !important;
        }
        body.pos-mode-ice-cream .pos-shell--bakery .cart-body,
        body.pos-mode-ice-cream .pos-shell--ice-cream .cart-body {
            flex: 1 1 auto !important;
            max-height: none !important;
            min-height: 180px !important;
        }
        @media (max-width: 575.98px) {
            body.pos-mode-ice-cream #productsGrid,
            body.pos-mode-ice-cream #productsGrid.product-grid,
            body.pos-mode-ice-cream #productsGrid.ice-products-4x4 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                grid-template-rows: none !important;
                grid-auto-rows: 130px !important;
                height: auto !important;
            }
        }

        /* Restaurant desktop Cart: box-shaped keypad (taller keys, still fits viewport) */
        @media (min-width: 992px) {
            body.pos-mode-restaurant .pos-shell--restaurant .cart-footer .rest-cart-keypad:not(.d-none) {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                grid-template-rows: none !important;
                grid-auto-rows: auto !important;
                gap: 8px !important;
                margin-bottom: 0.4rem !important;
            }
            body.pos-mode-restaurant .pos-shell--restaurant .cart-footer .rest-cart-keypad .touch-btn {
                aspect-ratio: unset !important;
                width: 100% !important;
                height: clamp(42px, 5.2vh, 52px) !important;
                min-height: 42px !important;
                max-height: 52px !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: clamp(1.15rem, 2.2vh, 1.45rem) !important;
                font-weight: 800 !important;
                line-height: 1 !important;
                border-radius: 12px !important;
                border: 1px solid #d6d3d1 !important;
                color: #1c1917 !important;
                background: linear-gradient(180deg, #ffffff 0%, #f5f5f4 100%) !important;
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.95),
                    0 2px 0 #a8a29e,
                    0 4px 10px rgba(28, 25, 23, 0.12) !important;
            }
            body.pos-mode-restaurant .pos-shell--restaurant .cart-footer .rest-cart-keypad .touch-btn:active {
                transform: translateY(2px) !important;
                box-shadow:
                    inset 0 2px 6px rgba(28, 25, 23, 0.16),
                    0 1px 0 #78716c !important;
            }
            body.pos-mode-restaurant .pos-shell--restaurant .cart-footer .rest-cart-keypad .touch-btn.warning {
                border-color: #fdba74 !important;
                color: #9a3412 !important;
                background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%) !important;
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.8),
                    0 2px 0 #ea580c,
                    0 4px 10px rgba(234, 88, 12, 0.22) !important;
            }
            body.pos-mode-restaurant .pos-shell--restaurant .cart-footer .pos-btn {
                min-height: 38px !important;
                font-size: 0.88rem !important;
            }
            body.pos-mode-restaurant .restaurant-cat-grid[data-count="1"],
            body.pos-mode-restaurant .restaurant-cat-grid[data-count="2"],
            body.pos-mode-restaurant .restaurant-cat-grid[data-count="3"] {
                grid-template-columns: 1fr !important;
                height: auto !important;
                max-height: 100% !important;
                align-content: start !important;
            }
            body.pos-mode-restaurant .restaurant-cat-grid[data-count="1"] {
                grid-auto-rows: 128px !important;
            }
            body.pos-mode-restaurant .restaurant-cat-grid[data-count="2"],
            body.pos-mode-restaurant .restaurant-cat-grid[data-count="3"] {
                grid-auto-rows: 112px !important;
            }
            body.pos-mode-restaurant .restaurant-cat-grid[data-count="1"] .category-btn {
                height: 128px !important;
                max-height: 128px !important;
            }
            body.pos-mode-restaurant .restaurant-cat-grid[data-count="2"] .category-btn,
            body.pos-mode-restaurant .restaurant-cat-grid[data-count="3"] .category-btn {
                height: 112px !important;
                max-height: 112px !important;
            }
            body.pos-mode-restaurant .restaurant-cat-grid .category-btn.active {
                background: rgba(255,255,255,0.08) !important;
                color: #fafaf9 !important;
            }
        }
        @media (min-width: 992px) and (max-height: 800px) {
            body.pos-mode-restaurant .pos-shell--restaurant .cart-footer .rest-cart-keypad {
                gap: 6px !important;
            }
            body.pos-mode-restaurant .pos-shell--restaurant .cart-footer .rest-cart-keypad .touch-btn {
                height: clamp(36px, 4.6vh, 44px) !important;
                min-height: 36px !important;
                max-height: 44px !important;
                font-size: 1.15rem !important;
                border-radius: 10px !important;
            }
            body.pos-mode-restaurant .pos-shell--restaurant .cart-footer .pos-btn {
                min-height: 34px !important;
                font-size: 0.82rem !important;
            }
        }

        /* Bakery desktop Cart: same box-shaped 3-column keypad */
        @media (min-width: 992px) {
            body.pos-mode-bakery .pos-shell--bakery .cart-footer .bakery-keypad-compact:not(.d-none) {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                grid-template-rows: none !important;
                gap: 8px !important;
                margin-bottom: 0.4rem !important;
            }
            body.pos-mode-bakery .pos-shell--bakery .cart-footer .bakery-keypad-compact .touch-btn {
                aspect-ratio: unset !important;
                width: 100% !important;
                height: clamp(42px, 5.2vh, 52px) !important;
                min-height: 42px !important;
                max-height: 52px !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: clamp(1.15rem, 2.2vh, 1.45rem) !important;
                font-weight: 800 !important;
                line-height: 1 !important;
                border-radius: 12px !important;
                border: 1px solid #e8dfd2 !important;
                color: #3b2a22 !important;
                background: linear-gradient(180deg, #ffffff 0%, #f7f3ec 100%) !important;
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.95),
                    0 2px 0 #c4b5a5,
                    0 4px 10px rgba(59, 42, 34, 0.12) !important;
            }
            body.pos-mode-bakery .pos-shell--bakery .cart-footer .bakery-keypad-compact .touch-btn:active {
                transform: translateY(2px) !important;
                box-shadow:
                    inset 0 2px 6px rgba(59, 42, 34, 0.16),
                    0 1px 0 #a89080 !important;
            }
            body.pos-mode-bakery .pos-shell--bakery .cart-footer .bakery-keypad-compact .touch-btn.warning {
                border-color: #e8b86d !important;
                color: #7c2d12 !important;
                background: linear-gradient(180deg, #fff7ed 0%, #fde68a 100%) !important;
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.8),
                    0 2px 0 #d97706,
                    0 4px 10px rgba(217, 119, 6, 0.22) !important;
            }
            body.pos-mode-bakery .pos-shell--bakery .cart-footer .pos-btn {
                min-height: 46px !important;
            }
        }
        @media (min-width: 992px) and (max-height: 800px) {
            body.pos-mode-bakery .pos-shell--bakery .cart-footer .bakery-keypad-compact {
                gap: 6px !important;
            }
            body.pos-mode-bakery .pos-shell--bakery .cart-footer .bakery-keypad-compact .touch-btn {
                height: clamp(36px, 4.6vh, 44px) !important;
                min-height: 36px !important;
                max-height: 44px !important;
                font-size: 1.15rem !important;
            }
            body.pos-mode-bakery .pos-shell--bakery .cart-footer .pos-btn {
                min-height: 38px !important;
                font-size: 0.88rem !important;
            }
        }

        /* Must follow keypad display:grid !important rules so Hide Numpad can actually unmount the grid. */
        .pos-cart-keypad-host[hidden],
        .pos-cart-keypad-host.d-none,
        body.pos-numpad-hidden .pos-cart-keypad-host,
        body.pos-numpad-hidden .pos-cart-keypad,
        body.pos-mode-restaurant.pos-numpad-hidden .pos-shell--restaurant .cart-footer .pos-cart-keypad-host,
        body.pos-mode-restaurant.pos-numpad-hidden .pos-shell--restaurant .cart-footer .rest-cart-keypad,
        body.pos-mode-bakery.pos-numpad-hidden .pos-shell--bakery .cart-footer .pos-cart-keypad-host,
        body.pos-mode-bakery.pos-numpad-hidden .pos-shell--bakery .cart-footer .bakery-keypad-compact {
            display: none !important;
        }
    </style>
    @include('partials.business-clock')
</head>
@php
    $posUiMode = \App\Http\Controllers\Auth\ShopUiController::currentMode(
        \App\Models\Setting::get('pos_ui_mode', 'restaurant')
    );
    if (! in_array($posUiMode, ['restaurant', 'bakery', 'ice_cream'], true)) {
        $posUiMode = 'restaurant';
    }
    $isBakeryUi = $posUiMode === 'bakery';
    $isIceCreamUi = $posUiMode === 'ice_cream';
    $isShopUi = $isBakeryUi || $isIceCreamUi;
    $bakeryDirectBilling = $isShopUi && (bool) \App\Models\Setting::get('bakery_direct_billing', false);
    $bakeryShowCartDisplay = ! $isShopUi || (bool) \App\Models\Setting::get('bakery_show_cart_display', true);
    $bakeryShowStatusDisplay = ! $isShopUi || (bool) \App\Models\Setting::get('bakery_show_status_display', false);
    $bakeryShowOrdersDisplay = ! $isShopUi || (bool) \App\Models\Setting::get('bakery_show_orders_display', false);
    $customerDisplayMode = \App\Models\Setting::get('customer_display_mode', 'digital');
    if (! in_array($customerDisplayMode, ['digital', 'analog'], true)) {
        $customerDisplayMode = 'digital';
    }
    $isAnalogCustomerDisplay = $customerDisplayMode === 'analog';
    $customerDisplayRoute = $isAnalogCustomerDisplay
        ? route('customer.display.led')
        : route('customer.display');
    $customerDisplayLabel = $isAnalogCustomerDisplay ? 'LED' : 'Cart';
    $customerDisplayIcon = $isAnalogCustomerDisplay ? 'fa-calculator' : 'fa-tv';
    $customerDisplayTitle = $isAnalogCustomerDisplay
        ? 'Connect rear LED customer display'
        : 'Digital TV cart display';
    $showKitchenLinks = (bool) \App\Models\Setting::get('kitchen_display_enabled', true)
        && (bool) \App\Models\Setting::get('kot_confirmation_enabled', true)
        && ! $bakeryDirectBilling;
@endphp
<body class="pos-mode-{{ str_replace('_', '-', $posUiMode) }} {{ $bakeryDirectBilling ? 'bakery-direct-billing' : '' }}">
<div class="pos-topbar">
    @php
        $posBrand = \App\Models\Setting::get('company_name', 'ResPOS');
        $branch = \App\Services\BranchService::current();
        if ($branch && $branch->displayName()) {
            $posBrand = $branch->displayName();
        }
        if ($posUiMode === 'restaurant' && $posBrand === 'Ice Cream Shop') {
            $fallbackBranch = \App\Models\Branch::query()
                ->where('is_main', true)
                ->value('company_name')
                ?: \App\Models\Branch::query()->value('company_name')
                ?: \App\Models\Branch::query()->where('is_main', true)->value('name')
                ?: \App\Models\Branch::query()->value('name');
            if ($fallbackBranch) {
                $posBrand = $fallbackBranch;
            }
        }
        if ($posUiMode === 'ice_cream') {
            $posBrand = 'Ice Cream Shop';
        }
        $posLogo = \App\Models\Setting::logoUrl();
    @endphp
    <div class="brand">
        @if($posLogo)
            <img src="{{ $posLogo }}" alt="{{ $posBrand }}">
        @else
            <i class="fas fa-cash-register me-2"></i>
        @endif
        {{ $posBrand }}
    </div>
    @hasSection('topbar-tools')
    <div class="pos-topbar-mid">
        @yield('topbar-tools')
    </div>
    @endif
    <div class="pos-topbar-actions">
        @if($posUiMode === 'restaurant')
        <!-- Waiter PWA order alerts -->
        <div id="waiterAlertDropdown" style="position:relative;flex-shrink:0;">
            <button type="button" id="waiterAlertBtn" class="btn btn-sm text-white position-relative" title="Waiter orders from PWA" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
                <i class="fas fa-user-tie me-1"></i><span class="d-none d-sm-inline">Waiter</span>
                <span id="waiterAlertBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" style="display: none;">0</span>
            </button>
        </div>
        @endif
        @if($showKitchenLinks)
        <a href="{{ route('kitchen.display') }}" target="_blank" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); text-decoration: none;">
            <i class="fas fa-fire me-1"></i><span class="d-none d-sm-inline">Kitchen</span>
        </a>
        @endif
        @if($posUiMode === 'restaurant')
        <button type="button" class="btn btn-sm text-white pos-topbar-bill-btn" onclick="typeof openBillTableModal === 'function' && openBillTableModal()" title="Open bills" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
            <i class="fas fa-file-invoice me-1"></i><span>Bills</span>
            <span id="openBillsBadge" class="pos-topbar-badge bg-open">0</span>
            <span id="openBillsBadgeMobile" class="d-none">0</span>
        </button>
        <button type="button" class="btn btn-sm text-white pos-topbar-bill-btn" data-pay-bills-btn onclick="typeof showPayBills === 'function' && showPayBills()" title="Pay bills — table requests" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
            <i class="fas fa-bell me-1"></i><span>Pay</span>
            <span id="payBillsBadge" class="pos-topbar-badge bg-pay">0</span>
            <span id="payBillsBadgeMobile" class="d-none">0</span>
        </button>
        <button type="button" class="btn btn-sm text-white pos-topbar-bill-btn" id="pendingKotPrintBtn" onclick="typeof printPendingKotsNow === 'function' && printPendingKotsNow(true)" title="Print pending KOTs from waiter / failed prints via Print Bridge" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
            <i class="fas fa-print me-1"></i><span class="d-none d-md-inline">KOT</span>
            <span id="pendingKotBadge" class="pos-topbar-badge bg-kot">0</span>
        </button>
        @endif
        @if($bakeryShowCartDisplay)
            @if($isAnalogCustomerDisplay)
            <button type="button" id="analogLedConnectBtn" onclick="typeof connectAnalogLedDisplay === 'function' && connectAnalogLedDisplay()" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);" title="{{ $customerDisplayTitle }}">
                <i class="fas {{ $customerDisplayIcon }} me-1"></i><span class="d-none d-sm-inline" id="analogLedConnectLabel">LED</span>
            </button>
            <button type="button" onclick="typeof testAnalogLedDisplay === 'function' && testAnalogLedDisplay()" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);" title="Send test 123.45 to rear LED">
                <i class="fas fa-vial me-1"></i><span class="d-none d-lg-inline">Test</span>
            </button>
            @else
            <a href="{{ $customerDisplayRoute }}" target="_blank" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); text-decoration: none;" title="{{ $customerDisplayTitle }}">
                <i class="fas {{ $customerDisplayIcon }} me-1"></i><span class="d-none d-sm-inline">{{ $customerDisplayLabel }}</span>
            </a>
            @endif
        @endif
        @if($bakeryShowOrdersDisplay)
        <button type="button" class="btn btn-sm text-white" onclick="typeof showRecentOrders === 'function' && showRecentOrders()" title="Today's orders" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
            <i class="fas fa-history me-1"></i><span class="d-none d-sm-inline">Orders</span>
        </button>
        @endif
        <button class="btn btn-sm text-white" onclick="showCashDrawer()" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
            <i class="fas fa-box me-1"></i><span class="d-none d-sm-inline">Drawer</span>
        </button>
        @php $shopUiPickerEnabled = \App\Http\Controllers\Auth\ShopUiController::isPickerEnabled(); @endphp
        @if($shopUiPickerEnabled)
        <a href="{{ route('shop-ui.select') }}" class="btn btn-sm text-white" title="Change shop UI" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); text-decoration: none;">
            <i class="fas fa-store me-1"></i><span class="d-none d-lg-inline">Shop UI</span>
        </a>
        @endif
        @if($isIceCreamUi || $isShopUi)
        <div class="btn-group pos-zoom-controls" role="group" aria-label="Display zoom">
            <button type="button" class="btn btn-sm text-white" onclick="posZoomOut()" title="Zoom out" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
                <i class="fas fa-search-minus"></i>
            </button>
            <button type="button" class="btn btn-sm text-white" id="posZoomLabel" onclick="posZoomReset()" title="Reset zoom" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); min-width: 52px;">
                100%
            </button>
            <button type="button" class="btn btn-sm text-white" onclick="posZoomIn()" title="Zoom in" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
                <i class="fas fa-search-plus"></i>
            </button>
        </div>
        @endif
        <button type="button" class="btn btn-sm text-white" id="posHeaderCloseShiftBtn" data-shift-label="header-close" onclick="showShiftCloseModal()" title="Close shift" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
            <i class="fas fa-clock me-1"></i><span class="d-none d-sm-inline" data-shift-label-text>Close Shift</span>
        </button>
        <a href="{{ route('dashboard') }}" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); text-decoration: none;">
            <i class="fas fa-tachometer-alt"></i>
        </a>
        <span class="text-white d-none d-md-inline" style="opacity: 0.9; white-space: nowrap;"><i class="fas fa-user-circle me-1"></i>{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </form>
    </div>
</div>

<div class="pos-container" id="posZoomRoot">
    @yield('content')
</div>

{{-- Waiter order alerts (right side) --}}
<div id="kotPrintLogStack" aria-live="polite"></div>
@if(($posUiMode ?? 'restaurant') === 'restaurant')
<div id="waiterAlertPanel" class="waiter-alert-panel" aria-hidden="true">
    <div class="waiter-alert-panel-head">
        <span><i class="fas fa-user-tie me-1"></i>Waiter orders</span>
        <button type="button" class="waiter-alert-panel-x" id="waiterAlertPanelCloseBtn" title="Close">&times;</button>
    </div>
    <div id="waiterAlertPanelList" class="waiter-alert-panel-list">
        <p class="waiter-alert-empty">No waiter alerts</p>
    </div>
</div>
@endif

<style>
/* Middle waiter alerts — compact 4-col grid under products */
#waiterAlertTray {
    width: 100%;
    margin: 8px 0 0;
    flex-shrink: 0;
    min-height: 0;
    z-index: 30;
}
#waiterOrderAlertStack {
    display: none;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    width: 100%;
    max-height: 168px;
    overflow-y: auto;
    padding: 2px;
}
#waiterOrderAlertStack.has-items { display: grid; }
#waiterOrderAlertStack .woa-card {
    background: #1c1917;
    color: #fafaf9;
    border-radius: 10px;
    border: 1px solid #44403c;
    box-shadow: 0 4px 12px rgba(0,0,0,.2);
    padding: 8px;
    min-height: 76px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    animation: woaIn .25s ease;
}
#waiterOrderAlertStack .woa-card.is-new { border-color: #f59e0b; }
#waiterOrderAlertStack .woa-card.is-modified { border-color: #38bdf8; }
#waiterOrderAlertStack .woa-title {
    font-weight: 700;
    font-size: .68rem;
    color: #fbbf24;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#waiterOrderAlertStack .woa-meta {
    font-size: .62rem;
    color: #d6d3d1;
    line-height: 1.25;
    flex: 1;
    min-height: 0;
}
#waiterOrderAlertStack .woa-items {
    color: #a8a29e;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
#waiterOrderAlertStack .woa-status {
    font-size: .58rem;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 5px;
    display: inline-flex;
    width: fit-content;
}
#waiterOrderAlertStack .woa-status.pending { background: #422006; color: #fcd34d; }
#waiterOrderAlertStack .woa-status.printed { background: #14532d; color: #86efac; }
#waiterOrderAlertStack .woa-status.error { background: #7f1d1d; color: #fecaca; }
#waiterOrderAlertStack .woa-actions {
    display: flex;
    gap: 4px;
    margin-top: auto;
}
#waiterOrderAlertStack .woa-actions .btn {
    font-size: .58rem;
    padding: 2px 5px;
    border-radius: 5px;
    line-height: 1.2;
    flex: 1;
}
@media (max-width: 1100px) {
    #waiterOrderAlertStack { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    #waiterOrderAlertStack { grid-template-columns: repeat(2, minmax(0, 1fr)); max-height: 200px; }
}

/* Compact Waiter topbar panel */
.waiter-alert-panel {
    display: none !important;
    position: fixed !important;
    z-index: 30000 !important;
    width: 268px;
    max-width: calc(100vw - 16px);
    background: #1c1917;
    border: 1px solid #57534e;
    border-radius: 12px;
    padding: 0;
    box-shadow: 0 12px 28px rgba(0,0,0,.45);
    overflow: hidden;
}
.waiter-alert-panel.is-open { display: block !important; }
.waiter-alert-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #1c1917;
    font-size: .78rem;
    font-weight: 700;
    padding: 8px 10px;
}
.waiter-alert-panel-x {
    border: 0;
    background: rgba(0,0,0,.15);
    color: #1c1917;
    width: 22px;
    height: 22px;
    border-radius: 6px;
    line-height: 1;
    font-size: 1rem;
    padding: 0;
}
.waiter-alert-panel-list {
    max-height: 240px;
    overflow-y: auto;
    padding: 8px;
}
.waiter-alert-empty {
    text-align: center;
    color: #a8a29e !important;
    font-size: .75rem;
    margin: 10px 0;
}
#waiterAlertPanelList .woa-panel-item {
    background: #292524;
    border: 1px solid #44403c;
    border-radius: 8px;
    padding: 7px 8px;
    margin-bottom: 6px;
    color: #fafaf9;
}
#waiterAlertPanelList .woa-panel-item:last-child { margin-bottom: 0; }
#waiterAlertPanelList .woa-panel-item.is-modified { border-color: #38bdf8; }
#waiterAlertPanelList .woa-panel-title {
    font-weight: 700;
    color: #fbbf24;
    font-size: .72rem;
    line-height: 1.25;
}
#waiterAlertPanelList .woa-panel-meta {
    font-size: .68rem;
    color: #d6d3d1;
    margin-top: 3px;
    line-height: 1.3;
}
#waiterAlertPanelList .woa-panel-items {
    color: #a8a29e;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
#waiterAlertPanelList .woa-panel-actions {
    display: flex;
    gap: 5px;
    margin-top: 6px;
    flex-wrap: nowrap;
}
#waiterAlertPanelList .woa-panel-actions .btn {
    font-size: .65rem;
    padding: 2px 7px;
    border-radius: 6px;
    line-height: 1.25;
    flex: 1 1 auto;
}
@media (max-width: 576px) {
    .waiter-alert-panel {
        position: fixed;
        top: 56px;
        right: 8px;
        left: auto;
        width: min(280px, calc(100vw - 16px));
    }
}
#kotPrintLogStack {
    position: fixed;
    bottom: 18px;
    right: 16px;
    z-index: 20050;
    display: flex;
    flex-direction: column-reverse;
    gap: 6px;
    width: min(260px, calc(100vw - 24px));
    pointer-events: none;
}
#kotPrintLogStack .kot-log {
    background: #14532d;
    color: #bbf7d0;
    border: 1px solid #22c55e;
    border-radius: 10px;
    padding: 7px 10px;
    font-size: .72rem;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
    animation: woaIn .3s ease;
}
@keyframes woaIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let notifyPanelOpen = false;
let lastReadyOrders = [];
const POS_LAYOUT_SOUND = @json((bool) \App\Models\Setting::get('pos_ready_sound_alert', true));
const POS_ZOOM_KEY = 'respos_pos_zoom';
const POS_ZOOM_STEPS = [0.75, 0.85, 0.9, 1, 1.1, 1.2, 1.3];
let posZoomLevel = 1;

function applyPosZoom(level) {
    const root = document.getElementById('posZoomRoot');
    const label = document.getElementById('posZoomLabel');
    if (!root) return;
    posZoomLevel = Math.max(POS_ZOOM_STEPS[0], Math.min(POS_ZOOM_STEPS[POS_ZOOM_STEPS.length - 1], Number(level) || 1));
    // Prefer CSS zoom for layout; fallback to transform for older engines
    root.style.zoom = posZoomLevel;
    root.style.transformOrigin = 'top left';
    if (label) label.textContent = Math.round(posZoomLevel * 100) + '%';
    try { localStorage.setItem(POS_ZOOM_KEY, String(posZoomLevel)); } catch (e) {}
}
function nearestZoomStep(value) {
    let best = POS_ZOOM_STEPS[0];
    let dist = Math.abs(value - best);
    POS_ZOOM_STEPS.forEach(step => {
        const d = Math.abs(value - step);
        if (d < dist) { best = step; dist = d; }
    });
    return best;
}
function posZoomIn() {
    const idx = POS_ZOOM_STEPS.indexOf(nearestZoomStep(posZoomLevel));
    applyPosZoom(POS_ZOOM_STEPS[Math.min(POS_ZOOM_STEPS.length - 1, idx + 1)]);
}
function posZoomOut() {
    const idx = POS_ZOOM_STEPS.indexOf(nearestZoomStep(posZoomLevel));
    applyPosZoom(POS_ZOOM_STEPS[Math.max(0, idx - 1)]);
}
function posZoomReset() { applyPosZoom(1); }
document.addEventListener('DOMContentLoaded', () => {
    try {
        const saved = parseFloat(localStorage.getItem(POS_ZOOM_KEY) || '1');
        if (!Number.isNaN(saved)) applyPosZoom(saved);
    } catch (e) {}
});

function positionNotifyPanel() {
    const panel = document.getElementById('notifyPanel');
    const btn = document.getElementById('notifyOrdersBtn');
    if (!panel || !btn) return;
    if (panel.parentElement !== document.body) {
        document.body.appendChild(panel);
    }
    const rect = btn.getBoundingClientRect();
    const width = Math.min(320, window.innerWidth - 24);
    let left = rect.right - width;
    left = Math.max(12, Math.min(left, window.innerWidth - width - 12));
    panel.style.width = width + 'px';
    panel.style.top = (rect.bottom + 8) + 'px';
    panel.style.left = left + 'px';
    panel.style.right = 'auto';
}

function closeNotifyPanel() {
    const panel = document.getElementById('notifyPanel');
    if (panel) panel.style.display = 'none';
    notifyPanelOpen = false;
}

function toggleNotifyPanel(e) {
    if (e) e.stopPropagation();
    const panel = document.getElementById('notifyPanel');
    if (!panel) return;
    notifyPanelOpen = !notifyPanelOpen;
    if (notifyPanelOpen) {
        positionNotifyPanel();
        panel.style.display = 'block';
        loadReadyOrders();
    } else {
        panel.style.display = 'none';
    }
}

// Close panel when clicking outside
document.addEventListener('click', function(e) {
    if (!notifyPanelOpen) return;
    if (e.target.closest('#notifyOrdersBtn') || e.target.closest('#notifyPanel')) return;
    closeNotifyPanel();
});

window.addEventListener('resize', function() {
    if (notifyPanelOpen) positionNotifyPanel();
});
window.addEventListener('scroll', function() {
    if (notifyPanelOpen) positionNotifyPanel();
}, true);

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'Just now';
    if (diffMins === 1) return '1 min ago';
    if (diffMins < 60) return diffMins + ' mins ago';
    const hours = Math.floor(diffMins / 60);
    return hours + 'h ' + (diffMins % 60) + 'm ago';
}

function loadReadyOrders() {
    const list = document.getElementById('notifyList');
    const badge = document.getElementById('notifyBadge');
    if (!list && !badge) return; // Ready UI replaced by Waiter alerts on restaurant POS
    fetch('/kitchen/ready-orders')
        .then(r => r.json())
        .then(data => {
            const orders = data.orders || [];

            const currentIds = orders.map(o => o.kot_number);
            const newOrders = currentIds.filter(id => !lastReadyOrders.includes(id));
            if (newOrders.length > 0 && lastReadyOrders.length > 0) {
                if (typeof playKitchenBell === 'function') playKitchenBell();
                else if (POS_LAYOUT_SOUND) {
                    const a = new Audio('https://assets.mixkit.co/active_storage/sfx/2000/2000-preview.mp3');
                    a.volume = 0.55;
                    a.play().catch(() => {});
                }
            }
            lastReadyOrders = currentIds;

            if (badge) {
                if (orders.length > 0) {
                    badge.textContent = orders.length;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
            if (!list) return;
            if (!orders.length) {
                list.innerHTML = '<p class="text-muted text-center my-3">No ready orders</p>';
                return;
            }

            list.innerHTML = orders.map(order => `
                <div onclick="typeof openOrderInCart === 'function' && openOrderInCart(${order.order_id}, true)" style="background: #1a1a2e; border-radius: 12px; padding: 12px; margin-bottom: 8px; border-left: 4px solid #00b894; cursor: pointer;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div style="font-weight: 700; color: #fff;">${order.kot_number}</div>
                            <div style="font-size: 0.85rem; color: #74b9ff;">Order: ${order.order_number}</div>
                            ${order.table_name ? `<div style="font-size: 0.8rem; color: #fdcb6e;"><i class="fas fa-table me-1"></i>${order.table_name}</div>` : ''}
                            ${order.waiter_name ? `<div style="font-size: 0.75rem; color: #a29bfe;"><i class="fas fa-user me-1"></i>${order.waiter_name}</div>` : ''}
                        </div>
                        <span style="font-size: 0.75rem; color: #00b894; font-weight: 600; white-space: nowrap;">
                            <i class="fas fa-check-circle me-1"></i>${formatTimeAgo(order.completed_at)}
                        </span>
                    </div>
                    <div style="margin-top: 8px; font-size: 0.85rem; color: #b2bec3;">
                        ${(order.items || []).map(item => `<span class="me-2">${item.name} x${item.quantity}</span>`).join('')}
                    </div>
                    <div style="margin-top: 8px; font-size: 0.75rem; color: #fbbf24;"><i class="fas fa-shopping-cart me-1"></i>Tap to open in cart</div>
                </div>
            `).join('');
        });
}

setInterval(() => { loadReadyOrders(); }, 8000);
setTimeout(loadReadyOrders, 2000);
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
function showToast(type, message) {
    Swal.fire({ toast: true, position: 'top-end', icon: type, title: message, showConfirmButton: false, timer: 3000, timerProgressBar: true });
}
$(document).ready(function() { $('.select2').select2({ theme: 'bootstrap-5', width: '100%' }); });
</script>
@stack('scripts')
<script src="{{ asset('js/avenque-tour.js') }}?v=1"></script>
</body>
</html>
