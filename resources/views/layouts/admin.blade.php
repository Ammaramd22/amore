<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Restaurant POS') - QRPOS</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) ?: 1 }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) ?: 1 }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v={{ @filemtime(public_path('favicon-32.png')) ?: 1 }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.min.css">
    @stack('styles')
    <style>
        :root { --sidebar-width: 260px; --primary: #f59e0b; --primary-dark: #d97706; --sidebar-bg: #1c1917; --sidebar-hover: #292524; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .main-sidebar { width: var(--sidebar-width) !important; min-height: 100vh !important; max-height: 100vh; overflow-y: auto; position: fixed !important; top: 0; left: 0; bottom: 0; z-index: 1038; background: var(--sidebar-bg) !important; border-right: 1px solid rgba(255,255,255,0.05); }
        .main-header {
            margin-left: var(--sidebar-width) !important;
            position: sticky !important;
            top: 0;
            z-index: 1037;
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid #e7e5e4 !important;
            box-shadow: 0 1px 0 rgba(28, 25, 23, 0.03);
            min-height: 64px;
            display: flex;
            align-items: center;
            padding: 0 8px 0 4px;
        }
        .main-header .navbar {
            width: 100%;
            padding: 0.45rem 0.75rem;
            background: transparent !important;
            box-shadow: none !important;
        }
        .nav-toggle-btn {
            width: 42px; height: 42px;
            border-radius: 12px;
            border: 1px solid #e7e5e4;
            background: #fafaf9;
            color: #44403c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none !important;
            transition: 0.18s ease;
        }
        .nav-toggle-btn:hover { background: #fff7ed; border-color: #fdba74; color: #c2410c; }
        .nav-crumb {
            display: flex;
            flex-direction: column;
            margin-left: 12px;
            line-height: 1.2;
        }
        .nav-crumb .nav-crumb-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #a8a29e;
        }
        .nav-crumb .nav-crumb-title {
            font-size: 0.98rem;
            font-weight: 750;
            color: #1c1917;
            letter-spacing: -0.02em;
        }
        .nav-quick {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-right: 0.5rem;
        }
        .nav-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            border: 1px solid #e7e5e4;
            background: #fff;
            color: #44403c !important;
            text-decoration: none !important;
            font-size: 0.8rem;
            font-weight: 650;
            transition: 0.18s ease;
            white-space: nowrap;
        }
        .nav-chip i { color: #f59e0b; }
        .nav-chip:hover {
            background: #fff7ed;
            border-color: #fdba74;
            color: #1c1917 !important;
            transform: translateY(-1px);
        }
        .nav-chip.clock {
            flex-direction: column;
            align-items: flex-end;
            gap: 0;
            padding: 0.35rem 0.75rem;
            line-height: 1.15;
            cursor: default;
            pointer-events: none;
            min-width: 7.5rem;
        }
        .nav-chip.clock .nav-clock-time {
            font-size: 0.82rem;
            font-weight: 750;
            color: #1c1917;
            font-variant-numeric: tabular-nums;
        }
        .nav-chip.clock .nav-clock-date {
            font-size: 0.65rem;
            font-weight: 600;
            color: #78716c;
            letter-spacing: 0.01em;
        }
        .nav-chip.primary {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            border-color: transparent;
            color: #fff !important;
            box-shadow: 0 6px 16px rgba(234, 88, 12, 0.28);
        }
        .nav-chip.primary i { color: #fff; }
        .nav-chip.primary:hover { filter: brightness(1.05); color: #fff !important; }
        .nav-user-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.35rem 0.65rem 0.35rem 0.35rem;
            border-radius: 999px;
            border: 1px solid #e7e5e4;
            background: #fff;
            color: #1c1917 !important;
            text-decoration: none !important;
            font-weight: 650;
            font-size: 0.85rem;
        }
        .nav-user-avatar {
            width: 34px; height: 34px;
            border-radius: 999px;
            display: grid; place-items: center;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #fff;
            font-size: 0.85rem;
        }
        .nav-user-btn .caret { color: #a8a29e; font-size: 0.7rem; }
        .nav-bell-wrap { position: relative; margin-right: 0.45rem; }
        .nav-bell-btn {
            width: 42px; height: 42px;
            border-radius: 12px;
            border: 1px solid #e7e5e4;
            background: #fff;
            color: #44403c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none !important;
            position: relative;
            cursor: pointer;
        }
        .nav-bell-btn:hover, .nav-bell-btn.is-open { background: #fff7ed; border-color: #fdba74; color: #c2410c; }
        .nav-bell-badge {
            position: absolute;
            top: -4px; right: -4px;
            min-width: 18px; height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #ea580c;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 800;
            display: none;
            align-items: center;
            justify-content: center;
            line-height: 1;
            border: 2px solid #fff;
        }
        .nav-bell-badge.show { display: inline-flex; }
        .nav-bell-panel {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: min(360px, 92vw);
            max-height: 420px;
            overflow: hidden;
            flex-direction: column;
            padding: 0;
            background: #fff;
            border: 1px solid #e7e5e4;
            border-radius: 14px;
            box-shadow: 0 16px 40px rgba(28, 25, 23, 0.14);
            z-index: 1080;
        }
        .nav-bell-panel.is-open { display: flex; }
        .nav-bell-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid #e7e5e4;
            flex-shrink: 0;
        }
        .nav-bell-head strong { font-size: 0.9rem; white-space: nowrap; }
        .nav-bell-head-actions { display: flex; align-items: center; gap: 0.45rem; flex-shrink: 0; }
        .nav-bell-close {
            width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e7e5e4;
            background: #fafaf9; color: #44403c; font-size: 1.25rem; line-height: 1;
            display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
            flex-shrink: 0;
        }
        .nav-bell-close:hover { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
        .nav-bell-list { overflow-y: auto; max-height: 300px; flex: 1; }
        .nav-bell-item {
            display: block;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f5f5f4;
            text-decoration: none !important;
            color: #1c1917;
            cursor: pointer;
            position: relative;
        }
        .nav-bell-item:hover { background: #fff7ed; }
        .nav-bell-item.unread { background: #fffbeb; }
        .nav-bell-item .nb-title { font-weight: 700; font-size: 0.82rem; margin-bottom: 2px; padding-right: 1.5rem; }
        .nav-bell-item .nb-preview { font-size: 0.75rem; color: #78716c; line-height: 1.35; }
        .nav-bell-item .nb-time { font-size: 0.68rem; color: #a8a29e; margin-top: 4px; }
        .nav-bell-item .nb-dismiss {
            position: absolute; top: 8px; right: 8px;
            width: 22px; height: 22px; border: 0; border-radius: 6px;
            background: transparent; color: #a8a29e; font-size: 0.95rem; line-height: 1; cursor: pointer;
        }
        .nav-bell-item .nb-dismiss:hover { background: #fee2e2; color: #b91c1c; }
        .nav-bell-item.nb-expiry {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding-right: 1rem;
        }
        .nav-bell-item.nb-expiry .nb-body { flex: 1; min-width: 0; }
        .nav-bell-item.nb-expiry .nb-title { padding-right: 0; }
        .nav-bell-item .nb-photo {
            width: 42px; height: 42px; border-radius: 10px;
            object-fit: cover; flex-shrink: 0; background: #f5f5f4;
            border: 1px solid #e7e5e4;
        }
        .nav-bell-item.nb-urgent {
            background: #fef2f2;
            border-left: 3px solid #dc2626;
            animation: navBellExpiryBlink 1s ease-in-out infinite;
        }
        .nav-bell-item.nb-urgent .nb-time {
            color: #dc2626; font-weight: 800;
        }
        @keyframes navBellExpiryBlink {
            0%, 100% { background: #fef2f2; }
            50% { background: #fee2e2; }
        }
        @media (prefers-reduced-motion: reduce) {
            .nav-bell-item.nb-urgent { animation: none; }
        }
        .nav-bell-empty { padding: 1.5rem 1rem; text-align: center; color: #a8a29e; font-size: 0.85rem; }
        .nav-bell-foot {
            border-top: 1px solid #e7e5e4; padding: 0.65rem 1rem; text-align: center; flex-shrink: 0;
        }
        .nav-bell-foot a { font-size: 0.78rem; font-weight: 700; color: #d97706; text-decoration: none; }
        .nav-bell-foot a:hover { color: #c2410c; }
        .dropdown-menu {
            border: 1px solid #e7e5e4;
            border-radius: 14px;
            box-shadow: 0 16px 40px rgba(28, 25, 23, 0.12);
            padding: 0.45rem;
            min-width: 200px;
        }
        .dropdown-item {
            border-radius: 10px;
            padding: 0.65rem 0.85rem;
            font-weight: 550;
            font-size: 0.88rem;
        }
        .dropdown-item:hover { background: #fff7ed; color: #c2410c; }
        .dropdown-item.text-danger:hover { background: #fef2f2; color: #b91c1c; }
        .content-wrapper { margin-left: var(--sidebar-width) !important; min-height: calc(100vh - 57px) !important; background: #fafaf9; padding-top: 0; overflow-x: auto; }
        /* Contain long names / numbers / grids so cards & forms do not spill horizontally */
        .content-wrapper .app-content,
        .content-wrapper .container-fluid { max-width: 100%; min-width: 0; }
        .content-wrapper .row > [class*="col-"] { min-width: 0; }
        .content-wrapper .card,
        .content-wrapper .card-body { min-width: 0; }
        .content-wrapper .form-control,
        .content-wrapper .form-select { max-width: 100%; min-width: 0; }
        .content-wrapper .input-group > .form-control,
        .content-wrapper .input-group > .form-select { min-width: 0; }
        .content-wrapper .table-responsive {
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .content-wrapper .table td,
        .content-wrapper .table th { overflow-wrap: anywhere; word-break: break-word; }
        .content-wrapper .table .text-nowrap { overflow-wrap: normal; word-break: normal; }
        .content-wrapper .badge,
        .content-wrapper .badge-soft { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }
        .content-wrapper .page-toolbar .toolbar-title { min-width: 0; overflow-wrap: anywhere; }
        .content-wrapper .modal-body { overflow-x: auto; min-width: 0; }
        .content-wrapper .select2-container { max-width: 100% !important; }
        .main-footer {
            margin-left: var(--sidebar-width) !important;
            background: #fafaf9 !important;
            border-top: 1px solid #e7e5e4 !important;
            color: #78716c !important;
            font-size: 0.8125rem;
            padding: 0.65rem 1rem !important;
            text-align: center;
        }
        .main-footer .footer-inner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.35rem 1rem;
        }
        .main-footer .footer-brand {
            color: #44403c;
            font-weight: 700;
        }
        .main-footer .footer-meta {
            color: #a8a29e;
            white-space: nowrap;
        }
        .brand-link img.brand-logo {
            width: 36px; height: 36px; border-radius: 10px; object-fit: cover;
            box-shadow: 0 0 0 2px rgba(245,158,11,0.35);
        }
        .brand-link { background: transparent !important; border-bottom: 1px solid rgba(255,255,255,0.08) !important; color: #fff !important; padding: 20px 24px !important; display: flex; align-items: center; gap: 12px; text-decoration: none !important; font-size: 1.25rem; font-weight: 700; letter-spacing: -0.5px; }
        .brand-link i { background: linear-gradient(135deg, #f59e0b, #ea580c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 1.5rem; }
        .sidebar { padding: 16px 12px; }
        .nav-sidebar { gap: 4px; }
        .nav-sidebar .nav-item { margin: 0; }
        .nav-sidebar .nav-item > .nav-link { color: #94a3b8 !important; border-radius: 10px; margin-bottom: 2px; padding: 12px 16px; font-size: 0.9rem; font-weight: 500; transition: all 0.2s ease; display: flex; align-items: center; gap: 12px; position: relative; overflow: hidden; }
        .nav-sidebar .nav-item > .nav-link::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 0; background: var(--primary); border-radius: 0 3px 3px 0; transition: height 0.2s ease; }
        .nav-sidebar .nav-item > .nav-link:hover { background: var(--sidebar-hover) !important; color: #fff !important; }
        .nav-sidebar .nav-item > .nav-link:hover::before { height: 20px; }
        .nav-sidebar .nav-item > .nav-link.active { background: rgba(245,158,11,0.15) !important; color: #fff !important; }
        .nav-sidebar .nav-item > .nav-link.active::before { height: 24px; }
        .nav-sidebar .nav-icon { color: #64748b !important; font-size: 1.1rem; width: 24px; text-align: center; transition: color 0.2s ease; }
        .nav-sidebar .nav-link:hover .nav-icon { color: #fff !important; }
        .nav-sidebar .nav-link.active .nav-icon { color: var(--primary) !important; }
        .nav-sidebar .nav-link p { margin: 0; letter-spacing: 0.3px; }
        .sidebar-divider { height: 1px; background: rgba(255,255,255,0.06); margin: 20px 16px 12px; }
        .sidebar-section-title {
            list-style: none;
            color: #f59e0b;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 18px 16px 6px;
            margin: 4px 0 0;
            opacity: 0.95;
            pointer-events: none;
            border-top: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .bil-nav-badge {
            width: 18px; height: 18px; border-radius: 5px;
            object-fit: cover; flex-shrink: 0;
            box-shadow: 0 0 0 1px rgba(245,158,11,.35);
        }
        .nav-sidebar .nav-icon.bil-nav-icon {
            width: 22px !important; height: 22px; border-radius: 6px;
            object-fit: cover; padding: 0 !important;
            box-shadow: 0 0 0 1px rgba(245,158,11,.3);
        }
        .nav-sidebar > .sidebar-section-title:first-child {
            border-top: 0;
            padding-top: 8px;
        }
        .btn-primary { background: linear-gradient(135deg, #f59e0b, #ea580c); border: none; padding: 10px 20px; border-radius: 8px; font-weight: 500; transition: all 0.2s; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245,158,11,0.4); }
        .btn-secondary { background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b; padding: 10px 20px; border-radius: 8px; font-weight: 500; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 8px; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); border: none; border-radius: 8px; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); border: none; border-radius: 8px; color: #fff; }
        .btn-info { background: linear-gradient(135deg, #06b6d4, #0891b2); border: none; border-radius: 8px; color: #fff; }
        .card { border: none; border-radius: 16px; box-shadow: 0 1px 2px rgba(28,25,23,0.04), 0 8px 24px rgba(28,25,23,0.04); transition: all 0.2s; background: #fff; }
        .card:hover { box-shadow: 0 4px 20px rgba(28,25,23,0.08); }
        .card-header { background: transparent; border-bottom: 1px solid #f5f5f4; padding: 18px 22px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; }
        .card-header h3 { font-size: 1rem; font-weight: 700; color: #1c1917; margin: 0; letter-spacing: -0.01em; }
        .card-body { padding: 22px; }
        .card-footer { background: transparent; border-top: 1px solid #f5f5f4; padding: 14px 22px; }
        .form-control, .form-select { border: 1px solid #e2e8f0; border-radius: 10px; padding: 11px 14px; font-size: 0.9rem; color: #292524; background-color: #fff; transition: all 0.2s; }
        .form-control::placeholder { color: #cbd5e1; }
        .form-control:hover, .form-select:hover { border-color: #cbd5e1; }
        .form-control:focus, .form-select:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.12); background-color: #fff; }

        /* Global form polish — applies to all create/edit blades */
        .card-body form label:not(.form-check-label),
        .card-body form .form-label {
            font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 6px;
            letter-spacing: 0.2px; display: inline-block;
        }
        .card-body form textarea.form-control { min-height: 90px; line-height: 1.5; }
        .card-body form .mb-3 { margin-bottom: 18px !important; }
        /* File inputs */
        .form-control[type="file"] { padding: 8px 14px; }
        .form-control[type="file"]::file-selector-button {
            background: #fff7ed; color: #d97706; border: none; border-radius: 8px;
            padding: 6px 14px; margin-right: 12px; font-weight: 600; font-size: 0.82rem; cursor: pointer;
            transition: background 0.2s;
        }
        .form-control[type="file"]::file-selector-button:hover { background: #e0e7ff; }
        /* Color input */
        .form-control[type="color"] { padding: 4px; height: 44px; width: 100%; cursor: pointer; }
        /* Checkboxes & switches */
        .card-body .form-check-input { width: 1.15em; height: 1.15em; margin-top: 0.15em; cursor: pointer; border-color: #cbd5e1; }
        .card-body .form-check-input:checked { background-color: #f59e0b; border-color: #f59e0b; }
        .card-body .form-check-label { font-size: 0.875rem; color: #475569; cursor: pointer; padding-left: 2px; }
        .card-body .form-check-inline { margin-right: 20px; }
        /* Form action buttons spacing */
        .card-body form .btn { padding: 10px 22px; font-weight: 600; }
        .card-body form .btn + .btn { margin-left: 8px; }
        /* Select2 — match form-control look */
        .select2-container .select2-selection--single {
            height: 44px !important; border: 1px solid #e2e8f0 !important; border-radius: 10px !important;
            display: flex; align-items: center; padding: 0 6px;
        }
        .select2-container--bootstrap-5 .select2-selection { box-shadow: none !important; }
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important; color: #292524 !important; padding-left: 8px;
        }
        .select2-container .select2-selection--single .select2-selection__arrow { height: 42px !important; }
        .select2-container--open .select2-selection { border-color: #f59e0b !important; box-shadow: 0 0 0 3px rgba(245,158,11,0.12) !important; }
        .select2-dropdown { border-color: #e2e8f0 !important; border-radius: 10px !important; box-shadow: 0 10px 40px rgba(0,0,0,0.12); overflow: hidden; }
        .select2-container--bootstrap-5 .select2-results__option--highlighted { background: #f59e0b !important; }
        /* Input groups */
        .input-group-text { border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; color: #64748b; font-weight: 600; }
        .table { font-size: 0.9rem; margin-bottom: 0; }
        .table thead th { background: #fafaf9; border-bottom: 1px solid #e7e5e4; font-weight: 700; color: #78716c; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.06em; padding: 14px 16px; white-space: nowrap; }
        .table tbody td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f5f5f4; color: #292524; }
        .table tbody tr:hover { background: #fff7ed; }
        .table-striped > tbody > tr:nth-of-type(odd) > * { background: transparent; }
        .page-toolbar {
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .page-toolbar .toolbar-title {
            font-size: 0.95rem; font-weight: 750; color: #1c1917; margin: 0;
        }
        .page-toolbar .toolbar-actions { display: flex; flex-wrap: wrap; gap: 0.45rem; align-items: center; }
        .index-filter-form {
            background: #fff; border: 1px solid #e7e5e4; border-radius: 16px;
            padding: 1rem 1.15rem; margin-bottom: 1rem;
        }
        .index-filter-form .form-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #a8a29e; }
        .btn-icon {
            width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 10px; border: 1px solid #e7e5e4; background: #fff; color: #57534e;
        }
        .btn-icon:hover { background: #fff7ed; border-color: #fdba74; color: #c2410c; }
        .btn-icon.danger:hover { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .btn-icon.success:hover { background: #ecfdf5; border-color: #a7f3d0; color: #059669; }
        .row-actions { position: static; display: inline-block; }
        .row-actions .dropdown-toggle::after { display: none; }
        .row-actions .dropdown-menu {
            z-index: 2000;
            min-width: 168px;
            padding: 0.4rem;
            border: 1px solid #e7e5e4;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(28, 25, 23, 0.14);
        }
        .row-actions .dropdown-item {
            border-radius: 8px;
            padding: 0.55rem 0.75rem;
            font-size: 0.86rem;
            font-weight: 600;
            color: #44403c;
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }
        .row-actions .dropdown-item i { width: 1rem; text-align: center; color: #a8a29e; }
        .row-actions .dropdown-item:hover { background: #fff7ed; color: #c2410c; }
        .row-actions .dropdown-item:hover i { color: #c2410c; }
        .row-actions .dropdown-item.text-danger { color: #b91c1c; }
        .row-actions .dropdown-item.text-danger i { color: #ef4444; }
        .row-actions .dropdown-item.text-danger:hover { background: #fef2f2; color: #991b1b; }
        .row-actions .dropdown-item.text-success { color: #047857; }
        .row-actions .dropdown-item.text-success i { color: #10b981; }
        .row-actions form { margin: 0; }
        .row-actions form .dropdown-item {
            width: 100%; border: 0; background: transparent; text-align: left; cursor: pointer;
        }
        .bulk-toolbar {
            padding: 0.65rem 1rem;
            background: #fff7ed;
            border-bottom: 1px solid #fed7aa;
        }
        .bulk-toolbar-inner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .bulk-toolbar-count { color: #9a3412; font-size: 0.9rem; }
        .bulk-toolbar-actions { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; }
        .bulk-table .col-check { width: 2.25rem; text-align: center; vertical-align: middle; }
        .bulk-table .col-num { width: 2.75rem; color: #78716c; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .bulk-table .bulk-row-check,
        .bulk-table .bulk-check-all {
            width: 1.05rem;
            height: 1.05rem;
            cursor: pointer;
            accent-color: #ea580c;
        }
        .bulk-table tbody tr.bulk-row-selected { background: rgba(251, 146, 60, 0.08) !important; }
        .card-body.p-0 > .table-responsive { overflow-x: auto; overflow-y: visible; }
        .empty-state {
            text-align: center; padding: 3rem 1.5rem; color: #a8a29e;
        }
        .empty-state i { font-size: 2rem; color: #d6d3d1; margin-bottom: 0.75rem; display: block; }
        .form-actions {
            display: flex; flex-wrap: wrap; gap: 0.5rem; padding-top: 0.5rem; margin-top: 0.5rem;
            border-top: 1px solid #f5f5f4;
        }
        .modal-header.respos {
            background: linear-gradient(135deg, #1c1917, #292524);
            color: #fff7ed; border-bottom: 2px solid #f59e0b; border-radius: 16px 16px 0 0;
        }
        .modal-header.respos .btn-close { filter: invert(1); }
        .badge-soft {
            background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa;
            padding: 0.35rem 0.7rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;
        }
        .badge-soft.success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .badge-soft.danger { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .badge-soft.muted { background: #f5f5f4; color: #78716c; border-color: #e7e5e4; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.3px; }
        .bg-success { background: linear-gradient(135deg, #10b981, #059669) !important; }
        .bg-danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; }
        .bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
        .bg-info { background: linear-gradient(135deg, #06b6d4, #0891b2) !important; }
        .bg-primary { background: linear-gradient(135deg, #f59e0b, #ea580c) !important; }
        .bg-secondary { background: #f1f5f9 !important; color: #64748b !important; }
        .stat-card { background: #fff; border-radius: 16px; padding: 24px; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #f59e0b, #ea580c); }
        .stat-card.success::before { background: linear-gradient(90deg, #10b981, #059669); }
        .stat-card.warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .stat-card.danger::before { background: linear-gradient(90deg, #ef4444, #dc2626); }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 16px; }
        .stat-card .stat-value { font-size: 1.75rem; font-weight: 700; color: #292524; margin-bottom: 4px; }
        .stat-card .stat-label { font-size: 0.875rem; color: #64748b; font-weight: 500; }

        /* Reports — shared design */
        .rpt-shell { display: flex; flex-direction: column; gap: 1rem; }
        .rpt-toolbar {
            display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;
            gap: 1rem; padding: 1.1rem 1.25rem; background: #fff; border: 1px solid #e7e5e4;
            border-radius: 18px;
        }
        .rpt-toolbar h3 {
            margin: 0; font-size: 1.05rem; font-weight: 800; color: #1c1917;
            display: flex; align-items: center; gap: .5rem;
        }
        .rpt-toolbar h3 i { color: #f59e0b; }
        .rpt-toolbar .rpt-sub { margin: .2rem 0 0; font-size: .8rem; color: #a8a29e; }
        .rpt-filters { display: flex; flex-wrap: wrap; gap: .55rem; align-items: end; }
        .rpt-filters label {
            display: block; font-size: .68rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: #78716c; margin-bottom: .25rem;
        }
        .rpt-filters .form-control, .rpt-filters .form-select {
            border-radius: 10px; border-color: #e7e5e4; min-width: 140px;
        }
        .rpt-kpis {
            display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;
        }
        .rpt-kpis.is-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        @media (max-width: 1100px) { .rpt-kpis.is-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 900px) { .rpt-kpis, .rpt-kpis.is-4 { grid-template-columns: 1fr; } }
        .rpt-kpi {
            border-radius: 18px; padding: 1.35rem 1.25rem; color: #fff;
            box-shadow: 0 10px 28px rgba(28,25,23,.1); position: relative; overflow: hidden;
            min-height: 112px; display: flex; flex-direction: column; justify-content: center;
        }
        .rpt-kpi::after {
            content: ''; position: absolute; right: -20px; top: -20px;
            width: 110px; height: 110px; border-radius: 50%;
            background: rgba(255,255,255,.12);
        }
        .rpt-kpi .rpt-kpi-value {
            font-size: clamp(1.45rem, 2.4vw, 1.85rem); font-weight: 800; line-height: 1.15;
            position: relative; z-index: 1; word-break: break-word;
        }
        .rpt-kpi .rpt-kpi-label {
            margin-top: .4rem; font-size: .88rem; font-weight: 600; opacity: .92;
            position: relative; z-index: 1;
        }
        .rpt-kpi .rpt-kpi-icon {
            position: absolute; right: 1.1rem; bottom: 1rem; font-size: 1.6rem;
            opacity: .35; z-index: 1;
        }
        .rpt-kpi.is-green { background: linear-gradient(135deg, #10b981, #059669); }
        .rpt-kpi.is-blue { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
        .rpt-kpi.is-amber { background: linear-gradient(135deg, #f59e0b, #ea580c); }
        .rpt-kpi.is-stone { background: linear-gradient(135deg, #44403c, #1c1917); }
        .rpt-kpi.is-violet { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .rpt-panel {
            background: #fff; border: 1px solid #e7e5e4; border-radius: 18px; overflow: hidden;
        }
        .rpt-panel-head {
            display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            padding: 1rem 1.25rem; border-bottom: 1px solid #f5f5f4;
        }
        .rpt-panel-head h4 { margin: 0; font-size: .95rem; font-weight: 800; color: #1c1917; }
        .rpt-table { margin: 0; }
        .rpt-table thead th {
            background: #fafaf9; color: #78716c; font-size: .72rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e7e5e4;
            padding: .85rem 1.1rem; white-space: nowrap;
        }
        .rpt-table tbody td {
            padding: .95rem 1.1rem; border-color: #f5f5f4; color: #292524; vertical-align: middle;
        }
        .rpt-table tbody tr:nth-child(even) { background: #fcfcfb; }
        .rpt-table tbody tr:hover { background: #fffbeb; }
        .rpt-empty { text-align: center; color: #a8a29e; padding: 2.5rem 1rem; }
        .rpt-footer {
            text-align: center; font-size: .75rem; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; color: #a8a29e; padding: .35rem 0 .75rem;
        }
        .rpt-footer span { color: #f59e0b; }
        .report-hub { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .report-card {
            text-align: left; padding: 1.35rem 1.2rem; height: 100%;
            border: 1px solid #e7e5e4; border-radius: 18px; background: #fff;
            transition: 0.18s ease; text-decoration: none !important; color: inherit; display: block;
            position: relative; overflow: hidden;
        }
        .report-card::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
            background: linear-gradient(180deg, #f59e0b, #ea580c);
        }
        .report-card:hover { border-color: #fdba74; background: #fff7ed; transform: translateY(-3px); box-shadow: 0 14px 28px rgba(234,88,12,0.12); }
        .report-card i {
            width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center;
            background: #fff7ed; color: #f59e0b; font-size: 1.15rem; margin-bottom: .85rem;
        }
        .report-card h5 { font-size: 1rem; font-weight: 800; color: #1c1917; margin: 0 0 0.35rem; }
        .report-card p { font-size: 0.82rem; color: #a8a29e; margin: 0; line-height: 1.4; }

        .rpt-charts {
            display: grid; grid-template-columns: 1.4fr 1fr; gap: 1rem;
        }
        @media (max-width: 992px) { .rpt-charts { grid-template-columns: 1fr; } }
        .rpt-chart-box {
            background: #fff; border: 1px solid #e7e5e4; border-radius: 18px;
            padding: 1rem 1.15rem 1.15rem; min-height: 280px;
        }
        .rpt-chart-box h4 {
            margin: 0 0 .85rem; font-size: .92rem; font-weight: 800; color: #1c1917;
            display: flex; align-items: center; gap: .45rem;
        }
        .rpt-chart-box h4 i { color: #f59e0b; }
        .rpt-chart-wrap { position: relative; height: 220px; }
        .rpt-chart-wrap.is-tall { height: 280px; }

        .rpt-ai {
            background: linear-gradient(135deg, #1c1917 0%, #292524 55%, #431407 100%);
            border-radius: 18px; padding: 1.15rem 1.25rem; color: #fff7ed;
            border: 1px solid rgba(245,158,11,.35);
        }
        .rpt-ai-head {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: .5rem; margin-bottom: .9rem;
        }
        .rpt-ai-badge {
            display: inline-flex; align-items: center; gap: .45rem;
            background: rgba(245,158,11,.2); color: #fbbf24; border: 1px solid rgba(251,191,36,.35);
            border-radius: 999px; padding: .35rem .75rem; font-size: .78rem; font-weight: 800;
            letter-spacing: .04em; text-transform: uppercase;
        }
        .rpt-ai-sub { font-size: .78rem; color: #a8a29e; }
        .rpt-ai-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: .75rem;
        }
        .rpt-ai-card {
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px; padding: .9rem 1rem; border-left: 3px solid #f59e0b;
        }
        .rpt-ai-card.tone-success { border-left-color: #34d399; }
        .rpt-ai-card.tone-warn { border-left-color: #fbbf24; }
        .rpt-ai-card.tone-tip { border-left-color: #38bdf8; }
        .rpt-ai-card.tone-info { border-left-color: #a78bfa; }
        .rpt-ai-card h5 { margin: 0 0 .35rem; font-size: .9rem; font-weight: 800; color: #fef3c7; }
        .rpt-ai-card p { margin: 0; font-size: .82rem; line-height: 1.45; color: #d6d3d1; }
        .content-header { background: transparent; border-bottom: none; padding: 8px 0 4px; margin-bottom: 12px; }
        .content-header h1 { font-size: 1.35rem; font-weight: 800; color: #1c1917; letter-spacing: -0.02em; }
        .breadcrumb { font-size: 0.875rem; }
        .breadcrumb-item a { color: #f59e0b; text-decoration: none; }
        .breadcrumb-item.active { color: #64748b; }
        .alert { border: none; border-radius: 12px; padding: 16px 20px; }
        .modal-content { border: none; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { border-bottom: 1px solid #f1f5f9; padding: 20px 24px; }
        .modal-body { padding: 24px; }
        .modal-footer { border-top: 1px solid #f1f5f9; padding: 20px 24px; }
        .pagination .page-link { border: none; padding: 10px 16px; color: #64748b; border-radius: 8px; margin: 0 2px; }
        .pagination .page-item.active .page-link { background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; }
        .page-item.active .page-link { background: linear-gradient(135deg, #f59e0b, #ea580c) !important; border-color: #f59e0b; }
        /* Safety: unconstrained pagination SVGs (Tailwind default) must never fill the page */
        .pagination svg,
        nav[role="navigation"] svg,
        .card-footer svg {
            width: 1.15rem !important;
            height: 1.15rem !important;
            max-width: 1.25rem !important;
            max-height: 1.25rem !important;
            flex-shrink: 0;
            display: inline-block;
            vertical-align: middle;
        }
        .table img,
        .bulk-table img {
            max-width: 48px;
            max-height: 48px;
            width: 40px;
            height: 40px;
            object-fit: cover;
        }
        .pos-btn { min-height: 80px; font-size: 1.1rem; }
        .touch-btn { min-height: 60px; font-size: 1.2rem; }
        .card-table { cursor: pointer; transition: all 0.2s; }
        .card-table:hover { transform: scale(1.02); }
        .table-available { border-left: 5px solid #28a745; }
        .table-occupied { border-left: 5px solid #dc3545; }
        .table-reserved { border-left: 5px solid #ffc107; }
        .table-cleaning { border-left: 5px solid #6c757d; }
        .kot-card { border-left: 4px solid #e74c3c; }
        .kot-bar { border-left: 4px solid #3498db; }
        .kitchen-column { min-height: 80vh; max-height: 80vh; overflow-y: auto; }
        .product-card { cursor: pointer; transition: transform 0.1s; }
        .product-card:hover { transform: scale(1.02); }
        .product-card:active { transform: scale(0.98); }
        .on-screen-keypad .btn { min-height: 55px; font-size: 1.3rem; }
        .order-summary { position: sticky; top: 0; }
        .qr-menu-item img { height: 150px; object-fit: cover; }

        /* Mobile responsive admin layout */
        @media (max-width: 991.98px) {
            .main-sidebar { transform: translateX(-100%); transition: transform 0.25s ease; width: 280px !important; height: 100vh !important; max-height: 100vh !important; overflow-y: auto !important; -webkit-overflow-scrolling: touch; }
            .main-sidebar.open { transform: translateX(0); }
            .main-header { margin-left: 0 !important; }
            .content-wrapper { margin-left: 0 !important; }
            .main-footer { margin-left: 0 !important; }
            .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); z-index: 1037; }
            .sidebar-backdrop.open { display: block; }
            .content-header { padding: 16px 0; margin-bottom: 16px; }
            .content-header h1 { font-size: 1.25rem; }
            .content { padding: 0 12px; }
            .container-fluid { padding: 0 12px; }
            .card-body { padding: 16px; }
            .table { font-size: 0.85rem; }
            .table thead th { padding: 10px 12px; }
            .table tbody td { padding: 10px 12px; }
            .btn { padding: 8px 14px; }
            .stat-card { padding: 18px; }
            .stat-card .stat-value { font-size: 1.5rem; }
            .form-control, .form-select { padding: 10px 14px; }
        }

        @media (max-width: 575.98px) {
            .content-header { padding: 14px 0; }
            .content-header h1 { font-size: 1.1rem; }
            .card-header { padding: 14px 16px; }
            .card-body { padding: 12px; }
            .table-responsive-stack { display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .breadcrumb { display: none; }
            .main-header .navbar-nav .nav-link { padding: 8px 10px; font-size: 0.85rem; }
        }
    </style>
    @include('partials.business-clock')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>
    <nav class="main-header navbar navbar-expand">
        <ul class="navbar-nav align-items-center">
            <li class="nav-item">
                <a class="nav-toggle-btn" href="#" role="button" onclick="toggleSidebar(); return false;" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block">
                <div class="nav-crumb">
                    <span class="nav-crumb-label">QRPOS</span>
                    <span class="nav-crumb-title">@yield('page_title', 'Dashboard')</span>
                </div>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item">
                <div class="nav-quick">
                    <div class="nav-chip clock d-none d-md-flex" id="adminBusinessClock" title="{{ \App\Models\Setting::timezone() }}">
                        <span class="nav-clock-time" data-clock-time>--:--</span>
                        <span class="nav-clock-date" data-clock-date>—</span>
                    </div>
                    @can('pos.access')
                    <a class="nav-chip primary d-none d-sm-inline-flex" href="{{ route('pos.index') }}">
                        <i class="fas fa-cash-register"></i> POS
                    </a>
                    @endcan
                    @if(\App\Models\Setting::get('kitchen_display_enabled', true) && \App\Models\Setting::get('kot_confirmation_enabled', true))
                    <a class="nav-chip d-none d-lg-inline-flex" href="{{ route('kitchen.display') }}" target="_blank">
                        <i class="fas fa-fire"></i> Kitchen
                    </a>
                    @endif
                    <a class="nav-chip d-none d-xl-inline-flex" href="{{ route('customer.display') }}" target="_blank">
                        <i class="fas fa-tv"></i> Display
                    </a>
                </div>
            </li>
            @if(\App\Services\BranchService::enabled())
            @php
                $__branches = \App\Services\BranchService::accessibleBranches();
                $__currentBranch = \App\Services\BranchService::current();
            @endphp
            @if($__branches->count() > 0)
            <li class="nav-item dropdown me-1">
                <a class="nav-chip dropdown-toggle" href="#" id="branchDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration:none;">
                    <i class="fas fa-code-branch"></i>
                    <span class="d-none d-md-inline">{{ $__currentBranch?->name ?? 'Branch' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="branchDropdown" style="min-width:220px;">
                    <li class="dropdown-header">Working branch</li>
                    @foreach($__branches as $__b)
                    <li>
                        <form method="post" action="{{ route('branches.switch') }}" class="m-0">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $__b->id }}">
                            <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center {{ (int)$__currentBranch?->id === (int)$__b->id ? 'active' : '' }}">
                                <span>{{ $__b->name }}</span>
                                @if((int)$__currentBranch?->id === (int)$__b->id)<i class="fas fa-check text-success"></i>@endif
                            </button>
                        </form>
                    </li>
                    @endforeach
                    @can('branches.view')
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('branches.index') }}"><i class="fas fa-cog me-2"></i>Manage branches</a></li>
                    @endcan
                </ul>
            </li>
            @endif
            @endif
            <li class="nav-item">
                <div class="nav-bell-wrap" id="headerNotifWrap">
                    <button type="button" class="nav-bell-btn" id="headerNotifBell" title="Notifications" aria-expanded="false" aria-controls="headerNotifPanel">
                        <i class="fas fa-bell"></i>
                        <span class="nav-bell-badge" id="headerNotifBadge">0</span>
                    </button>
                    <div class="nav-bell-panel" id="headerNotifPanel" role="dialog" aria-label="Notifications">
                        <div class="nav-bell-head">
                            <strong>Notifications</strong>
                            <div class="nav-bell-head-actions">
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="headerNotifReadAll" style="font-size:0.72rem;font-weight:700;color:#d97706;white-space:nowrap;">Mark all read</button>
                                <button type="button" class="nav-bell-close" id="headerNotifClose" title="Close" aria-label="Close">&times;</button>
                            </div>
                        </div>
                        <div class="nav-bell-list" id="headerNotifList">
                            <div class="nav-bell-empty">Loading…</div>
                        </div>
                        <div class="nav-bell-foot">
                            <a href="{{ route('notifications.index') }}" id="headerNotifViewAll">Open reminders panel</a>
                        </div>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-user-btn dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-user-avatar"><i class="fas fa-user"></i></span>
                    <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <div class="px-3 py-2">
                        <div class="fw-bold" style="font-size:0.9rem;">{{ auth()->user()->name }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">{{ auth()->user()->email }}</div>
                        @if(auth()->user()->isSoftwareOwner())
                            <div style="font-size:0.7rem;font-weight:700;color:#d97706;margin-top:4px;">Software Owner</div>
                        @endif
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('dashboard') }}"><i class="fas fa-home me-2 text-muted"></i>Dashboard</a>
                    @can('settings.view')
                    <a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fas fa-cog me-2 text-muted"></i>Settings</a>
                    @endcan
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('dashboard') }}" class="brand-link">
            @php $brandLogo = \App\Models\Setting::logoUrl(); @endphp
            @if($brandLogo)
                <img src="{{ $brandLogo }}" alt="Logo" class="brand-logo">
            @else
                <i class="fas fa-utensils"></i>
            @endif
            <span>{{ \App\Models\Setting::get('company_name', 'ResPOS') }}</span>
        </a>
        <div class="sidebar">
            <nav>
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="sidebar-section-title">Front Desk</li>
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    @can('pos.access')
                    <li class="nav-item">
                        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cash-register"></i>
                            <p>POS Billing</p>
                        </a>
                    </li>
                    @endcan
                    @can('waiter.panel')
                    <li class="nav-item">
                        <a href="{{ route('waiter.index') }}" class="nav-link {{ request()->routeIs('waiter.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-tie"></i>
                            <p>Waiter Panel</p>
                        </a>
                    </li>
                    @endcan

                    <li class="sidebar-section-title">Menu Management</li>
                    @can('orders.view')
                    <li class="nav-item">
                        <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-receipt"></i>
                            <p>Orders</p>
                        </a>
                    </li>
                    @endcan
                    @can('products.view')
                    <li class="nav-item">
                        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hamburger"></i>
                            <p>Products</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('barcode-labels.index') }}" class="nav-link {{ request()->routeIs('barcode-labels.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-barcode"></i>
                            <p>Barcode Labels</p>
                        </a>
                    </li>
                    @endcan
                    @can('categories.view')
                    <li class="nav-item">
                        <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tags"></i>
                            <p>Categories</p>
                        </a>
                    </li>
                    @endcan
                    @can('subcategories.view')
                    <li class="nav-item">
                        <a href="{{ route('subcategories.index') }}" class="nav-link {{ request()->routeIs('subcategories.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-sitemap"></i>
                            <p>Subcategories</p>
                        </a>
                    </li>
                    @endcan
                    @can('option-sets.view')
                    <li class="nav-item">
                        <a href="{{ route('option-sets.index') }}" class="nav-link {{ request()->routeIs('option-sets.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-list-ul"></i>
                            <p>Option Sets</p>
                        </a>
                    </li>
                    @endcan
                    @can('addons.view')
                    <li class="nav-item">
                        <a href="{{ route('addon-groups.index') }}" class="nav-link {{ request()->routeIs('addon-groups.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-layer-group"></i>
                            <p>Modifiers</p>
                        </a>
                    </li>
                    @endcan
                    @can('recipes.view')
                    <li class="nav-item">
                        <a href="{{ route('recipes.index') }}" class="nav-link {{ request()->routeIs('recipes.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book"></i>
                            <p>Recipes</p>
                        </a>
                    </li>
                    @endcan

                    <li class="sidebar-section-title">Inventory</li>
                    @can('ingredients.view')
                    <li class="nav-item">
                        <a href="{{ route('ingredients.index') }}" class="nav-link {{ request()->routeIs('ingredients.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-carrot"></i>
                            <p>Ingredients</p>
                        </a>
                    </li>
                    @endcan
                    @can('inventory.view')
                    <li class="nav-item">
                        <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-warehouse"></i>
                            <p>Inventory</p>
                        </a>
                    </li>
                    @endcan
                    @can('purchases.view')
                    <li class="nav-item">
                        <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>Purchases</p>
                        </a>
                    </li>
                    @endcan
                    @can('suppliers.view')
                    <li class="nav-item">
                        <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>Suppliers</p>
                        </a>
                    </li>
                    @endcan

                    <li class="sidebar-section-title">Operations</li>
                    @can('customers.view')
                    <li class="nav-item">
                        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Customers</p>
                        </a>
                    </li>
                    @endcan
                    @can('customers.view')
                    @if(\App\Services\LoyaltyService::enabled())
                    <li class="nav-item">
                        <a href="{{ route('loyalty.index') }}" class="nav-link {{ request()->routeIs('loyalty.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-stamp"></i>
                            <p>Loyalty</p>
                        </a>
                    </li>
                    @endif
                    @endcan
                    @can('tables.view')
                    <li class="nav-item">
                        <a href="{{ route('floors.index') }}" class="nav-link {{ request()->routeIs('floors.*') || request()->routeIs('tables.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chair"></i>
                            <p>Floors & Tables</p>
                        </a>
                    </li>
                    @endcan
                    @can('delivery_partners.view')
                    <li class="nav-item">
                        <a href="{{ route('delivery-partners.index') }}" class="nav-link {{ request()->routeIs('delivery-partners.index') || request()->routeIs('delivery-partners.create') || request()->routeIs('delivery-partners.edit') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-motorcycle"></i>
                            <p>Delivery Partners</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('delivery-partners.ledger') }}" class="nav-link {{ request()->routeIs('delivery-partners.ledger') || request()->routeIs('delivery-partners.payments.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book"></i>
                            <p>Partner Ledger</p>
                        </a>
                    </li>
                    @endcan

                    @if(\App\Services\BilliardsService::enabled())
                    @canany(['billiards.access','billiards.bookings','billiards.tables','billiards.display','billiards.reports'])
                    <li class="sidebar-section-title">
                        <img src="{{ asset('images/billiards-icon.png') }}" alt="" class="bil-nav-badge" width="18" height="18">
                        Billiards
                    </li>
                    @canany(['billiards.access','billiards.bookings'])
                    <li class="nav-item">
                        <a href="{{ route('billiards.desk') }}" class="nav-link {{ request()->routeIs('billiards.desk') ? 'active' : '' }}">
                            <img src="{{ asset('images/billiards-icon.png') }}" alt="" class="nav-icon bil-nav-icon">
                            <p>Live Desk</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('billiards.pos') }}" class="nav-link {{ request()->routeIs('billiards.pos') || request()->routeIs('billiards.bookings.create') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-plus-circle"></i>
                            <p>Create Booking</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('billiards.bookings.index') }}" class="nav-link {{ request()->routeIs('billiards.bookings.*') && !request()->routeIs('billiards.bookings.create') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>Bookings</p>
                        </a>
                    </li>
                    @endcanany
                    @canany(['billiards.access','billiards.display'])
                    <li class="nav-item">
                        <a href="{{ route('billiards.display') }}" target="_blank" class="nav-link {{ request()->routeIs('billiards.display*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tv"></i>
                            <p>Display</p>
                        </a>
                    </li>
                    @endcanany
                    @canany(['billiards.access','billiards.tables'])
                    <li class="nav-item">
                        <a href="{{ route('billiards.tables.index') }}" class="nav-link {{ request()->routeIs('billiards.tables.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>Tables Management</p>
                        </a>
                    </li>
                    @endcanany
                    @canany(['billiards.access','billiards.reports'])
                    <li class="nav-item">
                        <a href="{{ route('billiards.reports') }}" class="nav-link {{ request()->routeIs('billiards.reports') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Reports</p>
                        </a>
                    </li>
                    @endcanany
                    @canany(['billiards.access'])
                    <li class="nav-item">
                        <a href="{{ route('billiards.settings') }}" class="nav-link {{ request()->routeIs('billiards.settings*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Settings</p>
                        </a>
                    </li>
                    @endcanany
                    @endcanany
                    @endif

                    <li class="sidebar-section-title">Finance</li>
                    @can('accounts.view')
                    <li class="nav-item">
                        <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-university"></i>
                            <p>Accounts</p>
                        </a>
                    </li>
                    @endcan
                    @can('purchases.view')
                    @if(\App\Models\Cheque::managementEnabled())
                    <li class="nav-item">
                        <a href="{{ route('cheques.index') }}" class="nav-link {{ request()->routeIs('cheques.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-check-alt"></i>
                            <p>Cheques</p>
                        </a>
                    </li>
                    @endif
                    @endcan
                    @can('expenses.view')
                    <li class="nav-item">
                        <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>Expenses</p>
                        </a>
                    </li>
                    @endcan

                    <li class="sidebar-section-title">Kitchen</li>
                    @can('kitchen.manage')
                    <li class="nav-item">
                        <a href="{{ route('kitchens.index') }}" class="nav-link {{ request()->routeIs('kitchens.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-fire"></i>
                            <p>Kitchens &amp; KOT</p>
                        </a>
                    </li>
                    @endcan
                    @can('kitchen.view')
                    @if(\App\Models\Setting::get('kitchen_display_enabled', true) && \App\Models\Setting::get('kot_confirmation_enabled', true))
                    <li class="nav-item">
                        <a href="{{ route('kitchen.display') }}" target="_blank" class="nav-link">
                            <i class="nav-icon fas fa-desktop"></i>
                            <p>Kitchen Display</p>
                        </a>
                    </li>
                    @endif
                    @endcan

                    @can('displays.view')
                    <li class="sidebar-section-title">Customer Displays</li>
                    <li class="nav-item">
                        <a href="{{ route('customer.display') }}" target="_blank" class="nav-link">
                            <i class="nav-icon fas fa-tv"></i>
                            <p>Cart Display</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('customer.display.status') }}" target="_blank" class="nav-link">
                            <i class="nav-icon fas fa-columns"></i>
                            <p>Order Status Board</p>
                        </a>
                    </li>
                    @can('displays.manage')
                    <li class="nav-item">
                        <a href="{{ route('admin.marketing.index') }}" class="nav-link {{ request()->routeIs('admin.marketing.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-image"></i>
                            <p>Display Marketing</p>
                        </a>
                    </li>
                    @endcan
                    @endcan

                    @can('promos.view')
                    <li class="sidebar-section-title">Marketing</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.promos.index') }}" class="nav-link {{ request()->routeIs('admin.promos.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-bullhorn"></i>
                            <p>Promo Campaigns</p>
                        </a>
                    </li>
                    @endcan

                    <li class="sidebar-section-title">Administration</li>
                    @if(\App\Services\BranchService::enabled())
                    @canany(['branches.view', 'branches.create', 'branches.edit'])
                    <li class="nav-item">
                        <a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-code-branch"></i>
                            <p>Branches</p>
                        </a>
                    </li>
                    @endcanany
                    @endif
                    @can('reports.view')
                    <li class="nav-item">
                        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Reports</p>
                        </a>
                    </li>
                    @endcan
                    @if(\App\Services\AiAgentService::isAvailable() || auth()->user()?->isSoftwareOwner())
                    <li class="nav-item">
                        <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-robot"></i>
                            <p>Avenque AI</p>
                        </a>
                    </li>
                    @endif
                    @can('notifications.view')
                    <li class="nav-item">
                        <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-bell"></i>
                            <p>Notifications</p>
                        </a>
                    </li>
                    @endcan
                    @can('settings.view')
                    <li class="nav-item">
                        <a href="{{ route('guide.index') }}" class="nav-link {{ request()->routeIs('guide.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book-open"></i>
                            <p>User Guide</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Settings</p>
                        </a>
                    </li>
                    @endcan
                    @can('users.view')
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Users</p>
                        </a>
                    </li>
                    @endcan
                    @can('roles.view')
                    <li class="nav-item">
                        <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-key"></i>
                            <p>Roles &amp; Permissions</p>
                        </a>
                    </li>
                    @endcan
                    @can('owner.access')
                    <li class="sidebar-section-title">Software Owner</li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}#business" class="nav-link">
                            <i class="nav-icon fas fa-image"></i>
                            <p>Software Logo</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}#pos" class="nav-link">
                            <i class="nav-icon fas fa-cash-register"></i>
                            <p>Shift Method</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}#kitchen" class="nav-link">
                            <i class="nav-icon fas fa-utensils"></i>
                            <p>Kitchen Settings</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}#qr" class="nav-link">
                            <i class="nav-icon fas fa-qrcode"></i>
                            <p>QR Menu Settings</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}#waiter" class="nav-link">
                            <i class="nav-icon fas fa-user-tie"></i>
                            <p>Waiter Settings</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-bell"></i>
                            <p>Reminders</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}#email" class="nav-link">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>Email / SMTP</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}#pwa" class="nav-link">
                            <i class="nav-icon fas fa-mobile-alt"></i>
                            <p>PWA Branding</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}#notifications" class="nav-link">
                            <i class="nav-icon fas fa-plug"></i>
                            <p>Integrations</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}#ai" class="nav-link">
                            <i class="nav-icon fas fa-robot"></i>
                            <p>Avenque AI (Gemini)</p>
                        </a>
                    </li>
                    @endcan
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('page_title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            @yield('breadcrumbs')
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>
    </div>

    <footer class="main-footer">
        <div class="footer-inner">
            <span class="footer-brand">QRPOS By AVENQUE (PVT) LTD | 076 822 2201</span>
            <span class="footer-meta">Version 1.0</span>
        </div>
    </footer>
</div>

@php
    $showWelcome = false;
    try {
        $showWelcome = (bool) \App\Models\Setting::get('welcome_show', false)
            && auth()->check()
            && (auth()->user()->can('settings.view') || auth()->user()->isSoftwareOwner());
    } catch (\Throwable $e) {}
@endphp
@if($showWelcome)
<div class="modal fade" id="firstUseWelcomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px;border:0;overflow:hidden;">
            <div class="modal-body p-4">
                <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#f59e0b,#ea580c);display:grid;place-items:center;color:#fff;font-weight:800;margin-bottom:1rem;">Q</div>
                <h4 class="fw-bold mb-2">Welcome to QRPOS</h4>
                <p class="text-muted mb-3">Your restaurant POS is ready. Start with Business settings, add products, then open POS for the first shift.</p>
                <ul class="text-muted small mb-3" style="padding-left:1.1rem;">
                    <li>Open <strong>User Guide</strong> for English / தமிழ் / සිංහල</li>
                    <li>Change admin password under Users</li>
                    <li>Software Owner can run Fresh Start before customer handover</li>
                </ul>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('guide.index') }}" class="btn btn-primary">Open guide</a>
                    <button type="button" class="btn btn-outline-secondary" id="dismissWelcomeBtn" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.main-sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;
    const isOpen = sidebar.classList.toggle('open');
    if (backdrop) backdrop.classList.toggle('open', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

// Close sidebar when clicking a nav link on mobile
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.main-sidebar .nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                const sidebar = document.querySelector('.main-sidebar');
                if (sidebar && sidebar.classList.contains('open')) toggleSidebar();
            }
        });
    });
});

$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

function showToast(type, message) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

function confirmDelete(callback) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ea580c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

function initBulkTables() {
    const deleteUrl = @json(route('admin.bulk-delete'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    document.querySelectorAll('table.bulk-table').forEach((table) => {
        if (table.dataset.bulkReady) return;
        table.dataset.bulkReady = '1';

        const card = table.closest('.card');
        let toolbar = card?.querySelector('[data-bulk-toolbar]');
        if (!toolbar && card) {
            const resource = table.dataset.resource || '';
            const canDelete = table.dataset.canDelete !== '0' && resource;
            const exportName = table.dataset.export || 'export';
            toolbar = document.createElement('div');
            toolbar.className = 'bulk-toolbar d-none';
            toolbar.dataset.bulkToolbar = '1';
            toolbar.dataset.resource = resource;
            toolbar.dataset.export = exportName;
            toolbar.dataset.canDelete = canDelete ? '1' : '0';
            toolbar.innerHTML = `
                <div class="bulk-toolbar-inner">
                    <span class="bulk-toolbar-count"><strong class="bulk-selected-count">0</strong> selected</span>
                    <div class="bulk-toolbar-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary bulk-export-btn"><i class="fas fa-file-export me-1"></i>Export CSV</button>
                        ${canDelete ? '<button type="button" class="btn btn-sm btn-outline-danger bulk-delete-btn"><i class="fas fa-trash me-1"></i>Bulk Delete</button>' : ''}
                        <button type="button" class="btn btn-sm btn-link text-muted bulk-clear-btn">Clear</button>
                    </div>
                </div>`;
            const body = card.querySelector('.card-body');
            if (body) body.prepend(toolbar);
            else card.prepend(toolbar);
        }

        const checkAll = table.querySelector('.bulk-check-all');
        const sync = () => {
            const boxes = [...table.querySelectorAll('.bulk-row-check')];
            const selected = boxes.filter((b) => b.checked);
            boxes.forEach((b) => b.closest('tr')?.classList.toggle('bulk-row-selected', b.checked));
            if (checkAll) {
                checkAll.checked = boxes.length > 0 && selected.length === boxes.length;
                checkAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
            }
            if (toolbar) {
                toolbar.classList.toggle('d-none', selected.length === 0);
                const countEl = toolbar.querySelector('.bulk-selected-count');
                if (countEl) countEl.textContent = String(selected.length);
            }
        };

        checkAll?.addEventListener('change', () => {
            table.querySelectorAll('.bulk-row-check').forEach((b) => { b.checked = checkAll.checked; });
            sync();
        });
        table.addEventListener('change', (e) => {
            if (e.target.classList.contains('bulk-row-check')) sync();
        });

        toolbar?.querySelector('.bulk-clear-btn')?.addEventListener('click', () => {
            table.querySelectorAll('.bulk-row-check, .bulk-check-all').forEach((b) => { b.checked = false; b.indeterminate = false; });
            sync();
        });

        toolbar?.querySelector('.bulk-export-btn')?.addEventListener('click', () => {
            const rows = [...table.querySelectorAll('tbody tr')].filter((tr) => tr.querySelector('.bulk-row-check')?.checked);
            if (!rows.length) return;
            const headers = [...table.querySelectorAll('thead th')].map((th, i) => ({ th, i }))
                .filter(({ th }) => !th.classList.contains('col-check') && !th.classList.contains('col-num') && !/actions/i.test(th.textContent.trim()))
                .map(({ th, i }) => ({ label: th.textContent.trim(), i }));
            const escape = (v) => `"${String(v ?? '').replace(/"/g, '""').replace(/\s+/g, ' ').trim()}"`;
            const lines = [headers.map((h) => escape(h.label)).join(',')];
            rows.forEach((tr) => {
                const cells = [...tr.children];
                lines.push(headers.map((h) => escape(cells[h.i]?.innerText || '')).join(','));
            });
            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            const bizDate = (window.BusinessClock && BusinessClock.format)
                ? (() => { try { return BusinessClock.now().toLocaleDateString('en-CA', { timeZone: BusinessClock.timezone() }); } catch (e) { return new Date().toISOString().slice(0, 10); } })()
                : new Date().toISOString().slice(0, 10);
            a.download = `${toolbar.dataset.export || 'export'}-${bizDate}.csv`;
            a.click();
            URL.revokeObjectURL(a.href);
            showToast('success', `Exported ${rows.length} row(s)`);
        });

        toolbar?.querySelector('.bulk-delete-btn')?.addEventListener('click', () => {
            const ids = [...table.querySelectorAll('.bulk-row-check:checked')].map((b) => b.value);
            const resource = toolbar.dataset.resource;
            if (!ids.length || !resource) return;
            Swal.fire({
                title: `Delete ${ids.length} item(s)?`,
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#b91c1c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete'
            }).then((result) => {
                if (!result.isConfirmed) return;
                fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ resource, ids }),
                })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message || 'Delete failed');
                    showToast('success', data.message || 'Deleted');
                    setTimeout(() => window.location.reload(), 600);
                })
                .catch((err) => {
                    Swal.fire('Error', err.message || 'Bulk delete failed', 'error');
                });
            });
        });

        sync();
    });
}

$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
    initBulkTables();

    const welcomeEl = document.getElementById('firstUseWelcomeModal');
    if (welcomeEl) {
        new bootstrap.Modal(welcomeEl).show();
        document.getElementById('dismissWelcomeBtn')?.addEventListener('click', () => {
            fetch('{{ route('guide.dismiss-welcome') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            }).catch(() => {});
        });
    }

    // Action menus: open outside clipped table containers
    document.querySelectorAll('.row-actions [data-bs-toggle="dropdown"]').forEach((el) => {
        bootstrap.Dropdown.getOrCreateInstance(el, {
            popperConfig(defaultConfig) {
                return {
                    ...defaultConfig,
                    strategy: 'fixed',
                    modifiers: [
                        ...(defaultConfig.modifiers || []),
                        { name: 'preventOverflow', options: { boundary: 'viewport', padding: 8 } },
                        { name: 'flip', options: { fallbackPlacements: ['top-end', 'bottom-start', 'top-start'] } },
                    ],
                };
            },
        });
    });

    // Header in-app notifications (custom panel — not Bootstrap dropdown)
    (function initHeaderNotifications() {
        const wrap = document.getElementById('headerNotifWrap');
        const bell = document.getElementById('headerNotifBell');
        const panel = document.getElementById('headerNotifPanel');
        const badge = document.getElementById('headerNotifBadge');
        const list = document.getElementById('headerNotifList');
        const readAllBtn = document.getElementById('headerNotifReadAll');
        const closeBtn = document.getElementById('headerNotifClose');
        if (!wrap || !bell || !panel || !badge || !list) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function isOpen() {
            return panel.classList.contains('is-open');
        }

        function openPanel() {
            panel.classList.add('is-open');
            bell.classList.add('is-open');
            bell.setAttribute('aria-expanded', 'true');
            load();
        }

        function closePanel() {
            panel.classList.remove('is-open');
            bell.classList.remove('is-open');
            bell.setAttribute('aria-expanded', 'false');
        }

        function togglePanel(e) {
            e.preventDefault();
            e.stopPropagation();
            if (isOpen()) closePanel();
            else openPanel();
        }

        function setBadge(unread) {
            const u = Number(unread || 0);
            badge.textContent = u > 99 ? '99+' : String(u);
            badge.classList.toggle('show', u > 0);
        }

        function markRead(id) {
            return fetch(`/inbox/notifications/${id}/read`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            }).then(r => r.json());
        }

        function render(data) {
            setBadge(data.unread);
            const items = data.notifications || [];
            if (!items.length) {
                list.innerHTML = '<div class="nav-bell-empty">No notifications yet</div>';
                return;
            }
            list.innerHTML = items.map((n) => {
                const isExpiry = n.kind === 'expiry';
                const urgent = !!n.urgent;
                const classes = [
                    'nav-bell-item',
                    n.read ? '' : 'unread',
                    isExpiry ? 'nb-expiry' : '',
                    urgent ? 'nb-urgent' : '',
                ].filter(Boolean).join(' ');
                const photo = isExpiry
                    ? `<img class="nb-photo" src="${escapeHtml(n.image || '/images/product-placeholder.svg')}" alt="" onerror="this.onerror=null;this.src='/images/product-placeholder.svg'">`
                    : '';
                const dismiss = n.dismissible === false
                    ? ''
                    : `<button type="button" class="nb-dismiss" data-dismiss-id="${escapeHtml(n.id)}" title="Dismiss">&times;</button>`;
                const icon = isExpiry
                    ? ''
                    : `<i class="fas ${escapeHtml(n.icon || 'fa-bell')} me-1" style="color:#d97706"></i>`;
                return `
                <div class="${classes}" data-id="${escapeHtml(n.id)}" data-url="${escapeHtml(n.url || '')}" data-kind="${escapeHtml(n.kind || '')}" role="button" tabindex="0">
                    ${dismiss}
                    ${photo}
                    <div class="nb-body">
                        <div class="nb-title">${icon}${escapeHtml(n.title || 'Notification')}</div>
                        <div class="nb-preview">${escapeHtml(n.preview || '')}</div>
                        <div class="nb-time">${escapeHtml(n.created_at || '')}</div>
                    </div>
                </div>`;
            }).join('');

            list.querySelectorAll('.nav-bell-item').forEach((el) => {
                el.addEventListener('click', (e) => {
                    if (e.target.closest('.nb-dismiss')) return;
                    const id = el.getAttribute('data-id');
                    const url = el.getAttribute('data-url');
                    const kind = el.getAttribute('data-kind');
                    if (!id) return;

                    if (kind === 'expiry' && url) {
                        closePanel();
                        window.location.href = url;
                        return;
                    }

                    markRead(id).then((res) => {
                        el.classList.remove('unread');
                        setBadge(res.unread);
                        closePanel();
                        if (url && url !== '#' && !url.includes('/notifications')) {
                            window.location.href = url;
                            return;
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: el.querySelector('.nb-title')?.innerText?.trim() || 'Notification',
                                text: el.querySelector('.nb-preview')?.textContent?.trim() || '',
                                confirmButtonColor: '#f59e0b',
                                confirmButtonText: 'Close',
                            });
                        }
                    }).catch(() => {});
                });
            });

            list.querySelectorAll('.nb-dismiss').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const id = btn.getAttribute('data-dismiss-id');
                    if (!id) return;
                    markRead(id).then((res) => {
                        btn.closest('.nav-bell-item')?.remove();
                        setBadge(res.unread);
                        if (!list.querySelector('.nav-bell-item')) {
                            list.innerHTML = '<div class="nav-bell-empty">No notifications yet</div>';
                        }
                    }).catch(() => {});
                });
            });
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function load() {
            fetch('/inbox/notifications', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(render)
                .catch(() => { list.innerHTML = '<div class="nav-bell-empty">Unable to load</div>'; });
        }

        bell.addEventListener('click', togglePanel);

        closeBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            closePanel();
        });

        readAllBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fetch('/inbox/notifications/read-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            }).then(r => r.json()).then(() => {
                load();
                closePanel();
            }).catch(() => {});
        });

        document.getElementById('headerNotifViewAll')?.addEventListener('click', () => closePanel());

        // Click outside closes
        document.addEventListener('click', (e) => {
            if (!isOpen()) return;
            if (wrap.contains(e.target)) return;
            closePanel();
        });

        // Escape closes
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isOpen()) closePanel();
        });

        load();
        setInterval(load, 45000);
    })();
});
</script>
@stack('scripts')
<script>
(function () {
    if (!window.BusinessClock) return;
    BusinessClock.bind('#adminBusinessClock', { mode: 'split', seconds: true });
})();
</script>
<script src="{{ asset('js/avenque-tour.js') }}?v=1"></script>
@include('partials.avenque-ai-chatbot')
</body>
</html>
