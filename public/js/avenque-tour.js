/**
 * Avenque Guide — lightweight interactive tours (no external API).
 * Usage: AvenqueTour.start('billing') or ?tour=billing
 */
(function (window, document) {
    'use strict';

    const STYLE_ID = 'avenque-tour-style';

    function ensureStyles() {
        if (document.getElementById(STYLE_ID)) return;
        const s = document.createElement('style');
        s.id = STYLE_ID;
        s.textContent = `
            .aq-tour-overlay {
                position: fixed; inset: 0; z-index: 20000;
                background: rgba(28, 25, 23, .55);
                pointer-events: auto;
            }
            .aq-tour-spotlight {
                position: fixed; z-index: 20001;
                border-radius: 14px;
                box-shadow: 0 0 0 9999px rgba(28,25,23,.55), 0 0 0 3px #f59e0b, 0 12px 40px rgba(0,0,0,.35);
                pointer-events: none;
                transition: top .25s ease, left .25s ease, width .25s ease, height .25s ease;
                background: transparent;
            }
            .aq-tour-card {
                position: fixed; z-index: 20002;
                width: min(340px, calc(100vw - 24px));
                background: #fff;
                border-radius: 16px;
                padding: 14px 16px 12px;
                box-shadow: 0 20px 50px rgba(28,25,23,.3);
                border: 1px solid #fed7aa;
                font-family: Inter, system-ui, sans-serif;
            }
            .aq-tour-card .aq-kicker {
                display: inline-flex; align-items: center; gap: 6px;
                font-size: .7rem; font-weight: 800; color: #ea580c;
                text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px;
            }
            .aq-tour-card h4 {
                margin: 0 0 6px; font-size: 1rem; font-weight: 800; color: #1c1917;
            }
            .aq-tour-card p {
                margin: 0 0 12px; font-size: .86rem; line-height: 1.45; color: #57534e;
            }
            .aq-tour-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
            .aq-tour-actions button {
                border-radius: 10px; border: none; padding: .45rem .85rem;
                font-weight: 700; font-size: .82rem; cursor: pointer;
            }
            .aq-btn-skip { background: #f5f5f4; color: #57534e; }
            .aq-btn-back { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa !important; }
            .aq-btn-next { background: #f59e0b; color: #1c1917; }
            .aq-tour-progress {
                height: 3px; background: #f5f5f4; border-radius: 99px; margin: 0 0 10px; overflow: hidden;
            }
            .aq-tour-progress > span {
                display: block; height: 100%; background: linear-gradient(90deg,#f59e0b,#ea580c);
                transition: width .25s ease;
            }
        `;
        document.head.appendChild(s);
    }

    function pick(selectors) {
        if (!selectors) return null;
        const list = Array.isArray(selectors) ? selectors : [selectors];
        for (const sel of list) {
            const el = document.querySelector(sel);
            if (el && el.offsetParent !== null) return el;
            if (el) return el; // hidden still ok for some steps
        }
        return null;
    }

    const TOURS = {
        billing: {
            title: 'Billing tour',
            steps: [
                {
                    selectors: ['#orderTypeGroup', '.order-type-btn.dine-in', '.order-type-btn'],
                    title: '1. Order type',
                    body: 'Choose Dine-in, Takeaway, Delivery, or Express. This controls table, kitchen tickets, and payment.',
                },
                {
                    selectors: ['#tableSelectContainer', '#tableSelect'],
                    title: '2. Table (dine-in)',
                    body: 'For dine-in, select the table here. Waiter can be assigned if your restaurant uses waiters.',
                    optional: true,
                },
                {
                    selectors: ['#productSearch', '.product-search', 'input[placeholder*="Search"]'],
                    title: '3. Find products',
                    body: 'Search by name or code (often F2). Or tap a category chip, then pick items from the grid.',
                },
                {
                    selectors: ['#productsGrid', '.product-grid', '.product-card'],
                    title: '4. Add to cart',
                    body: 'Tap a product to add it. Variants/add-ons open if the item has them. Build the guest’s order in the cart.',
                },
                {
                    selectors: ['#placeOrderBtn', '.place-order-btn', '.mobile-place-order-btn', '.cart-footer'],
                    title: '5. Place Order',
                    body: 'When the cart is ready, tap Place Order. Kitchen gets KOT/BOT. The bill stays open until paid.',
                },
                {
                    selectors: ['.pay-btn', 'button[onclick=\"openPaymentModal()\"]', '.mobile-pay-btn'],
                    title: '6. Pay Now',
                    body: 'When the guest pays, tap Pay Now → choose Cash/Card/etc. → confirm. Print the receipt if asked.',
                },
            ],
        },
        product: {
            title: 'Add product tour',
            steps: [
                {
                    selectors: ['a[href*="products/create"]', '.btn-primary'],
                    title: '1. Add Product',
                    body: 'Click + Add Product to open the create form.',
                },
                {
                    selectors: ['#name', 'input[name="name"]', 'form'],
                    title: '2. Fill details',
                    body: 'Enter Name, Code, Category, Selling price. Set type KOT / BOT / Direct, then Save.',
                    optional: true,
                },
            ],
        },
    };

    let state = null;

    function clearUi() {
        document.querySelectorAll('.aq-tour-overlay, .aq-tour-spotlight, .aq-tour-card').forEach((n) => n.remove());
    }

    function placeCard(card, target) {
        const pad = 12;
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const cr = card.getBoundingClientRect();
        let top = pad;
        let left = Math.max(pad, (vw - cr.width) / 2);

        if (target) {
            const r = target.getBoundingClientRect();
            // Prefer below target; flip above if needed
            top = r.bottom + 14;
            left = Math.min(Math.max(pad, r.left), vw - cr.width - pad);
            if (top + cr.height > vh - pad) {
                top = Math.max(pad, r.top - cr.height - 14);
            }
            if (top < pad) top = pad;
        }

        card.style.top = `${Math.round(top)}px`;
        card.style.left = `${Math.round(left)}px`;
    }

    function renderStep() {
        if (!state) return;
        clearUi();
        ensureStyles();

        const tour = state.tour;
        const step = tour.steps[state.index];
        const total = tour.steps.length;
        let target = pick(step.selectors);

        // Skip optional missing targets
        if (!target && step.optional) {
            state.index += state.dir >= 0 ? 1 : -1;
            if (state.index < 0 || state.index >= total) {
                stop();
                return;
            }
            renderStep();
            return;
        }

        const overlay = document.createElement('div');
        overlay.className = 'aq-tour-overlay';
        // transparent overlay holes via spotlight box-shadow; keep overlay for click-block
        overlay.style.background = 'transparent';
        document.body.appendChild(overlay);

        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        }

        requestAnimationFrame(() => {
            const spot = document.createElement('div');
            spot.className = 'aq-tour-spotlight';
            if (target) {
                const r = target.getBoundingClientRect();
                const m = 6;
                spot.style.top = `${Math.max(0, r.top - m)}px`;
                spot.style.left = `${Math.max(0, r.left - m)}px`;
                spot.style.width = `${r.width + m * 2}px`;
                spot.style.height = `${r.height + m * 2}px`;
            } else {
                spot.style.top = '20%';
                spot.style.left = '10%';
                spot.style.width = '80%';
                spot.style.height = '40%';
            }
            document.body.appendChild(spot);

            const card = document.createElement('div');
            card.className = 'aq-tour-card';
            const pct = Math.round(((state.index + 1) / total) * 100);
            card.innerHTML = `
                <div class="aq-tour-progress"><span style="width:${pct}%"></span></div>
                <div class="aq-kicker"><i class="fas fa-route"></i> ${tour.title} · ${state.index + 1}/${total}</div>
                <h4>${step.title}</h4>
                <p>${step.body}${!target ? '<br><br><em>Open POS Billing to see this control live.</em>' : ''}</p>
                <div class="aq-tour-actions">
                    <button type="button" class="aq-btn-skip" data-aq="skip">Skip</button>
                    ${state.index > 0 ? '<button type="button" class="aq-btn-back" data-aq="back">Back</button>' : ''}
                    <button type="button" class="aq-btn-next" data-aq="next">${state.index >= total - 1 ? 'Finish' : 'Next'}</button>
                </div>
            `;
            document.body.appendChild(card);
            placeCard(card, target);

            card.querySelector('[data-aq="skip"]').onclick = stop;
            const back = card.querySelector('[data-aq="back"]');
            if (back) back.onclick = () => { state.dir = -1; state.index -= 1; renderStep(); };
            card.querySelector('[data-aq="next"]').onclick = () => {
                if (state.index >= total - 1) { stop(); return; }
                state.dir = 1;
                state.index += 1;
                renderStep();
            };
        });
    }

    function stop() {
        state = null;
        clearUi();
        try {
            const u = new URL(window.location.href);
            if (u.searchParams.has('tour')) {
                u.searchParams.delete('tour');
                window.history.replaceState({}, '', u.pathname + u.search + u.hash);
            }
        } catch (e) {}
    }

    function start(name) {
        const tour = TOURS[name];
        if (!tour) return false;
        state = { name, tour, index: 0, dir: 1 };
        renderStep();
        return true;
    }

    function launch(name, url) {
        const path = window.location.pathname || '';
        if (name === 'billing' && !path.includes('/pos')) {
            window.location.href = url || '/pos?tour=billing';
            return;
        }
        if (name === 'product' && !path.includes('/products')) {
            window.location.href = url || '/products?tour=product';
            return;
        }
        start(name);
    }

    function autoStartFromQuery() {
        try {
            const u = new URL(window.location.href);
            const t = u.searchParams.get('tour');
            if (t && TOURS[t]) {
                setTimeout(() => start(t), 600);
            }
        } catch (e) {}
    }

    window.AvenqueTour = { start, stop, launch, tours: TOURS };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoStartFromQuery);
    } else {
        autoStartFromQuery();
    }
})(window, document);
