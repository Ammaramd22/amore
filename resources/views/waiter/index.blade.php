<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('pwa_app_name', 'QRPOS Waiter Panel') }}</title>
    @include('partials.waiter-pwa-head')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --panel: #1e293b;
            --card: #334155;
            --accent: #f59e0b;
            --accent2: #ea580c;
            --text: #f8fafc;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html { max-width: 100%; overflow-x: hidden; }
        body {
            margin: 0; background: var(--bg); color: var(--text);
            font-family: Inter, system-ui, -apple-system, sans-serif;
            min-height: 100vh; overflow-x: hidden; max-width: 100%;
        }
        .topbar {
            position: sticky; top: 0; z-index: 50;
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; padding: 12px 16px;
            background: linear-gradient(135deg, #1c1917, #292524);
            border-bottom: 2px solid var(--accent);
        }
        .topbar h1 { font-size: 1.05rem; margin: 0; font-weight: 700; }
        .topbar .meta { color: var(--muted); font-size: 0.8rem; }
        .btn-new-order {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: 0; color: #fff; font-weight: 700; border-radius: 12px;
            padding: 10px 14px; font-size: 0.85rem;
            display: inline-flex; align-items: center; gap: 6px;
            white-space: nowrap;
        }
        .btn-new-order .lbl { display: inline; }
        .btn-accent {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: 0; color: #fff; font-weight: 600; border-radius: 12px;
            padding: 12px 16px;
            min-height: 48px;
        }
        .btn-ghost {
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
            color: #fff; border-radius: 12px; padding: 10px 14px;
            min-height: 44px; min-width: 44px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .order-bill-actions {
            display: none;
            gap: 8px;
            margin-bottom: 10px;
        }
        .order-bill-actions.show { display: flex; }
        .order-bill-actions .btn-transfer {
            flex: 1;
            background: rgba(59, 130, 246, 0.18);
            border: 1px solid rgba(59, 130, 246, 0.45);
            color: #93c5fd;
            border-radius: 12px;
            padding: 10px 12px;
            font-weight: 600;
            min-height: 44px;
        }
        .order-bill-actions .btn-cancel-order {
            flex: 1;
            background: rgba(248, 113, 113, 0.15);
            border: 1px solid rgba(248, 113, 113, 0.4);
            color: #fca5a5;
            border-radius: 12px;
            padding: 10px 12px;
            font-weight: 600;
            min-height: 44px;
        }
        .screen { display: none; padding: 16px; padding-bottom: calc(16px + env(safe-area-inset-bottom)); }
        .screen.active { display: block; }
        #screenMenu.active {
            display: flex; flex-direction: column;
            min-height: calc(100vh - 62px); padding-bottom: 0;
            width: 100%; max-width: 100%; min-width: 0;
            overflow-x: hidden;
        }
        .screen-hint { color: var(--muted); font-size: 0.8rem; }
        .order-sheet-backdrop {
            display: none;
            position: fixed; inset: 0; z-index: 35;
            background: rgba(2, 6, 23, 0.55);
        }
        .order-sheet-backdrop.show { display: block; }
        .floor-title {
            color: var(--accent); font-size: 0.75rem; font-weight: 700;
            letter-spacing: 1px; text-transform: uppercase; margin: 8px 0 12px;
        }
        .table-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)); gap: 12px;
        }
        .table-card {
            background: var(--panel); border-radius: 16px; padding: 14px 12px 12px;
            border: 2px solid transparent; cursor: pointer; min-height: 150px;
            display: flex; flex-direction: column; align-items: center; text-align: center;
        }
        .table-card:active { transform: scale(0.98); }
        .table-card.available { border-color: rgba(16,185,129,0.35); }
        .table-card.occupied { border-color: rgba(245,158,11,0.55); background: #292524; }
        .table-card .name { font-size: 1rem; font-weight: 700; width: 100%; }
        .table-card .sub { color: var(--muted); font-size: 0.72rem; margin-top: 2px; width: 100%; }
        .table-card .meta-row {
            display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;
            font-size: 0.72rem; font-weight: 700; justify-content: center; width: 100%;
        }
        .table-card .meta-pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 8px; border-radius: 999px;
            background: rgba(255,255,255,0.06); color: #e2e8f0;
        }
        .table-card .meta-pill.timer { color: #fbbf24; background: rgba(245,158,11,0.15); font-variant-numeric: tabular-nums; }
        .table-card .meta-pill.guests { color: #7dd3fc; background: rgba(14,165,233,0.12); }

        /* Visual seat layout from admin capacity */
        .seat-diagram {
            position: relative;
            width: 108px;
            height: 88px;
            margin: 10px auto 6px;
            flex: 0 0 auto;
        }
        .seat-diagram .seat-table-top {
            position: absolute;
            left: 50%; top: 50%;
            transform: translate(-50%, -50%);
            width: 44%; height: 40%;
            border-radius: 10px;
            background: linear-gradient(160deg, #57534e, #292524);
            border: 2px solid rgba(245,158,11,0.35);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .seat-diagram.seats-n-2 .seat-table-top { width: 30%; height: 46%; border-radius: 40%; }
        .seat-diagram.seats-n-3 .seat-table-top,
        .seat-diagram.seats-n-4 .seat-table-top { width: 40%; height: 40%; border-radius: 12px; }
        .seat-diagram.seats-n-5 .seat-table-top,
        .seat-diagram.seats-n-6 .seat-table-top { width: 52%; height: 34%; border-radius: 10px; }
        .seat-diagram.seats-n-7 .seat-table-top,
        .seat-diagram.seats-n-8 .seat-table-top,
        .seat-diagram.seats-n-9 .seat-table-top,
        .seat-diagram.seats-n-10 .seat-table-top,
        .seat-diagram.seats-n-11 .seat-table-top,
        .seat-diagram.seats-n-12 .seat-table-top { width: 58%; height: 32%; border-radius: 10px; }
        .seat-chair {
            position: absolute;
            width: 16px; height: 11px;
            margin: 0;
            border-radius: 4px 4px 3px 3px;
            transform: translate(-50%, -50%) rotate(var(--seat-rot, 0deg));
            background: rgba(148,163,184,0.35);
            border: 1.5px solid rgba(148,163,184,0.55);
            box-sizing: border-box;
        }
        .table-card.available .seat-chair {
            background: rgba(16,185,129,0.12);
            border-color: rgba(52,211,153,0.55);
        }
        .table-card.occupied .seat-chair,
        .seat-chair.is-filled {
            background: rgba(245,158,11,0.85);
            border-color: #f59e0b;
            box-shadow: 0 0 0 1px rgba(245,158,11,0.25);
        }
        .table-card.occupied .seat-chair:not(.is-filled) {
            background: rgba(245,158,11,0.18);
            border-color: rgba(245,158,11,0.4);
            box-shadow: none;
        }
        .badge-soft {
            display: inline-block; margin-top: 10px; padding: 4px 8px; border-radius: 999px;
            font-size: 0.7rem; font-weight: 700;
        }
        .badge-soft.free { background: rgba(16,185,129,0.15); color: #34d399; }
        .badge-soft.busy { background: rgba(245,158,11,0.18); color: #fbbf24; }
        .menu-layout {
            flex: 1; display: grid; grid-template-columns: 1fr;
            gap: 14px; min-height: 0; min-width: 0;
            width: 100%; max-width: 100%;
        }
        @media (min-width: 900px) {
            .menu-layout {
                grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
                align-items: stretch;
                overflow: hidden;
            }
        }
        .menu-left, .menu-right {
            background: rgba(30,41,59,0.55);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 12px;
            min-height: 280px;
            min-width: 0;
            max-width: 100%;
        }
        .menu-left {
            container-type: inline-size;
            container-name: menupane;
            overflow: hidden;
        }
        .menu-right {
            display: flex; flex-direction: column;
            position: sticky; bottom: 0;
        }
        @media (min-width: 900px) {
            #screenMenu.active {
                height: calc(100dvh - 62px);
                min-height: calc(100dvh - 62px);
                max-height: calc(100dvh - 62px);
                overflow: hidden;
                padding-bottom: 0;
            }
            .menu-left {
                display: flex; flex-direction: column;
                min-height: 0; height: auto;
            }
            .menu-left .cats { flex: 0 0 auto; min-width: 0; }
            .menu-right {
                position: static;
                max-height: none;
                height: 100%;
                min-height: 0;
                overflow: hidden;
            }
            .order-footer { flex-shrink: 0; }
        }
        .cats {
            display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 10px;
            min-width: 0; max-width: 100%;
        }
        .cat-btn {
            flex: 0 0 auto; border: 0; border-radius: 999px; padding: 10px 16px;
            background: var(--panel); color: var(--muted); font-weight: 600;
        }
        .cat-btn.active { background: var(--accent); color: #fff; }
        .product-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
            width: 100%;
            max-width: 100%;
            align-content: start;
            box-sizing: border-box;
        }
        .product-card {
            background: var(--panel);
            border-radius: 14px;
            padding: 12px;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.08);
            min-height: 88px;
            min-width: 0;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 8px;
            overflow: hidden;
            -webkit-user-select: none;
            user-select: none;
        }
        .product-card:active {
            outline: 2px solid var(--accent);
            transform: scale(0.98);
            background: #3f4b5c;
        }
        .product-card .pname {
            font-weight: 700;
            font-size: 0.88rem;
            line-height: 1.3;
            color: #f8fafc;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .product-card .pfoot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: auto;
        }
        .product-card .pprice {
            color: var(--accent);
            font-weight: 800;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .product-card .padd {
            width: 34px; height: 34px; border-radius: 10px; border: 0;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff; font-weight: 800; font-size: 1.15rem; line-height: 1;
            display: inline-flex; align-items: center; justify-content: center;
            flex: 0 0 auto;
            pointer-events: none;
        }
        .order-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 10px; padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .order-list { flex: 1; overflow: auto; min-height: 120px; max-height: 42vh; min-width: 0; }
        @media (min-width: 900px) { .order-list { max-height: none; min-height: 0; } }
        .section-label {
            font-size: 0.72rem; font-weight: 700; color: var(--accent);
            text-transform: uppercase; letter-spacing: 0.8px; margin: 10px 0 8px;
        }
        .cart-item, .bill-item {
            display: flex; justify-content: space-between; gap: 10px; align-items: center;
            padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .bill-item { opacity: 0.85; }
        .item-name { font-weight: 600; font-size: 0.92rem; }
        .item-meta { color: var(--muted); font-size: 0.75rem; }
        .qty-wrap { display: flex; align-items: center; gap: 6px; }
        .qty-btn {
            width: 34px; height: 34px; border-radius: 10px; border: 0;
            background: var(--card); color: #fff; font-weight: 700;
        }
        .btn-remove {
            border: 0; background: transparent; color: #f87171; padding: 6px;
        }
        .btn-note {
            border: 1px solid rgba(245, 158, 11, 0.55);
            background: rgba(245, 158, 11, 0.16);
            color: #fbbf24;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-top: 6px;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
        }
        .item-note-chip {
            margin-top: 4px;
            padding: 6px 8px;
            border-radius: 8px;
            background: rgba(245, 158, 11, 0.12);
            color: #fcd34d;
            font-size: 0.78rem;
            font-weight: 700;
            word-break: break-word;
        }
        .order-footer {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 12px; margin-top: 8px;
        }
        .total-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 10px;
        }
        .total-row .total { font-size: 1.25rem; font-weight: 800; color: var(--accent); }
        .notes-input {
            width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff;
            border-radius: 10px; padding: 10px 12px; margin-bottom: 10px;
        }
        .menu-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            min-width: 0;
            width: 100%;
            flex: 0 0 auto;
        }
        .menu-top .table-meta { margin-left: auto; text-align: right; }
        .cust-search {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 999px;
            padding: 3px 3px 3px 10px;
            max-width: 220px;
        }
        .cust-search input {
            width: 110px;
            border: 0;
            outline: 0;
            background: transparent;
            color: #fff;
            font-size: 0.85rem;
            padding: 6px 0;
            min-width: 0;
        }
        .cust-search input::placeholder { color: #64748b; }
        .cust-search .btn-icon {
            width: 32px; height: 32px;
            border: 0; border-radius: 999px;
            background: rgba(245,158,11,0.15);
            color: #fbbf24;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer;
            flex: 0 0 auto;
        }
        .btn-add-cust {
            display: none;
            border: 0;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-add-cust.show { display: inline-flex; align-items: center; gap: 6px; }
        .cust-chip {
            display: none;
            align-items: center;
            gap: 8px;
            background: rgba(16,185,129,0.12);
            border: 1px solid rgba(16,185,129,0.35);
            color: #34d399;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.8rem;
            font-weight: 650;
            max-width: 100%;
        }
        .cust-chip.show { display: inline-flex; }
        .cust-chip .x {
            border: 0; background: transparent; color: #94a3b8; cursor: pointer; padding: 0 2px;
        }
        .empty { text-align: center; color: var(--muted); padding: 28px 12px; }
        .empty.sm { padding: 16px 8px; font-size: 0.85rem; }
        #printPreviewModal .modal-content { border-radius: 16px; overflow: hidden; }

        /* Floating draft cart — survives leaving menu / dashboard */
        .draft-float {
            display: none;
            position: fixed;
            left: 12px;
            right: 12px;
            bottom: calc(14px + env(safe-area-inset-bottom));
            z-index: 1040;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 18px;
            background: linear-gradient(135deg, #1c1917, #292524);
            border: 1px solid rgba(245,158,11,.55);
            box-shadow: 0 12px 40px rgba(0,0,0,.45), 0 0 0 1px rgba(245,158,11,.12);
            color: #fafaf9;
            text-decoration: none;
            cursor: pointer;
            max-width: 520px;
            margin: 0 auto;
        }
        .draft-float.show { display: flex; }
        .draft-float .df-icon {
            width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
            display: grid; place-items: center;
            background: rgba(245,158,11,.18); color: #f59e0b; font-size: 1.15rem;
            position: relative;
        }
        .draft-float .df-badge {
            position: absolute; top: -6px; right: -6px;
            min-width: 20px; height: 20px; padding: 0 5px;
            border-radius: 999px; background: #f59e0b; color: #1c1917;
            font-size: 0.7rem; font-weight: 800; display: grid; place-items: center;
        }
        .draft-float .df-body { flex: 1; min-width: 0; text-align: left; }
        .draft-float .df-title { font-weight: 800; font-size: 0.95rem; line-height: 1.2; }
        .draft-float .df-meta { color: #a8a29e; font-size: 0.78rem; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .draft-float .df-resume {
            flex-shrink: 0; border: 0; border-radius: 12px; padding: 10px 12px;
            background: #f59e0b; color: #1c1917; font-weight: 800; font-size: 0.8rem; cursor: pointer;
        }
        .draft-float .df-cancel {
            flex-shrink: 0; border: 0; background: transparent; color: #a8a29e;
            width: 36px; height: 36px; border-radius: 10px; font-size: 1.2rem; cursor: pointer;
        }
        .draft-float .df-cancel:hover { color: #f87171; background: rgba(248,113,113,.12); }
        body.has-draft-float #screenTables { padding-bottom: 96px; }

        /* —— Mobile phone layout (tablet+ unchanged) —— */
        @media (max-width: 767.98px) {
            html, body { overflow-x: hidden; max-width: 100vw; }
            .topbar {
                padding: 8px 12px;
                padding-top: calc(8px + env(safe-area-inset-top));
                gap: 8px;
            }
            .topbar h1 { font-size: 0.92rem; }
            .topbar h1 .fa-user-tie { display: none; }
            .topbar .meta { display: none; }
            .btn-new-order { padding: 8px 12px; border-radius: 10px; font-size: 0.8rem; }
            .btn-new-order .lbl { display: none; }
            .btn-ghost { padding: 8px 10px; border-radius: 10px; min-height: 40px; min-width: 40px; }

            .screen {
                padding: 12px;
                padding-left: max(12px, env(safe-area-inset-left));
                padding-right: max(12px, env(safe-area-inset-right));
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
                box-sizing: border-box;
            }
            .screen-hint { display: none; }
            .table-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }
            .table-card {
                padding: 10px 8px 10px; min-height: 140px; border-radius: 14px;
            }
            .table-card .name { font-size: 0.95rem; }
            .table-card .sub { font-size: 0.68rem; }
            .table-card .meta-row { font-size: 0.65rem; gap: 4px; }
            .seat-diagram { width: 96px; height: 78px; margin: 8px auto 4px; }
            .seat-chair { width: 13px; height: 9px; }
            .badge-soft { font-size: 0.65rem; margin-top: 6px; }

            .menu-top {
                gap: 8px;
                margin-bottom: 10px;
                align-items: flex-start;
                width: 100%;
                max-width: 100%;
            }
            .menu-top .table-meta {
                order: -1;
                width: 100%;
                margin-left: 0;
                text-align: left;
            }
            .menu-top .btn-ghost .hide-sm { display: none; }
            .cust-search { max-width: none; flex: 1 1 auto; min-width: 0; }
            .cust-search input { width: 100%; flex: 1; font-size: 16px; /* avoid iOS zoom */ }
            .btn-add-cust { padding: 8px 10px; font-size: 0.75rem; }
            .cust-chip { max-width: 100%; font-size: 0.75rem; }

            #screenMenu.active {
                min-height: calc(100dvh - 56px);
                padding-bottom: calc(148px + env(safe-area-inset-bottom));
                overflow-x: hidden;
            }
            .menu-layout {
                gap: 0;
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }
            .menu-left {
                background: transparent;
                border: 0;
                padding: 0;
                min-height: 0;
                border-radius: 0;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow: hidden;
            }
            .cats {
                gap: 6px;
                margin-bottom: 10px;
                -webkit-overflow-scrolling: touch;
                width: 100%;
                max-width: 100%;
            }
            .cat-btn { padding: 10px 14px; font-size: 0.85rem; min-height: 40px; }
            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .product-card {
                padding: 12px;
                min-height: 96px;
                border-radius: 14px;
            }
            .product-card .pname { font-size: 0.86rem; line-height: 1.3; }
            .product-card .pprice { font-size: 0.88rem; }
            .product-card .padd { width: 36px; height: 36px; }

            .menu-right {
                position: fixed;
                left: 0; right: 0; bottom: 0;
                z-index: 40;
                max-height: min(72dvh, 580px);
                border-radius: 20px 20px 0 0;
                border: 1px solid rgba(255,255,255,0.1);
                border-bottom: 0;
                background: #1e293b;
                padding: 10px 14px calc(14px + env(safe-area-inset-bottom));
                transform: translateY(calc(100% - 132px - env(safe-area-inset-bottom)));
                transition: transform 0.28s ease;
                box-shadow: 0 -8px 32px rgba(0,0,0,0.35);
                min-height: 0;
                width: 100%;
                max-width: 100vw;
                box-sizing: border-box;
            }
            .menu-right.sheet-open {
                transform: translateY(0);
            }
            .menu-right:not(.sheet-open) .order-list,
            .menu-right:not(.sheet-open) .notes-input {
                display: none;
            }
            .menu-right:not(.sheet-open) .order-head {
                margin-bottom: 0;
                padding-bottom: 4px;
            }
            .menu-right:not(.sheet-open) .section-label,
            .menu-right:not(.sheet-open) .bill-item,
            .menu-right:not(.sheet-open) .cart-item {
                display: none;
            }
            .menu-right .order-head {
                cursor: pointer;
                padding: 4px 0 8px;
                margin-bottom: 6px;
            }
            .menu-right .order-head::before {
                content: '';
                display: block;
                width: 40px; height: 4px;
                border-radius: 999px;
                background: rgba(255,255,255,0.22);
                margin: 0 auto 10px;
            }
            .order-list {
                max-height: 36dvh;
                min-height: 80px;
            }
            .order-footer { padding-top: 8px; }
            .notes-input { font-size: 16px; margin-bottom: 8px; }
            .qty-btn { width: 42px; height: 42px; }
            .btn-accent { min-height: 52px; font-size: 1rem; width: 100%; }
        }

        @media (max-width: 360px) {
            .product-card { min-height: 90px; padding: 10px; }
            .product-card .pname { font-size: 0.8rem; -webkit-line-clamp: 2; }
            .product-card .padd { width: 32px; height: 32px; font-size: 1rem; }
        }

        @media (min-width: 768px) and (max-width: 899.98px) {
            .table-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 10px !important; }
            .menu-layout { gap: 12px; }
        }

        /* Keep 2 columns in the menu pane (auto-fill was collapsing to 1) */
        @media (min-width: 900px) {
            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                flex: 1;
                overflow-y: auto;
                min-height: 0;
            }
        }
        @container menupane (min-width: 760px) {
            .product-grid { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
        }
    </style>
</head>
<body>
    @include('partials.waiter-pwa-splash')
    <div class="topbar">
        <div>
            <h1 style="display:flex;align-items:center;gap:10px;">
                <img src="{{ \App\Models\Setting::pwaLogoUrl() }}" alt="" width="32" height="32" style="border-radius:8px;background:#0a0a0a;object-fit:contain;">
                {{ \App\Models\Setting::get('pwa_app_short_name', 'QRPOS Waiter') }}
            </h1>
            <div class="meta">{{ $waiter->name }} · By Avenque</div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" class="btn-new-order" id="btnQrPending" onclick="showQrPending()" title="QR orders waiting" style="display:none;background:linear-gradient(135deg,#0ea5e9,#0284c7);">
                <i class="fas fa-qrcode"></i> <span class="lbl">QR <span id="qrPendingCount">0</span></span>
            </button>
            <button type="button" class="btn-new-order" onclick="startNewOrder()" title="Take a new order">
                <i class="fas fa-plus"></i> <span class="lbl">New Order</span>
            </button>
            <a class="btn-ghost" href="{{ route('waiter.dashboard') }}" title="Dashboard" style="text-decoration:none;display:inline-flex;align-items:center;"><i class="fas fa-home"></i></a>
            <button class="btn-ghost" onclick="refreshTables()" title="Refresh"><i class="fas fa-sync"></i></button>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn-ghost" type="submit"><i class="fas fa-sign-out-alt"></i></button>
            </form>
        </div>
    </div>

    <div id="screenTables" class="screen active">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold">Select Table</div>
            <small class="text-muted screen-hint">Code on card = guest unlock · tap table to order</small>
    </div>
        <div id="qrPendingBox" class="mb-3" style="display:none;"></div>
        <div id="tablesContainer" class="empty"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>

    <div id="screenMenu" class="screen">
        <div class="menu-top">
            <button class="btn-ghost" onclick="backToTables()"><i class="fas fa-arrow-left me-1"></i><span class="hide-sm">Tables</span></button>

            <div class="cust-search" title="Customer phone">
                <i class="fas fa-mobile-alt" style="color:#f59e0b;font-size:0.8rem;"></i>
                <input
                    type="tel"
                    id="customerPhone"
                    placeholder="Phone"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    enterkeyhint="search"
                    autocomplete="tel"
                >
                <button type="button" class="btn-icon" onclick="checkCustomerPhone()" title="Search"><i class="fas fa-search"></i></button>
    </div>

            <button type="button" id="btnAddCustomer" class="btn-add-cust" onclick="openAddCustomerPopup()">
                <i class="fas fa-user-plus"></i> Add customer
            </button>

            <div id="customerChip" class="cust-chip">
                <span id="customerChipText"></span>
                <button type="button" class="x" onclick="clearCustomer()" title="Clear">&times;</button>
</div>

            <div class="table-meta">
                <div class="fw-bold" id="selectedTableLabel">Table</div>
                <small class="text-muted" id="activeOrderLabel"></small>
    </div>
</div>

        <div class="menu-layout">
            <div class="menu-left">
                <div class="cats" id="categoryTabs"></div>
                <div class="product-grid" id="productGrid"></div>
            </div>

            <div class="menu-right" id="orderSheet">
                <div class="order-head" onclick="toggleOrderSheet()">
                    <div>
                        <div class="fw-bold">Current Order</div>
                        <small class="text-muted" id="orderPanelSub">Add items from menu</small>
                    </div>
                    <button class="btn-ghost btn-sm" onclick="event.stopPropagation(); clearNewItems()" title="Clear new items"><i class="fas fa-trash"></i></button>
                </div>

                <div class="order-list" id="orderList">
                    <div class="empty sm">No items yet — tap a product to add</div>
                </div>

                <div class="order-footer">
                    <div class="order-bill-actions" id="orderBillActions">
                        <button type="button" class="btn-transfer" onclick="transferTable()"><i class="fas fa-exchange-alt me-1"></i>Transfer</button>
                        <button type="button" class="btn-cancel-order" onclick="cancelActiveOrder()"><i class="fas fa-ban me-1"></i>Cancel</button>
                    </div>
                    <input type="text" id="orderNotes" class="notes-input" placeholder="Order notes (less spicy, no onion...)">
                    <div class="total-row">
                        <div>
                            <div class="text-muted small" id="cartCountLabel">0 new items</div>
                            <div class="text-muted small" id="billTotalLabel"></div>
                        </div>
                        <div class="total" id="cartTotalLabel">LKR 0.00</div>
                    </div>
                    <button class="btn-accent w-100" id="sendOrderBtn" onclick="sendToKitchen()">
                        <i class="fas fa-paper-plane me-2" id="sendOrderBtnIcon"></i><span id="sendOrderBtnLabel">Send KOT</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="order-sheet-backdrop" id="orderSheetBackdrop" onclick="closeOrderSheet()"></div>

    <audio id="qrBell" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
    </audio>

    <div class="draft-float" id="draftFloat" role="button" tabindex="0" aria-label="Resume draft order">
        <div class="df-icon">
            <i class="fas fa-shopping-bag"></i>
            <span class="df-badge" id="draftFloatCount">0</span>
        </div>
        <div class="df-body" onclick="resumeDraftOrder()">
            <div class="df-title" id="draftFloatTitle">Draft order</div>
            <div class="df-meta" id="draftFloatMeta">Tap to continue</div>
        </div>
        <button type="button" class="df-resume" onclick="resumeDraftOrder()">Continue</button>
        <button type="button" class="df-cancel" onclick="cancelDraftOrder()" title="Cancel draft">&times;</button>
    </div>

    <div class="modal fade" id="printPreviewModal" tabindex="-1" aria-hidden="true" style="z-index:1080;">
        <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff;">
                    <h5 class="modal-title fw-bold"><i class="fas fa-print me-2"></i>Print Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="printPreviewFrame" style="width:100%;height:480px;border:0;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning text-white" onclick="printPreviewFrame()">Print</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.all.min.js"></script>
<script>
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const currency = @json($settings['currency_symbol'] ?? 'LKR');
        let printAskBefore = {{ $settings['print_ask_before'] ? 'true' : 'false' }};
        let autoPrintKot = {{ $settings['auto_print_kot'] ? 'true' : 'false' }};

        let tables = [];
        let categories = [];
        let selectedTable = null;
        let activeCategoryId = 'all';
        let cart = [];          // new items to send
        let billItems = [];     // already on unpaid bill
        let originalBillSignature = '';
        let billTotal = 0;
        let selectedCustomer = null; // {id, name, phone}
        let activeOrderId = null;
        let pendingGuestCount = null;
        let tableTimerInterval = null;
        let printPreviewQueue = [];
        let printPreviewBusy = false;
        const DRAFT_KEY = 'waiter_order_draft_v1';
        let lastQrIds = [];
        let qrSoundPrimed = false;

        function primeQrSound() {
            if (qrSoundPrimed) return;
            qrSoundPrimed = true;
            const a = document.getElementById('qrBell');
            if (!a) return;
            a.muted = true;
            a.play().then(() => { a.pause(); a.currentTime = 0; a.muted = false; }).catch(() => { a.muted = false; });
        }
        document.addEventListener('pointerdown', primeQrSound, { once: true });

        function playQrBell() {
            const a = document.getElementById('qrBell');
            if (!a) return;
            a.currentTime = 0;
            a.volume = 0.85;
            a.play().catch(() => {});
            setTimeout(() => { a.currentTime = 0; a.play().catch(() => {}); }, 700);
        }

        function draftLineTotal(item) {
            const addons = (item.addons || []).reduce((s, a) => s + Number(a.price || 0), 0);
            return (Number(item.price || 0) + addons) * Number(item.quantity || 0);
        }

        function peekDraft() {
            try {
                const raw = sessionStorage.getItem(DRAFT_KEY);
                if (!raw) return null;
                const d = JSON.parse(raw);
                if (!d || !Array.isArray(d.cart) || !d.cart.length || !d.table?.id) return null;
                return d;
            } catch (e) {
                return null;
            }
        }

        function saveDraft() {
            if (!selectedTable) {
                updateFloatingCart();
                return;
            }
            if (!cart.length) {
                // Empty cart on the menu screen means draft is done / cleared.
                // Leaving to tables sets selectedTable=null first so draft is kept.
                if (document.getElementById('screenMenu')?.classList.contains('active')) {
                    sessionStorage.removeItem(DRAFT_KEY);
                }
                updateFloatingCart();
                return;
            }
            const payload = {
                table: {
                    id: selectedTable.id,
                    name: selectedTable.name,
                    floor: selectedTable.floor || null,
                    capacity: selectedTable.capacity || null,
                    active_order: selectedTable.active_order || null,
                },
                cart: cart,
                notes: document.getElementById('orderNotes')?.value || '',
                customer: selectedCustomer,
                customer_phone: (document.getElementById('customerPhone')?.value || '').trim(),
                pending_guest_count: pendingGuestCount,
                active_order_id: activeOrderId || selectedTable.active_order?.id || null,
                saved_at: Date.now(),
            };
            sessionStorage.setItem(DRAFT_KEY, JSON.stringify(payload));
            updateFloatingCart();
        }

        function clearDraft() {
            sessionStorage.removeItem(DRAFT_KEY);
            updateFloatingCart();
        }

        function updateFloatingCart() {
            const el = document.getElementById('draftFloat');
            if (!el) return;
            const onTables = document.getElementById('screenTables')?.classList.contains('active');
            const draft = peekDraft();
            const show = !!(draft && onTables);
            el.classList.toggle('show', show);
            document.body.classList.toggle('has-draft-float', show);
            if (!draft) return;
            const count = draft.cart.reduce((s, i) => s + Number(i.quantity || 0), 0);
            const total = draft.cart.reduce((s, i) => s + draftLineTotal(i), 0);
            const tableName = draft.table.name || ('Table ' + draft.table.id);
            document.getElementById('draftFloatCount').textContent = count;
            document.getElementById('draftFloatTitle').textContent = tableName + ' · draft';
            document.getElementById('draftFloatMeta').textContent =
                count + ' item' + (count === 1 ? '' : 's') + ' · ' + currency + ' ' + total.toFixed(2) + ' · tap Continue';
        }

        function cancelDraftOrder() {
            Swal.fire({
                title: 'Cancel draft order?',
                text: 'Unsent items will be removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Cancel draft',
                cancelButtonText: 'Keep',
                confirmButtonColor: '#ef4444',
            }).then(res => {
                if (!res.isConfirmed) return;
                clearDraft();
                cart = [];
                if (document.getElementById('screenMenu')?.classList.contains('active')) {
                    updateCartUI();
                }
                showToast('success', 'Draft cancelled');
            });
        }

        function resumeDraftOrder() {
            const draft = peekDraft();
            if (!draft) {
                showToast('warning', 'No draft order');
                return;
            }

            const apply = () => {
                selectedTable = tables.find(t => t.id === draft.table.id) || draft.table;
                pendingGuestCount = draft.pending_guest_count || null;
                activeOrderId = draft.active_order_id || selectedTable.active_order?.id || null;
                cart = Array.isArray(draft.cart) ? draft.cart : [];
                billItems = [];
                billTotal = 0;

                document.getElementById('selectedTableLabel').textContent =
                    selectedTable.name + (selectedTable.floor ? ' · ' + selectedTable.floor : '');
                document.getElementById('orderNotes').value = draft.notes || '';

                if (draft.customer) {
                    setCustomerUI(draft.customer);
                } else {
                    clearCustomer(true);
                    if (draft.customer_phone) {
                        document.getElementById('customerPhone').value = draft.customer_phone;
                    }
                }

                const loadBill = activeOrderId
                    ? loadExistingOrder(activeOrderId)
                    : Promise.resolve();

                return loadBill.then(() => loadMenu()).then(() => {
                    const guests = pendingGuestCount || selectedTable.active_order?.guest_count;
                    document.getElementById('activeOrderLabel').textContent = selectedTable.active_order
                        ? ('Editing ' + (selectedTable.active_order.order_number || '') + (guests ? ' · ' + guests + ' guests' : ''))
                        : ('New order' + (guests ? ' · ' + guests + ' guests' : ''));
                    document.getElementById('orderPanelSub').textContent = 'Draft restored — send when ready';
                    showScreen('screenMenu');
                    updateCartUI();
                    saveDraft();
                    if (isMobileWaiter()) openOrderSheet();
                    showToast('success', 'Draft order restored');
                });
            };

            if (!tables.length) {
                fetch('/waiter/tables').then(r => r.json()).then(data => {
                    tables = data.tables || data || [];
                    apply();
                }).catch(() => apply());
                return;
            }
            apply();
        }

        function showToast(icon, title) {
            Swal.fire({ toast: true, position: 'top', icon, title, showConfirmButton: false, timer: 2000 });
        }

        function showScreen(id) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            if (id === 'screenTables') {
                startTableTimers();
                closeOrderSheet();
            } else {
                stopTableTimers();
            }
            updateCartUI();
            updateFloatingCart();
        }

        function isMobileWaiter() {
            return window.matchMedia('(max-width: 767.98px)').matches;
        }

        function toggleOrderSheet() {
            if (!isMobileWaiter()) return;
            const sheet = document.getElementById('orderSheet');
            if (!sheet) return;
            if (sheet.classList.contains('sheet-open')) closeOrderSheet();
            else openOrderSheet();
        }

        function openOrderSheet() {
            if (!isMobileWaiter()) return;
            document.getElementById('orderSheet')?.classList.add('sheet-open');
            document.getElementById('orderSheetBackdrop')?.classList.add('show');
        }

        function closeOrderSheet() {
            document.getElementById('orderSheet')?.classList.remove('sheet-open');
            document.getElementById('orderSheetBackdrop')?.classList.remove('show');
        }

        function formatElapsed(startedAt) {
            if (!startedAt) return '00:00';
            const start = new Date(startedAt).getTime();
            if (Number.isNaN(start)) return '00:00';
            let secs = Math.max(0, Math.floor((Date.now() - start) / 1000));
            const h = Math.floor(secs / 3600);
            const m = Math.floor((secs % 3600) / 60);
            const s = secs % 60;
            const pad = n => String(n).padStart(2, '0');
            return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
        }

        function startTableTimers() {
            stopTableTimers();
            tickTableTimers();
            tableTimerInterval = setInterval(tickTableTimers, 1000);
        }

        function stopTableTimers() {
            if (tableTimerInterval) {
                clearInterval(tableTimerInterval);
                tableTimerInterval = null;
            }
        }

        function tickTableTimers() {
            document.querySelectorAll('[data-started-at]').forEach(el => {
                el.textContent = formatElapsed(el.getAttribute('data-started-at'));
            });
        }

        function refreshTables() {
            document.getElementById('tablesContainer').innerHTML = '<div class="empty"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            fetch('/waiter/tables')
        .then(r => r.json())
        .then(data => {
                    tables = data.tables || data || [];
                    renderTables();
                    refreshQrPending();
                })
                .catch(() => {
                    document.getElementById('tablesContainer').innerHTML = '<div class="empty text-danger">Could not load tables</div>';
                });
        }

        function refreshQrPending() {
            fetch('/waiter/qr-orders')
                .then(r => r.json())
                .then(data => {
                    const orders = data.orders || [];
                    const ids = orders.map(o => o.id);
                    const newly = ids.filter(id => !lastQrIds.includes(id));
                    if (newly.length) {
                        playQrBell();
                        if (lastQrIds.length) {
                            showToast('info', newly.length + ' guest QR order' + (newly.length > 1 ? 's' : '') + ' waiting');
                        }
                    }
                    lastQrIds = ids;

                    const btn = document.getElementById('btnQrPending');
                    const box = document.getElementById('qrPendingBox');
                    document.getElementById('qrPendingCount').textContent = orders.length;
                    if (!orders.length) {
                        btn.style.display = 'none';
                        box.style.display = 'none';
                        box.innerHTML = '';
                        return;
                    }
                    btn.style.display = '';
                    box.style.display = '';
                    box.innerHTML = `
                        <div style="background:#0c4a6e;border:1px solid #38bdf8;border-radius:14px;padding:12px;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong style="color:#7dd3fc;"><i class="fas fa-qrcode me-1"></i>QR orders — any waiter can accept (${orders.length})</strong>
                    </div>
                            ${orders.map(o => `
                                <div style="background:rgba(0,0,0,.25);border-radius:12px;padding:10px;margin-bottom:8px;">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                        <div>
                                            <div class="fw-bold">${escapeHtml(o.table || 'Table')} · ${escapeHtml(o.order_number)}</div>
                                            <small style="color:#bae6fd;">Code ${escapeHtml(o.qr_code || '—')} · ${escapeHtml(o.created_at || '')} · ${currency} ${Number(o.total || 0).toFixed(0)}</small>
                                            <div style="font-size:.85rem;margin-top:4px;color:#e0f2fe;">${(o.items||[]).map(i => escapeHtml(i.name) + ' ×' + i.quantity).join(' · ')}</div>
                                            ${o.notes ? `<div style="font-size:.8rem;color:#fde68a;margin-top:4px;">${escapeHtml(o.notes)}</div>` : ''}
                </div>
                                        <div class="d-flex gap-2 align-items-start">
                                            <button type="button" class="btn-accent" style="padding:8px 12px;min-height:0;" onclick="acceptQrOrder(${o.id})">Accept</button>
                                            <button type="button" class="btn-ghost" style="color:#fca5a5;" onclick="rejectQrOrder(${o.id})">Reject</button>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>`;
                })
                .catch(() => {});
        }

        function showQrPending() {
            showScreen('screenTables');
            refreshQrPending();
            document.getElementById('qrPendingBox')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function acceptQrOrder(id) {
            fetch('/waiter/qr-orders/' + id + '/accept', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return showToast('error', data.message || 'Failed');
                showToast('success', data.message || 'Accepted — KOT prints on POS');
                // Printing is handled by POS Print Bridge pending-KOT queue.
                refreshTables();
            })
            .catch(() => showToast('error', 'Network error'));
        }

        function rejectQrOrder(id) {
            Swal.fire({
                title: 'Reject this QR order?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#ef4444',
            }).then(res => {
                if (!res.isConfirmed) return;
                fetch('/waiter/qr-orders/' + id + '/reject', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                })
        .then(r => r.json())
        .then(data => {
                    showToast(data.success ? 'success' : 'error', data.message || (data.success ? 'Rejected' : 'Failed'));
                    refreshTables();
                });
            });
        }

        function seatDiagramHtml(capacity, opts = {}) {
            const n = Math.max(1, Math.min(12, Number(capacity) || 4));
            const occupied = !!opts.occupied;
            const guestCount = opts.guests != null && opts.guests !== ''
                ? Math.max(0, Math.min(n, Number(opts.guests)))
                : null;
            const radius = n <= 2 ? 34 : (n <= 4 ? 36 : (n <= 6 ? 38 : 40));
            let chairs = '';
            for (let i = 0; i < n; i++) {
                const angle = (360 / n) * i - 90;
                const rad = angle * Math.PI / 180;
                const x = 50 + radius * Math.cos(rad);
                const y = 50 + radius * Math.sin(rad);
                const filled = guestCount !== null ? (i < guestCount) : occupied;
                chairs += `<span class="seat-chair${filled ? ' is-filled' : ''}" style="left:${x.toFixed(2)}%;top:${y.toFixed(2)}%;--seat-rot:${(angle + 90).toFixed(1)}deg;"></span>`;
            }
            return `<div class="seat-diagram seats-n-${n}${occupied ? ' is-occupied' : ''}" data-seats="${n}" aria-label="${n} seats"><div class="seat-table-top"></div>${chairs}</div>`;
        }

        function renderTables() {
            const byFloor = {};
            tables.forEach(t => {
                const f = t.floor || 'Main';
                if (!byFloor[f]) byFloor[f] = [];
                byFloor[f].push(t);
            });
            let html = '';
            Object.keys(byFloor).forEach(floor => {
                html += `<div class="floor-title">${floor}</div><div class="table-grid">`;
                byFloor[floor].forEach(t => {
                    const busy = t.status === 'occupied' || t.active_order;
                    const ao = t.active_order;
                    const guests = ao?.guest_count;
                    const started = ao?.started_at;
                    const seats = Number(t.capacity) || 4;
                    html += `
                    <div class="table-card ${busy ? 'occupied' : 'available'}" onclick="selectTable(${t.id})">
                        <div class="name">${t.name}</div>
                        <div class="sub">Code <strong style="color:var(--accent);letter-spacing:.12em;">${t.qr_code || '—'}</strong></div>
                        ${seatDiagramHtml(seats, { occupied: busy, guests })}
                        ${t.pending_qr ? `<div class="meta-row"><span class="meta-pill" style="background:#0369a1;color:#fff;"><i class="fas fa-qrcode"></i> ${t.pending_qr} QR wait</span></div>` : ''}
                        ${ao ? `
                            <div class="meta-row">
                                ${started ? `<span class="meta-pill timer"><i class="fas fa-clock"></i> <span data-started-at="${started}">${formatElapsed(started)}</span></span>` : ''}
                                ${guests ? `<span class="meta-pill guests"><i class="fas fa-users"></i> ${guests}/${seats}</span>` : `<span class="meta-pill guests"><i class="fas fa-chair"></i> ${seats}</span>`}
                            </div>
                            <span class="badge-soft busy">${ao.order_number} · ${currency} ${Number(ao.total).toFixed(0)}</span>
                            <div class="sub" style="margin-top:4px;color:var(--accent);">Tap to edit / add items</div>
                        ` : `<span class="badge-soft free">${seats} seats · Available</span>`}
                    </div>`;
                });
                html += `</div>`;
            });
            document.getElementById('tablesContainer').innerHTML = html || '<div class="empty">No tables found</div>';
            if (document.getElementById('screenTables')?.classList.contains('active')) {
                startTableTimers();
            }
        }

        function askGuestCount(capacity) {
            const max = Math.max(1, Number(capacity) || 20);
            return Swal.fire({
                title: 'How many guests?',
                input: 'number',
                inputLabel: 'People at this table',
                inputValue: Math.min(2, max),
                inputAttributes: { min: 1, max, step: 1, inputmode: 'numeric' },
                showCancelButton: true,
                confirmButtonText: 'Continue',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#f59e0b',
                inputValidator: (value) => {
                    const n = parseInt(value, 10);
                    if (!n || n < 1) return 'Enter guest count';
                    if (n > max) return `Max ${max} for this table`;
                    return null;
                }
            }).then(result => {
                if (!result.isConfirmed) return null;
                return parseInt(result.value, 10);
            });
        }

        function openMenuForTable(opts = {}) {
            const keepCart = !!opts.keepCart;
            document.getElementById('selectedTableLabel').textContent =
                selectedTable.name + (selectedTable.floor ? ' · ' + selectedTable.floor : '');

            if (!keepCart) {
                const draft = peekDraft();
                // Same table with a saved draft → restore items
                if (draft && draft.table?.id === selectedTable.id && draft.cart?.length) {
                    cart = draft.cart;
                    document.getElementById('orderNotes').value = draft.notes || '';
                    pendingGuestCount = draft.pending_guest_count || pendingGuestCount;
                    if (draft.customer) setCustomerUI(draft.customer);
                    else {
                        clearCustomer(true);
                        if (draft.customer_phone) {
                            document.getElementById('customerPhone').value = draft.customer_phone;
                        }
                    }
                } else {
                    cart = [];
                    clearCustomer(true);
                    document.getElementById('orderNotes').value = '';
                }
            }

            billItems = [];
            originalBillSignature = '';
            billTotal = 0;
            activeOrderId = selectedTable.active_order?.id || null;

            const load = selectedTable.active_order
                ? loadExistingOrder(selectedTable.active_order.id)
                : Promise.resolve();

            load.then(() => loadMenu()).then(() => {
                const guests = pendingGuestCount || selectedTable.active_order?.guest_count;
                document.getElementById('activeOrderLabel').textContent = selectedTable.active_order
                    ? ('Editing ' + selectedTable.active_order.order_number + (guests ? ' · ' + guests + ' guests' : ''))
                    : ('New order' + (guests ? ' · ' + guests + ' guests' : ''));
                document.getElementById('orderPanelSub').textContent = selectedTable.active_order
                    ? (billItems.length
                        ? ('Edit items below, add new, or delete — then Update & Send KOT')
                        : 'Edit bill items below, or add new — then Update & Send KOT')
                    : 'Add items from menu';
                showScreen('screenMenu');
                updateCartUI();
                saveDraft();
                // On phone, open Current Order so existing items are visible for edit
                if (selectedTable.active_order && typeof openOrderSheet === 'function') {
                    openOrderSheet();
                }
            });
        }

        function selectTable(id) {
            const draft = peekDraft();
            if (draft && draft.table?.id !== id && draft.cart?.length) {
                Swal.fire({
                    title: 'Draft on ' + (draft.table.name || 'another table'),
                    text: 'You have unsent items. Continue that draft, or discard and open this table?',
                    icon: 'question',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Continue draft',
                    denyButtonText: 'Discard & open',
                    cancelButtonText: 'Stay',
                    confirmButtonColor: '#f59e0b',
                    denyButtonColor: '#ef4444',
                }).then(res => {
                    if (res.isConfirmed) {
                        resumeDraftOrder();
                        return;
                    }
                    if (res.isDenied) {
                        clearDraft();
                        selectTableAfterDraftCheck(id);
                    }
                });
                return;
            }
            selectTableAfterDraftCheck(id);
        }

        function selectTableAfterDraftCheck(id) {
            selectedTable = tables.find(t => t.id === id);
            if (!selectedTable) return;

            if (selectedTable.active_order) {
                pendingGuestCount = selectedTable.active_order.guest_count || null;
                openMenuForTable();
                return;
            }

            // Same table draft already has guest count
            const draft = peekDraft();
            if (draft && draft.table?.id === id && draft.pending_guest_count) {
                pendingGuestCount = draft.pending_guest_count;
                openMenuForTable();
                return;
            }

            askGuestCount(selectedTable.capacity).then(count => {
                if (count === null) {
                    selectedTable = null;
                    return;
                }
                pendingGuestCount = count;
                openMenuForTable();
            });
        }

        function showAddCustomerBtn(show) {
            document.getElementById('btnAddCustomer')?.classList.toggle('show', !!show);
        }

        function clearCustomer(silent = false) {
            selectedCustomer = null;
            const phone = document.getElementById('customerPhone');
            if (phone) phone.value = '';
            showAddCustomerBtn(false);
            document.getElementById('customerChip')?.classList.remove('show');
            if (!silent) showToast('success', 'Customer cleared');
        }

        function setCustomerUI(customer) {
            selectedCustomer = customer;
            document.getElementById('customerPhone').value = customer.phone || '';
            showAddCustomerBtn(false);
            const chip = document.getElementById('customerChip');
            const text = document.getElementById('customerChipText');
            if (text) text.textContent = (customer.name || 'Customer') + ' · ' + (customer.phone || '');
            if (chip) chip.classList.add('show');
        }

        function checkCustomerPhone() {
            const phone = (document.getElementById('customerPhone').value || '').trim();
            if (!phone) { showToast('warning', 'Enter phone number'); return; }

            fetch('/waiter/customer-lookup?phone=' + encodeURIComponent(phone))
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        showToast('warning', data.message || 'Invalid phone');
                        return;
                    }
                    if (data.found && data.customer) {
                        setCustomerUI(data.customer);
                        showToast('success', 'Customer found');
                        return;
                    }
                    selectedCustomer = null;
                    document.getElementById('customerChip')?.classList.remove('show');
                    showAddCustomerBtn(true);
                    showToast('info', 'Not found — tap Add customer');
                })
                .catch(() => showToast('error', 'Network error'));
        }

        function openAddCustomerPopup(prefillPhone) {
            const phoneVal = (prefillPhone || document.getElementById('customerPhone').value || '').trim();
            Swal.fire({
                title: 'Add customer',
                html: `
                    <div style="text-align:left;margin-top:8px;">
                        <label style="font-size:0.75rem;font-weight:700;color:#94a3b8;">Phone</label>
                        <input id="swalCustPhone" type="tel" inputmode="numeric" pattern="[0-9]*" class="swal2-input" style="margin:6px 0 12px;width:100%;" value="${phoneVal.replace(/"/g, '&quot;')}" placeholder="07X XXX XXXX">
                        <label style="font-size:0.75rem;font-weight:700;color:#94a3b8;">Name</label>
                        <input id="swalCustName" type="text" inputmode="text" autocomplete="name" autocapitalize="words" class="swal2-input" style="margin:6px 0 0;width:100%;" placeholder="Customer name">
                        </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Save',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#f59e0b',
                focusConfirm: false,
                didOpen: () => {
                    const nameEl = document.getElementById('swalCustName');
                    const phoneEl = document.getElementById('swalCustPhone');
                    if (phoneVal) nameEl?.focus();
                    else phoneEl?.focus();
                },
                preConfirm: () => {
                    const phone = (document.getElementById('swalCustPhone')?.value || '').trim();
                    const name = (document.getElementById('swalCustName')?.value || '').trim();
                    if (!phone) {
                        Swal.showValidationMessage('Phone is required');
                        return false;
                    }
                    if (!name) {
                        Swal.showValidationMessage('Name is required');
                        return false;
                    }
                    return { phone, name };
                }
            }).then(result => {
                if (!result.isConfirmed || !result.value) return;
                saveCustomer(result.value.phone, result.value.name);
            });
        }

        function saveCustomer(phone, name) {
            const body = { phone, name };
            const orderId = activeOrderId || selectedTable?.active_order?.id;
            const url = orderId ? ('/waiter/order/' + orderId + '/customer') : '/waiter/customer';

            Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ data }) => {
                Swal.close();
                if (data.needs_name) {
                    openAddCustomerPopup(phone);
                    showToast('warning', data.message || 'Name required');
                    return;
                }
                if (!data.success) {
                    showToast('error', data.message || 'Could not save');
                    return;
                }
                setCustomerUI(data.customer);
                showToast('success', data.message || (orderId ? 'Linked to bill' : 'Customer saved'));
            })
            .catch(() => {
                Swal.close();
                showToast('error', 'Network error');
            });
        }

        function ensureCustomerBeforeSend() {
            const phone = (document.getElementById('customerPhone').value || '').trim();
            if (!phone) return Promise.resolve(null);
            if (selectedCustomer?.id && String(selectedCustomer.phone) === phone) {
                return Promise.resolve(selectedCustomer.id);
            }
            showAddCustomerBtn(true);
            openAddCustomerPopup(phone);
            showToast('warning', 'Add customer name first');
            return Promise.reject('needs_name');
        }

        function billSignature(items) {
            return (items || billItems).map(i =>
                String(i.id) + ':' + Number(i.quantity) + ':' + (i.special_instructions || '')
            ).join('|');
        }

        function billItemsDirty() {
            return !!activeOrderId && billSignature() !== originalBillSignature;
        }

        function loadExistingOrder(orderId) {
            activeOrderId = orderId;
            return fetch('/waiter/order/' + orderId, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
                credentials: 'same-origin',
            })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    const order = data && data.order;
                    if (!ok || !order) {
                        showToast('warning', (data && data.message) || 'Could not load bill items');
                        billItems = [];
                        billTotal = 0;
                        originalBillSignature = '';
                        return;
                    }
                    billTotal = Number(order.total_amount || 0);
                    billItems = (order.items || []).filter(item => !item.is_void).map(item => {
                        const qty = Number(item.quantity) || 1;
                        const addonSum = (item.addons || []).reduce((s, a) => s + Number(a.price || 0), 0);
                        const unit = Number(item.total_price || 0) / qty || (Number(item.unit_price ?? item.price ?? 0) + addonSum);
                        return {
                            id: item.id,
                            name: item.product_name || item.name,
                            quantity: qty,
                            price: unit,
                            total: unit * qty,
                            special_instructions: item.special_instructions || '',
                        };
                    });
                    originalBillSignature = billSignature(billItems);
                    updateCartUI();
                    if (order.order_notes) {
                        document.getElementById('orderNotes').value = order.order_notes;
                    }
                    if (order.customer_id) {
                        setCustomerUI({
                            id: order.customer_id,
                            name: order.customer_name || 'Customer',
                            phone: order.customer_phone || '',
                        });
                    }
                    if (order.guest_count && !pendingGuestCount) {
                        pendingGuestCount = order.guest_count;
                    }
                    if (selectedTable && selectedTable.active_order) {
                        selectedTable.active_order.order_number = order.order_number || selectedTable.active_order.order_number;
                        selectedTable.active_order.total = billTotal;
                        if (order.guest_count) selectedTable.active_order.guest_count = order.guest_count;
                    }
                })
                .catch(() => {
                    billItems = [];
                    billTotal = 0;
                    originalBillSignature = '';
                    showToast('warning', 'Could not load existing order');
                });
        }

        function backToTables() {
            if (cart.length && selectedTable) {
                saveDraft();
            }
            selectedTable = null;
            activeOrderId = null;
            pendingGuestCount = null;
            cart = [];
            billItems = [];
            originalBillSignature = '';
            billTotal = 0;
            clearCustomer(true);
            const notes = document.getElementById('orderNotes');
            if (notes && !peekDraft()) notes.value = '';
            const billActions = document.getElementById('orderBillActions');
            if (billActions) billActions.classList.remove('show');
            closeOrderSheet();
            showScreen('screenTables');
            refreshTables();
            updateFloatingCart();
        }

        async function transferTable() {
            const orderId = activeOrderId || selectedTable?.active_order?.id;
            if (!orderId) {
                showToast('warning', 'No open order to transfer');
                return;
            }
            let freeTables = [];
            try {
                const r = await fetch('/waiter/tables', { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                const data = await r.json();
                freeTables = (data.tables || []).filter(t => t.status === 'available' && Number(t.id) !== Number(selectedTable?.id));
            } catch (_) {
                showToast('error', 'Could not load tables');
                return;
            }
            if (!freeTables.length) {
                showToast('warning', 'No free tables available');
                return;
            }
            const options = {};
            freeTables.forEach(t => {
                options[t.id] = (t.floor ? t.floor + ' · ' : '') + t.name;
            });
            const res = await Swal.fire({
                title: 'Transfer table',
                text: 'Move bill ' + (selectedTable?.active_order?.order_number || '') + ' to another table',
                input: 'select',
                inputOptions: options,
                inputPlaceholder: 'Select free table',
                showCancelButton: true,
                confirmButtonText: 'Transfer',
                confirmButtonColor: '#3b82f6',
                inputValidator: (v) => !v && 'Select a table',
            });
            if (!res.isConfirmed || !res.value) return;

            Swal.fire({ title: 'Transferring...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const r = await fetch('/waiter/order/' + orderId + '/transfer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ table_id: Number(res.value) }),
                });
                const data = await r.json().catch(() => ({}));
                Swal.close();
                if (!r.ok || !data.success) {
                    showToast('error', data.message || 'Transfer failed');
                    return;
                }
                clearDraft();
                cart = [];
                billItems = [];
                await Swal.fire({
                    icon: 'success',
                    title: 'Transferred',
                    text: data.message || ('Moved to ' + (data.table_name || 'new table')),
                    timer: 1600,
                    showConfirmButton: false,
                });
                backToTables();
            } catch (_) {
                Swal.close();
                showToast('error', 'Network error');
            }
        }

        async function cancelActiveOrder() {
            const orderId = activeOrderId || selectedTable?.active_order?.id;
            if (!orderId) {
                showToast('warning', 'No open order to cancel');
                return;
            }
            const res = await Swal.fire({
                title: 'Cancel order?',
                html: 'This cancels bill <strong>' + escapeHtml(selectedTable?.active_order?.order_number || ('#' + orderId)) + '</strong> and frees the table.',
                input: 'text',
                inputLabel: 'Reason (required)',
                inputPlaceholder: 'e.g. Guest left / wrong table',
                showCancelButton: true,
                confirmButtonText: 'Cancel order',
                confirmButtonColor: '#dc2626',
                inputValidator: (v) => (!v || String(v).trim().length < 3) && 'Enter a reason (min 3 characters)',
            });
            if (!res.isConfirmed) return;

            Swal.fire({ title: 'Cancelling...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const r = await fetch('/waiter/order/' + orderId + '/cancel', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ reason: String(res.value || '').trim() }),
                });
                const data = await r.json().catch(() => ({}));
                Swal.close();
                if (!r.ok || !data.success) {
                    showToast('error', data.message || 'Cancel failed');
                    return;
                }
                clearDraft();
                cart = [];
                billItems = [];
                await Swal.fire({
                    icon: 'success',
                    title: 'Order cancelled',
                    text: data.message || 'Bill cancelled',
                    timer: 1600,
                    showConfirmButton: false,
                });
                backToTables();
            } catch (_) {
                Swal.close();
                showToast('error', 'Network error');
            }
        }

        function startNewOrder() {
            const draft = peekDraft();
            const onMenu = document.getElementById('screenMenu')?.classList.contains('active');
            if ((onMenu && cart.length) || (draft && draft.cart?.length)) {
                Swal.fire({
                    title: 'Start new order?',
                    text: 'Draft / unsent items will be cleared.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'New Order',
                    cancelButtonText: 'Stay',
                    confirmButtonColor: '#f59e0b',
                }).then(result => {
                    if (!result.isConfirmed) return;
                    clearDraft();
                    cart = [];
                    backToTables();
                });
                return;
            }
            clearDraft();
            backToTables();
        }

        function loadMenu() {
            return fetch('/waiter/menu')
                .then(r => r.json())
                .then(data => {
                    categories = data.categories || [];
                    renderCategories();
                    renderProducts();
                });
        }

        function renderCategories() {
            let html = `<button class="cat-btn ${activeCategoryId==='all'?'active':''}" onclick="filterCategory('all', this)">All</button>`;
            categories.forEach(c => {
                html += `<button class="cat-btn" onclick="filterCategory(${c.id}, this)">${c.name}</button>`;
            });
            document.getElementById('categoryTabs').innerHTML = html;
        }

        function filterCategory(id, btn) {
            activeCategoryId = id;
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderProducts();
        }

        function renderProducts() {
            const list = [];
            categories.forEach(c => {
                if (activeCategoryId !== 'all' && c.id !== activeCategoryId) return;
                (c.products || []).forEach(p => list.push(p));
            });
            document.getElementById('productGrid').innerHTML = list.map(p => {
                const hasMods = (p.variants && p.variants.length) || (p.addons && p.addons.length) || p.has_variants || p.has_addons;
                return `
                <div class="product-card" onclick="addProductById(${p.id})" role="button" aria-label="Add ${escapeHtml(p.name)}">
                    <div class="pname">${escapeHtml(p.name)}${hasMods ? ' <i class="fas fa-sliders-h" style="font-size:0.7rem;opacity:.55;"></i>' : ''}</div>
                    <div class="pfoot">
                        <div class="pprice">${currency} ${Number(p.price).toFixed(0)}</div>
                        <span class="padd" aria-hidden="true">+</span>
                    </div>
                </div>`;
            }).join('') || '<div class="empty sm">No products</div>';
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
        }

        function addProductById(id) {
            let product = null;
            categories.forEach(c => (c.products || []).forEach(p => { if (p.id === id) product = p; }));
            if (!product) return;
            if ((product.variants && product.variants.length) || (product.addons && product.addons.length) || product.has_variants || product.has_addons) {
                showModifierSheet(product);
                return;
            }
            addToCart(product);
        }

        function lineUnitPrice(item) {
            return Number(item.price || 0) + (item.addons || []).reduce((s, a) => s + Number(a.price || 0), 0);
        }

        function showModifierSheet(product) {
            const variants = product.variants || [];
            const addons = product.addons || [];
            const base = Number(product.price || 0);

            let html = `<div style="text-align:left;">
                <div style="background:#f0fdf4;border-radius:12px;padding:12px;margin-bottom:14px;">
                    <div style="color:#64748b;font-size:0.8rem;">Base price</div>
                    <div style="font-weight:700;color:#15803d;font-size:1.1rem;">${currency} ${base.toFixed(2)}</div>
                </div>`;

            if (variants.length) {
                html += `<div style="margin-bottom:14px;">
                    <div style="font-weight:600;margin-bottom:8px;color:#57534e;">Portion / Size</div>
                    <div id="wVariantList" style="display:grid;gap:8px;">`;
                variants.forEach((v, i) => {
                    const adj = Number(v.price_adjustment || 0);
                    const adjLabel = adj === 0 ? 'Base' : (adj > 0 ? `+${currency} ${adj.toFixed(0)}` : `-${currency} ${Math.abs(adj).toFixed(0)}`);
                    html += `<label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid #e7e5e4;border-radius:12px;cursor:pointer;background:#fff;">
                        <input type="radio" name="wVariant" value="${v.id}" data-name="${escapeHtml(v.name)}" data-adj="${adj}" ${i === 0 ? 'checked' : ''} style="width:18px;height:18px;">
                        <span style="flex:1;font-weight:600;">${escapeHtml(v.name)}</span>
                        <span style="color:#f59e0b;font-weight:700;">${adjLabel}</span>
                    </label>`;
                });
                html += `</div></div>`;
            }

            if (addons.length) {
                html += `<div style="margin-bottom:14px;">
                    <div style="font-weight:600;margin-bottom:8px;color:#57534e;">Add-ons</div>
                    <div id="wAddonList" style="display:grid;gap:8px;">`;
                addons.forEach(a => {
                    html += `<label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid #e7e5e4;border-radius:12px;cursor:pointer;background:#fff;">
                        <input type="checkbox" class="w-addon" value="${a.id}" data-name="${escapeHtml(a.name)}" data-price="${Number(a.price || 0)}" style="width:18px;height:18px;">
                        <span style="flex:1;font-weight:600;">${escapeHtml(a.name)}</span>
                        <span style="color:#f59e0b;font-weight:700;">+${currency} ${Number(a.price || 0).toFixed(0)}</span>
                    </label>`;
                });
                html += `</div></div>`;
            }

            html += `<div style="margin-bottom:8px;">
                    <div style="font-weight:600;margin-bottom:6px;color:#57534e;">Note</div>
                    <input id="wItemNote" class="swal2-input" style="margin:0;width:100%;" placeholder="e.g. less spicy">
                </div>
                <div id="wModTotal" style="background:#fff7ed;border-radius:12px;padding:12px;display:flex;justify-content:space-between;font-weight:700;color:#c2410c;">
                    <span>Item total</span><span>${currency} ${base.toFixed(2)}</span>
                </div>
            </div>`;

            const recalc = () => {
                const checked = document.querySelector('input[name="wVariant"]:checked');
                const adj = checked ? Number(checked.dataset.adj || 0) : 0;
                let addonSum = 0;
                document.querySelectorAll('.w-addon:checked').forEach(el => { addonSum += Number(el.dataset.price || 0); });
                const el = document.getElementById('wModTotal');
                if (el) el.innerHTML = `<span>Item total</span><span>${currency} ${(base + adj + addonSum).toFixed(2)}</span>`;
            };

            Swal.fire({
                title: product.name,
                html,
                showCancelButton: true,
                confirmButtonText: 'Add to order',
                confirmButtonColor: '#f59e0b',
                focusConfirm: false,
                didOpen: () => {
                    document.querySelectorAll('input[name="wVariant"], .w-addon').forEach(el => {
                        el.addEventListener('change', recalc);
                    });
                    recalc();
                },
                preConfirm: () => {
                    if (variants.length && !document.querySelector('input[name="wVariant"]:checked')) {
                        Swal.showValidationMessage('Select a portion');
                        return false;
                    }
                    const checked = document.querySelector('input[name="wVariant"]:checked');
                    const selectedAddons = [];
                    document.querySelectorAll('.w-addon:checked').forEach(el => {
                        selectedAddons.push({
                            id: Number(el.value),
                            name: el.dataset.name,
                            price: Number(el.dataset.price || 0),
                        });
                    });
                    return {
                        variant: checked ? {
                            id: Number(checked.value),
                            name: checked.dataset.name,
                            price_adjustment: Number(checked.dataset.adj || 0),
                        } : null,
                        addons: selectedAddons,
                        note: (document.getElementById('wItemNote')?.value || '').trim(),
                    };
                }
            }).then(res => {
                if (!res.isConfirmed || !res.value) return;
                const { variant, addons: selectedAddons, note } = res.value;
                const unitPrice = base + (variant ? Number(variant.price_adjustment || 0) : 0);
                const displayName = variant ? `${product.name} (${variant.name})` : product.name;
                cart.push({
                    product_id: product.id,
                    name: displayName,
                    price: unitPrice,
                    quantity: 1,
                    variant_id: variant?.id || null,
                    variant_name: variant?.name || null,
                    addons: selectedAddons,
                    special_instructions: note || '',
                });
                updateCartUI();
                const extras = [];
                if (variant) extras.push(variant.name);
                if (selectedAddons.length) extras.push(selectedAddons.map(a => a.name).join(', '));
                showToast('success', displayName + (extras.length ? ' · ' + extras.join(' · ') : '') + ' added');
            });
        }

        function addToCart(product) {
            const existing = cart.find(i =>
                i.product_id === product.id &&
                !(i.addons || []).length &&
                !i.variant_id &&
                !(i.special_instructions || '')
            );
            if (existing) existing.quantity += 1;
            else cart.push({
                product_id: product.id,
                name: product.name,
                price: Number(product.price),
                quantity: 1,
                variant_id: null,
                variant_name: null,
                addons: [],
                special_instructions: ''
            });
            updateCartUI();
            showToast('success', product.name + ' added');
        }

        function updateQty(index, delta) {
            cart[index].quantity += delta;
            if (cart[index].quantity <= 0) cart.splice(index, 1);
            updateCartUI();
        }

        function removeItem(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function liveBillTotal() {
            return billItems.reduce((s, i) => s + Number(i.price || 0) * Number(i.quantity || 0), 0);
        }

        function updateBillQty(index, delta) {
            const item = billItems[index];
            if (!item) return;
            const next = Number(item.quantity) + delta;
            if (next <= 0) {
                removeBillItem(index);
                return;
            }
            item.quantity = next;
            item.total = Number(item.price || 0) * next;
            updateCartUI();
        }

        function removeBillItem(index) {
            const item = billItems[index];
            if (!item) return;
            Swal.fire({
                title: 'Remove ' + item.name + '?',
                text: 'Kitchen will be updated and this item will come off the bill.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Remove',
                confirmButtonColor: '#ef4444',
            }).then(res => {
                if (!res.isConfirmed) return;
                billItems.splice(index, 1);
                updateCartUI();
            });
        }

        function editBillItemNote(index) {
            const item = billItems[index];
            if (!item) return;
            Swal.fire({
                title: item.name,
                input: 'text',
                inputLabel: 'Kitchen note (prints on KOT)',
                inputValue: item.special_instructions || '',
                inputPlaceholder: 'e.g. less spicy, Take Away, no onion',
                showCancelButton: true,
                confirmButtonText: 'Save note',
                confirmButtonColor: '#f59e0b',
            }).then(res => {
                if (!res.isConfirmed) return;
                item.special_instructions = res.value || '';
                updateCartUI();
            });
        }

        function clearNewItems() {
            if (!cart.length) return;
            Swal.fire({
                title: 'Clear new items?',
                text: 'Unsent items will be removed from this draft.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Clear',
                confirmButtonColor: '#ef4444',
            }).then(res => {
                if (!res.isConfirmed) return;
                cart = [];
                clearDraft();
                updateCartUI();
                showToast('success', 'New items cleared');
            });
        }

        function editItemNote(index) {
            const item = cart[index];
            if (!item) return;
            Swal.fire({
                title: item.name,
                input: 'text',
                inputLabel: 'Kitchen note (prints on KOT)',
                inputValue: item.special_instructions || '',
                inputPlaceholder: 'e.g. less spicy, Take Away, no onion',
                showCancelButton: true,
                confirmButtonText: 'Save note',
                confirmButtonColor: '#f59e0b',
            }).then(res => {
                if (!res.isConfirmed) return;
                item.special_instructions = res.value || '';
                updateCartUI();
            });
        }

        window.editBillItemNote = editBillItemNote;
        window.editItemNote = editItemNote;

        function cartTotal() {
            return cart.reduce((s, i) => s + lineUnitPrice(i) * i.quantity, 0);
        }

        function updateCartUI() {
            const count = cart.reduce((s, i) => s + i.quantity, 0);
            const newTotal = cartTotal();
            const dirty = billItemsDirty();
            const billNow = billItems.length ? (dirty ? liveBillTotal() : billTotal) : 0;
            const cartCountEl = document.getElementById('cartCountLabel');
            const cartTotalEl = document.getElementById('cartTotalLabel');
            const billTotalEl = document.getElementById('billTotalLabel');
            if (cartCountEl) cartCountEl.textContent = count + ' new item' + (count === 1 ? '' : 's');
            if (cartTotalEl) cartTotalEl.textContent = currency + ' ' + (billNow + newTotal).toFixed(2);
            if (billTotalEl) {
                billTotalEl.textContent = billItems.length
                    ? (`On bill ${currency} ${billNow.toFixed(2)}` + (count ? ` + new ${currency} ${newTotal.toFixed(2)}` : '') + (dirty ? ' · edited' : ''))
                    : (count ? `New ${currency} ${newTotal.toFixed(2)}` : '');
            }

            const btnLabel = document.getElementById('sendOrderBtnLabel');
            const btnIcon = document.getElementById('sendOrderBtnIcon');
            if (btnLabel) {
                btnLabel.textContent = dirty
                    ? 'Update & Send KOT'
                    : (activeOrderId ? 'Send KOT (add items)' : 'Send KOT');
            }
            if (btnIcon) {
                btnIcon.className = dirty ? 'fas fa-sync-alt me-2' : 'fas fa-paper-plane me-2';
            }

            const billActions = document.getElementById('orderBillActions');
            if (billActions) {
                billActions.classList.toggle('show', !!activeOrderId);
            }

            let html = '';
            if (billItems.length) {
                html += `<div class="section-label">Order items — tap Note for kitchen</div>`;
                html += billItems.map((item, idx) => {
                    const note = (item.special_instructions || '').trim();
                    return `
                    <div class="cart-item">
                        <div style="flex:1;min-width:0;">
                            <div class="item-name">${escapeHtml(item.name)}</div>
                            <div class="item-meta">${currency} ${Number(item.price).toFixed(2)}</div>
                            ${note ? `<div class="item-note-chip"><i class="fas fa-sticky-note me-1"></i>${escapeHtml(note)}</div>` : ''}
                            <button type="button" class="btn-note" onclick="editBillItemNote(${idx})"><i class="fas fa-sticky-note me-1"></i>${note ? 'Edit note' : 'Add note'}</button>
                        </div>
                        <div class="qty-wrap">
                            <button class="qty-btn" onclick="updateBillQty(${idx}, -1)">-</button>
                            <strong style="min-width:20px;text-align:center;">${item.quantity}</strong>
                            <button class="qty-btn" onclick="updateBillQty(${idx}, 1)">+</button>
                            <button class="btn-remove" onclick="removeBillItem(${idx})" title="Remove"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>`;
                }).join('');
            }

            if (cart.length) {
                html += `<div class="section-label">New items — add note per item</div>`;
                html += cart.map((item, idx) => {
                    const addonNames = (item.addons || []).map(a => a.name).join(', ');
                    const unit = lineUnitPrice(item);
                    const note = (item.special_instructions || '').trim();
                    return `
                    <div class="cart-item">
                        <div style="flex:1;min-width:0;">
                            <div class="item-name">${escapeHtml(item.name)}</div>
                            <div class="item-meta">${currency} ${unit.toFixed(2)}${addonNames ? ' · +' + escapeHtml(addonNames) : ''}</div>
                            ${note ? `<div class="item-note-chip"><i class="fas fa-sticky-note me-1"></i>${escapeHtml(note)}</div>` : ''}
                            <button type="button" class="btn-note" onclick="editItemNote(${idx})"><i class="fas fa-sticky-note me-1"></i>${note ? 'Edit note' : 'Add note'}</button>
                        </div>
                        <div class="qty-wrap">
                            <button class="qty-btn" onclick="updateQty(${idx}, -1)">-</button>
                            <strong style="min-width:20px;text-align:center;">${item.quantity}</strong>
                            <button class="qty-btn" onclick="updateQty(${idx}, 1)">+</button>
                            <button class="btn-remove" onclick="removeItem(${idx})" title="Remove"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>`;
                }).join('');
            }

            if (!billItems.length && !cart.length) {
                html = '<div class="empty sm">No items yet — tap a product to add</div>';
            }

            const list = document.getElementById('orderList');
            if (list) list.innerHTML = html;
            saveDraft();
            updateFloatingCart();
        }

        function sendToKitchen() {
            if (!selectedTable) { showToast('warning', 'Select a table first'); return; }
            const dirty = billItemsDirty();
            if (!cart.length && !dirty) { showToast('warning', 'Add or change items first'); return; }
            if (!cart.length && !billItems.length && dirty) {
                Swal.fire({
                    title: 'Remove all items?',
                    text: 'This will cancel the open order and free the table.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Cancel order',
                    confirmButtonColor: '#ef4444',
                }).then(res => {
                    if (res.isConfirmed) submitWaiterOrder();
                });
                return;
            }
            submitWaiterOrder();
        }

        function submitWaiterOrder() {
            const dirty = billItemsDirty();
            ensureCustomerBeforeSend()
                .then(customerId => {
                    Swal.fire({ title: 'Sending...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    const payload = {
                        table_id: selectedTable.id,
                        items: cart,
                        existing_items: billItems.map(i => ({
                            id: i.id,
                            quantity: i.quantity,
                            special_instructions: i.special_instructions || null,
                        })),
                        notes: document.getElementById('orderNotes').value || null,
                        customer_id: customerId || selectedCustomer?.id || null,
                        customer_phone: (document.getElementById('customerPhone').value || '').trim() || null,
                        customer_name: selectedCustomer?.name || null,
                        guest_count: pendingGuestCount || selectedTable.active_order?.guest_count || null,
                    };

                    const useUpdate = !!activeOrderId;
                    if (useUpdate && !dirty && !cart.length) {
                        showToast('warning', 'Add or change items / notes first');
                        Swal.close();
                        return null;
                    }
                    const url = useUpdate ? ('/waiter/order/' + activeOrderId + '/update') : '/waiter/order';

                    return fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify(payload)
                    }).then(r => r.json());
                })
                .then(data => {
                    Swal.close();
                    if (!data) return;
                    if (!data.success) {
                        if (data.needs_name) {
                            showAddCustomerBtn(true);
                            openAddCustomerPopup();
                        }
                        showToast('error', data.message || 'Failed');
                        return;
                    }

                    // Do not print from waiter PWA — POS Print Bridge handles KOT.
                    if (data.cancelled) {
                        clearDraft();
                        cart = [];
                        billItems = [];
                        originalBillSignature = '';
                        Swal.fire({
                            icon: 'success',
                            title: 'Order cancelled',
                            text: data.message || 'Bill closed',
                            timer: 1400,
                            showConfirmButton: false,
                        }).then(() => backToTables());
                        return;
                    }

                    const modified = !!(data.modified || data.new_order === false);
                    const title = modified ? 'Order modified' : 'Order sent to kitchen';
                    const text = data.message
                        || (modified
                            ? 'Updated KOT sent to kitchen. POS notified.'
                            : 'KOT sent to kitchen. Returning to tables…');

                    clearDraft();
                    cart = [];
                    billItems = [];
                    originalBillSignature = '';
                    activeOrderId = null;
                    pendingGuestCount = null;

                    Swal.fire({
                        icon: 'success',
                        title,
                        text,
                        timer: 1600,
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#f59e0b',
                    }).then(() => backToTables());
                })
                .catch(err => {
                    Swal.close();
                    if (err === 'needs_name' || err === 'customer_error') return;
                    showToast('error', 'Network error');
                });
        }

        function openPrintPreview(url) {
            if (!url) return;
            printPreviewQueue.push(url);
            processPrintPreviewQueue();
        }

        function processPrintPreviewQueue() {
            if (printPreviewBusy || !printPreviewQueue.length) return;
            printPreviewBusy = true;
            const url = printPreviewQueue.shift();
            const modalEl = document.getElementById('printPreviewModal');
            const frame = document.getElementById('printPreviewFrame');
            frame.src = url + (url.includes('?') ? '&' : '?') + 'format=html';
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const onHidden = () => {
                modalEl.removeEventListener('hidden.bs.modal', onHidden);
                printPreviewBusy = false;
                frame.src = 'about:blank';
                processPrintPreviewQueue();
            };
            modalEl.addEventListener('hidden.bs.modal', onHidden);
            modal.show();
        }

        function printPreviewFrame() {
            const frame = document.getElementById('printPreviewFrame');
            if (frame?.contentWindow) { frame.contentWindow.focus(); frame.contentWindow.print(); }
        }

        function handlePrintJobs(jobs, options = {}) {
            // Waiter PWA never auto-prints KOT (no local Print Bridge).
            // Only run when explicitly forced (manual reprint).
            const force = !!options.force;
            if (!force) return;
            (jobs || []).forEach(job => {
                if (job.url) openPrintPreview(job.url);
            });
        }

        refreshTables();
        updateFloatingCart();

        window.addEventListener('pagehide', () => {
            if (selectedTable && cart.length) saveDraft();
        });

        const waiterParams = new URLSearchParams(location.search);
        const resumeDraft = waiterParams.get('resume') === '1';
        const editOrderId = parseInt(waiterParams.get('edit_order') || '', 10) || 0;
        if (resumeDraft || editOrderId) {
            history.replaceState({}, '', @json(route('waiter.index')));
            fetch('/waiter/tables')
                .then(r => r.json())
                .then(data => {
                    tables = data.tables || data || [];
                    renderTables();
                    refreshQrPending();
                    if (editOrderId) {
                        const table = tables.find(t => t.active_order && Number(t.active_order.id) === editOrderId);
                        if (!table) {
                            showToast('warning', 'That order is not an open table bill');
                            return;
                        }
                        selectTable(table.id);
                        return;
                    }
                    if (peekDraft()) resumeDraftOrder();
                })
                .catch(() => { if (resumeDraft && peekDraft()) resumeDraftOrder(); });
        }

        setInterval(() => {
            refreshQrPending();
        }, 4000);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshQrPending();
        });

        document.getElementById('customerPhone')?.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                checkCustomerPhone();
            }
        });

        document.getElementById('orderNotes')?.addEventListener('change', saveDraft);
        document.getElementById('orderNotes')?.addEventListener('blur', saveDraft);

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw-waiter.js', { scope: '/waiter/' }).catch(() => {});
        }
</script>
    @include('partials.waiter-pwa-install')
</body>
</html>
