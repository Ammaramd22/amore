<style>
    .dash {
        --dash-ink: #1c1917;
        --dash-muted: #78716c;
        --dash-line: #e7e5e4;
        --dash-soft: #fafaf9;
        --dash-amber: #f59e0b;
        --dash-amber2: #ea580c;
        --dash-card: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .dash-hero {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1.25rem;
        padding: 1.5rem 1.6rem;
        border-radius: 20px;
        background:
            radial-gradient(ellipse 80% 120% at 100% 0%, rgba(245, 158, 11, 0.28), transparent 55%),
            radial-gradient(ellipse 50% 80% at 0% 100%, rgba(234, 88, 12, 0.18), transparent 50%),
            linear-gradient(135deg, #1c1917 0%, #292524 55%, #44403c 100%);
        color: #fff7ed;
        box-shadow: 0 18px 40px rgba(28, 25, 23, 0.22);
        animation: dashIn 0.45s ease both;
    }
    .dash-eyebrow {
        margin: 0 0 0.35rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #fbbf24;
    }
    .dash-title {
        margin: 0 0 0.35rem;
        font-size: clamp(1.45rem, 2.4vw, 1.85rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #fff;
    }
    .dash-sub {
        margin: 0;
        max-width: 36rem;
        color: rgba(255, 247, 237, 0.72);
        font-size: 0.95rem;
        line-height: 1.45;
    }
    .dash-hero-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }
    .dash-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        padding: 0.3rem;
        background: rgba(0, 0, 0, 0.25);
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .dash-pill {
        border: 0;
        background: transparent;
        color: rgba(255, 247, 237, 0.7);
        font: inherit;
        font-size: 0.8rem;
        font-weight: 650;
        padding: 0.45rem 0.85rem;
        border-radius: 999px;
        cursor: pointer;
        transition: 0.18s ease;
    }
    .dash-pill:hover { color: #fff; background: rgba(255,255,255,0.08); }
    .dash-pill.active {
        background: linear-gradient(135deg, var(--dash-amber), var(--dash-amber2));
        color: #fff;
        box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35);
    }
    .dash-custom-dates {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    .dash-custom-dates .form-control {
        background: rgba(255,255,255,0.08);
        border-color: rgba(255,255,255,0.15);
        color: #fff;
        max-width: 140px;
    }
    .dash-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.75rem 1.15rem;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--dash-amber), var(--dash-amber2));
        color: #fff !important;
        text-decoration: none !important;
        font-weight: 700;
        font-size: 0.92rem;
        box-shadow: 0 10px 24px rgba(234, 88, 12, 0.35);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .dash-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(234, 88, 12, 0.45);
        color: #fff !important;
    }

    .dash-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }
    .dash-kpi {
        background: var(--dash-card);
        border: 1px solid var(--dash-line);
        border-radius: 18px;
        padding: 1.15rem 1.2rem;
        box-shadow: 0 1px 2px rgba(28, 25, 23, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        animation: dashIn 0.5s ease both;
    }
    .dash-kpi:nth-child(2) { animation-delay: 0.05s; }
    .dash-kpi:nth-child(3) { animation-delay: 0.1s; }
    .dash-kpi:nth-child(4) { animation-delay: 0.15s; }
    .dash-kpi:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(28, 25, 23, 0.08);
    }
    .dash-kpi--sales {
        background: linear-gradient(160deg, #fff7ed 0%, #ffffff 55%);
        border-color: #fed7aa;
    }
    .dash-kpi--alert {
        border-color: #fdba74;
        background: linear-gradient(160deg, #fff7ed, #fff);
    }
    .dash-kpi-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.85rem;
    }
    .dash-kpi-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: grid; place-items: center;
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
        font-size: 1.05rem;
    }
    .dash-kpi-icon.is-amber { background: rgba(245, 158, 11, 0.14); color: #d97706; }
    .dash-kpi-icon.is-fire { background: rgba(234, 88, 12, 0.14); color: #ea580c; }
    .dash-kpi-icon.is-blue { background: rgba(14, 165, 233, 0.12); color: #0284c7; }
    .dash-kpi-tag {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--dash-muted);
    }
    .dash-kpi-value {
        font-size: clamp(1.45rem, 2vw, 1.85rem);
        font-weight: 800;
        color: var(--dash-ink);
        letter-spacing: -0.03em;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
    }
    .dash-kpi-label {
        margin-top: 0.35rem;
        font-size: 0.82rem;
        color: var(--dash-muted);
    }

    .dash-shortcuts {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 0.65rem;
    }
    .dash-shortcut {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.85rem 0.75rem;
        border-radius: 14px;
        background: #fff;
        border: 1px solid var(--dash-line);
        color: #44403c !important;
        text-decoration: none !important;
        font-size: 0.82rem;
        font-weight: 650;
        transition: 0.18s ease;
    }
    .dash-shortcut i { color: var(--dash-amber); }
    .dash-shortcut:hover {
        border-color: #fdba74;
        background: #fff7ed;
        transform: translateY(-2px);
        color: #1c1917 !important;
    }

    .dash-grid-main {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 1rem;
    }
    .dash-grid-two {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .dash-panel {
        background: #fff;
        border: 1px solid var(--dash-line);
        border-radius: 18px;
        padding: 1.15rem 1.25rem;
        box-shadow: 0 1px 2px rgba(28, 25, 23, 0.04);
        animation: dashIn 0.55s ease both;
    }
    .dash-panel--warn { border-color: #fdba74; }
    .dash-panel--table { padding-bottom: 0.5rem; }
    .dash-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .dash-panel-head h3 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 750;
        color: var(--dash-ink);
        letter-spacing: -0.02em;
    }
    .dash-panel-head p {
        margin: 0.2rem 0 0;
        font-size: 0.82rem;
        color: var(--dash-muted);
    }
    .dash-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .dash-link {
        font-size: 0.82rem;
        font-weight: 700;
        color: #d97706 !important;
        text-decoration: none !important;
    }
    .dash-link:hover { color: #ea580c !important; }

    .dash-chart-wrap {
        height: 280px;
        position: relative;
    }

    .dash-types { display: flex; flex-direction: column; gap: 0.9rem; }
    .dash-type-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .dash-type-icon {
        width: 40px; height: 40px;
        border-radius: 12px;
        display: grid; place-items: center;
        flex: 0 0 auto;
    }
    .tone-green { background: rgba(16,185,129,0.12); color: #059669; }
    .tone-sky { background: rgba(14,165,233,0.12); color: #0284c7; }
    .tone-amber { background: rgba(245,158,11,0.14); color: #d97706; }
    .tone-emerald { background: rgba(16,185,129,0.16); color: #047857; }
    .tone-red { background: rgba(239,68,68,0.12); color: #dc2626; }
    .dash-type-meta { flex: 1; min-width: 0; }
    .dash-type-meta strong { display: block; font-size: 0.92rem; color: var(--dash-ink); }
    .dash-type-meta span { font-size: 0.75rem; color: var(--dash-muted); }
    .dash-type-count {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--dash-ink);
        font-variant-numeric: tabular-nums;
    }
    .dash-bar {
        margin-top: 0.45rem;
        height: 6px;
        border-radius: 999px;
        background: #f5f5f4;
        overflow: hidden;
    }
    .dash-bar span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--dash-amber), var(--dash-amber2));
        transition: width 0.6s ease;
    }
    .dash-type--done {
        margin-top: 0.35rem;
        padding-top: 0.85rem;
        border-top: 1px dashed var(--dash-line);
    }

    .dash-rank { display: flex; flex-direction: column; gap: 0.75rem; }
    .dash-rank-row { display: flex; gap: 0.7rem; align-items: flex-start; }
    .dash-rank-n {
        width: 26px; height: 26px;
        border-radius: 8px;
        background: #1c1917;
        color: #fbbf24;
        display: grid; place-items: center;
        font-size: 0.72rem;
        font-weight: 800;
        flex: 0 0 auto;
        margin-top: 2px;
    }
    .dash-rank-body { flex: 1; min-width: 0; }
    .dash-rank-top {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.35rem;
        font-size: 0.88rem;
    }
    .dash-rank-top strong { color: var(--dash-ink); font-weight: 650; }
    .dash-rank-top span { color: var(--dash-muted); font-weight: 700; font-variant-numeric: tabular-nums; }

    .dash-stock { display: flex; flex-direction: column; gap: 0.55rem; }
    .dash-stock-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        padding: 0.7rem 0.85rem;
        border-radius: 12px;
        background: #fff7ed;
        border: 1px solid #ffedd5;
    }
    .dash-stock-row strong { display: block; font-size: 0.88rem; color: var(--dash-ink); }
    .dash-stock-row span { font-size: 0.75rem; color: var(--dash-muted); }
    .dash-stock-qty {
        font-weight: 800;
        color: #c2410c;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .dash-expiry-list { gap: 0.65rem; }
    .dash-expiry-row {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.65rem 0.85rem;
        border-radius: 14px;
        background: #fff7ed;
        border: 1px solid #ffedd5;
        color: inherit;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .dash-expiry-row:hover {
        background: #ffedd5;
        border-color: #fdba74;
    }
    .dash-expiry-photo {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        object-fit: cover;
        background: #f5f5f4;
        flex-shrink: 0;
        border: 1px solid rgba(0,0,0,0.06);
    }
    .dash-expiry-meta {
        flex: 1;
        min-width: 0;
    }
    .dash-expiry-meta strong {
        display: block;
        font-size: 0.92rem;
        color: var(--dash-ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dash-expiry-meta span {
        font-size: 0.75rem;
        color: var(--dash-muted);
    }
    .dash-expiry-badge {
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        color: #c2410c;
        font-size: 0.88rem;
    }
    .dash-expiry-qty {
        color: #1c1917;
        font-weight: 700;
    }
    .dash-expiry-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.35rem;
        flex-shrink: 0;
    }
    .dash-expiry-update {
        font-size: 0.75rem;
        font-weight: 700;
        color: #c2410c !important;
        text-decoration: none !important;
        border: 1px solid #fdba74;
        background: #fff;
        border-radius: 999px;
        padding: 0.2rem 0.65rem;
    }
    .dash-expiry-update:hover {
        background: #fff7ed;
        color: #9a3412 !important;
    }
    .dash-expiry-badge--urgent {
        color: #dc2626;
        background: #fee2e2;
        border: 1px solid #fecaca;
        border-radius: 999px;
        padding: 0.3rem 0.7rem;
    }
    .dash-expiry-row--blink {
        background: #fef2f2;
        border-color: #f87171;
        animation: dashExpiryBlink 1s ease-in-out infinite;
    }
    .dash-expiry-row--blink .dash-expiry-photo {
        box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.45);
    }
    @keyframes dashExpiryBlink {
        0%, 100% {
            background: #fef2f2;
            border-color: #f87171;
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.35);
        }
        50% {
            background: #fee2e2;
            border-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.18);
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .dash-expiry-row--blink { animation: none; }
    }

    .dash-empty {
        text-align: center;
        color: var(--dash-muted);
        padding: 2rem 1rem;
        font-size: 0.92rem;
    }
    .dash-empty--ok {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        color: #059669;
        background: #ecfdf5;
        border-radius: 14px;
        border: 1px solid #a7f3d0;
    }
    .dash-empty--ok i { font-size: 1.5rem; }

    .dash-table thead th {
        background: var(--dash-soft) !important;
        color: var(--dash-muted) !important;
    }
    .dash-order-link {
        font-weight: 700;
        color: #c2410c !important;
        text-decoration: none !important;
    }
    .dash-order-link:hover { color: #ea580c !important; }
    .dash-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .dash-badge.tone-green { background: #ecfdf5; color: #047857; }
    .dash-badge.tone-sky { background: #f0f9ff; color: #0369a1; }
    .dash-badge.tone-amber { background: #fff7ed; color: #c2410c; }
    .dash-badge.tone-red { background: #fef2f2; color: #b91c1c; }

    @keyframes dashIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .dash-hero, .dash-kpi, .dash-panel { animation: none; }
        .dash-kpi:hover, .dash-shortcut:hover, .dash-cta:hover { transform: none; }
    }

    @media (max-width: 1199.98px) {
        .dash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dash-shortcuts { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .dash-grid-main, .dash-grid-two { grid-template-columns: 1fr; }
    }
    @media (max-width: 575.98px) {
        .dash-hero { padding: 1.15rem; border-radius: 16px; }
        .dash-kpis, .dash-shortcuts { grid-template-columns: 1fr 1fr; }
        .dash-shortcut { font-size: 0.75rem; padding: 0.75rem 0.5rem; }
        .dash-chart-wrap { height: 220px; }
        .dash-pills { width: 100%; }
        .dash-pill { flex: 1; text-align: center; padding: 0.45rem 0.4rem; }
    }
</style>
