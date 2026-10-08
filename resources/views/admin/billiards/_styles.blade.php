{{-- Billiards design system v2 — club floor ops desk --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bil-ink:#10231c;
  --bil-muted:#5a6e64;
  --bil-soft:#8a9a92;
  --bil-felt:#0a3326;
  --bil-felt-2:#0f4a37;
  --bil-felt-3:#16664c;
  --bil-wood:#6b4423;
  --bil-wood-light:#a67c52;
  --bil-gold:#d4a017;
  --bil-gold-deep:#b8860b;
  --bil-paper:#f4f1ea;
  --bil-white:#fffcf7;
  --bil-line:rgba(16,35,28,.09);
  --bil-ok:#159a5a;
  --bil-warn:#c45c12;
  --bil-danger:#c62828;
  --bil-font:'Instrument Sans',system-ui,sans-serif;
  --bil-display:'Outfit',system-ui,sans-serif;
  --bil-radius:18px;
}
.bil-shell{
  --content-pad:1rem;
  font-family:var(--bil-font);
  color:var(--bil-ink);
  margin:-.5rem -.75rem 0;
  padding:0 0 5.5rem;
  min-height:calc(100vh - 70px);
  background:
    linear-gradient(180deg, transparent 0 42%, rgba(10,51,38,.04) 100%),
    radial-gradient(ellipse 80% 50% at 100% -20%, rgba(212,160,23,.16), transparent 55%),
    radial-gradient(ellipse 60% 40% at -10% 30%, rgba(15,74,55,.1), transparent 50%),
    repeating-linear-gradient(90deg, transparent, transparent 47px, rgba(16,35,28,.015) 47px, rgba(16,35,28,.015) 48px),
    var(--bil-paper);
}
@media(min-width:992px){
  .bil-shell{ margin:-.75rem -1.25rem 0; --content-pad:1.35rem; }
}
.bil-shell *,.bil-shell *::before,.bil-shell *::after{ box-sizing:border-box; }
.bil-inner{ max-width:1240px; margin:0 auto; padding:var(--content-pad) var(--content-pad) 0; }

/* Command bar */
.bil-cmd{
  display:grid;
  grid-template-columns:minmax(0,1fr) auto;
  gap:1rem;
  align-items:center;
  margin-bottom:1rem;
  padding:.85rem 1rem;
  border-radius:22px;
  background:rgba(255,252,247,.82);
  border:1px solid var(--bil-line);
  backdrop-filter:blur(12px);
  position:sticky; top:.5rem; z-index:30;
}
.bil-brand{ display:flex; align-items:center; gap:.9rem; min-width:0; }
.bil-mark{
  width:56px; height:56px; flex-shrink:0; border-radius:14px;
  background: url('/images/billiards-icon.png') center / cover no-repeat;
  border:1px solid rgba(201,162,39,.5);
  box-shadow: 0 8px 18px rgba(0,0,0,.2);
  position:relative;
}
.bil-mark::before,
.bil-mark::after{ display:none; }
.bil-brand h1{
  font-family:var(--bil-display); font-weight:800; letter-spacing:-.04em;
  font-size:clamp(1.45rem, 2.4vw, 1.85rem); margin:0; line-height:1;
}
.bil-brand .meta{
  display:flex; flex-wrap:wrap; align-items:center; gap:.45rem .7rem;
  margin-top:.35rem; color:var(--bil-muted); font-size:.8rem; font-weight:600;
}
.bil-live{
  display:inline-flex; align-items:center; gap:.35rem;
  color:var(--bil-ok); font-weight:700;
}
.bil-live i{
  width:7px; height:7px; border-radius:50%; background:currentColor;
  box-shadow:0 0 0 3px rgba(21,154,90,.2);
  animation:bilBlink 1.6s ease infinite;
}
.bil-clock{ font-variant-numeric:tabular-nums; color:var(--bil-ink); font-weight:700; }
.bil-actions{ display:flex; flex-wrap:wrap; gap:.45rem; justify-content:flex-end; }
.bil-btn{
  display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
  min-height:44px; padding:.55rem 1.05rem; border-radius:999px;
  font-weight:700; font-size:.84rem; text-decoration:none; border:1px solid transparent;
  cursor:pointer; transition:transform .15s ease, background .15s ease, box-shadow .15s ease;
}
.bil-btn:hover{ transform:translateY(-1px); text-decoration:none; }
.bil-btn:focus-visible{ outline:3px solid rgba(212,160,23,.5); outline-offset:2px; }
.bil-btn--primary{
  background:linear-gradient(180deg, #e8b84a, var(--bil-gold-deep));
  color:#1a1204; border-color:#a87808;
  box-shadow:0 6px 16px rgba(184,134,11,.28);
}
.bil-btn--primary:hover{ color:#1a1204; }
.bil-btn--ghost{
  background:var(--bil-white); color:var(--bil-ink); border-color:var(--bil-line);
}
.bil-btn--ghost:hover{ color:var(--bil-ink); background:#fff; }
.bil-btn--felt{
  background:var(--bil-felt); color:#e9f6ef; border-color:#062419;
}
.bil-btn--felt:hover{ color:#fff; background:var(--bil-felt-2); }

/* KPI rail */
.bil-kpis{
  display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.7rem;
  margin-bottom:1.1rem;
}
.bil-kpi{
  position:relative; overflow:hidden;
  padding:1rem 1.05rem .95rem;
  border-radius:var(--bil-radius);
  background:var(--bil-white);
  border:1px solid var(--bil-line);
  animation:bilRise .45s ease both;
}
.bil-kpi:nth-child(2){animation-delay:.04s}
.bil-kpi:nth-child(3){animation-delay:.08s}
.bil-kpi:nth-child(4){animation-delay:.12s}
.bil-kpi::after{
  content:''; position:absolute; right:-12px; top:-18px;
  width:70px; height:70px; border-radius:50%;
  background:radial-gradient(circle, rgba(15,74,55,.1), transparent 70%);
}
.bil-kpi .label{
  font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
  color:var(--bil-soft);
}
.bil-kpi .value{
  font-family:var(--bil-display); font-weight:800; letter-spacing:-.04em;
  font-size:clamp(1.55rem, 2.5vw, 1.9rem); margin-top:.2rem; line-height:1;
}
.bil-kpi .hint{ margin-top:.35rem; font-size:.75rem; font-weight:600; color:var(--bil-muted); }
.bil-kpi.is-alert{ border-color:rgba(198,40,40,.28); background:linear-gradient(160deg,#fff5f3,#fffcf7); }
.bil-kpi.is-alert .value{ color:var(--bil-danger); }

/* Section heads */
.bil-head{
  display:flex; align-items:baseline; justify-content:space-between; gap:.75rem;
  margin:0 0 .7rem;
}
.bil-head h2{
  font-family:var(--bil-display); font-size:1.05rem; font-weight:800;
  letter-spacing:-.02em; margin:0;
}
.bil-head span{ color:var(--bil-muted); font-size:.78rem; font-weight:600; }

/* Floor stage */
.bil-stage{
  position:relative;
  margin-bottom:1.15rem;
  border-radius:28px;
  padding:14px;
  background:
    linear-gradient(145deg, #8d6238 0%, #5c3a1c 40%, #3f2712 100%);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.18),
    0 18px 40px rgba(63,39,18,.18);
  animation:bilRise .5s ease both;
}
.bil-stage-felt{
  position:relative;
  border-radius:18px;
  min-height:220px;
  padding:1.15rem;
  background:
    radial-gradient(ellipse 90% 70% at 50% 40%, rgba(255,255,255,.06), transparent 55%),
    repeating-linear-gradient(0deg, transparent, transparent 3px, rgba(0,0,0,.03) 3px, rgba(0,0,0,.03) 4px),
    linear-gradient(165deg, #1a7a58 0%, var(--bil-felt-2) 42%, var(--bil-felt) 100%);
  overflow:hidden;
}
.bil-stage-felt::before,
.bil-stage-felt::after{
  content:''; position:absolute; width:14px; height:14px; border-radius:50%;
  background:#0a1f18; border:2px solid rgba(201,164,108,.55); z-index:0;
}
.bil-stage-felt::before{ left:10px; top:10px; box-shadow:
  calc(100% - 34px) 0 0 #0a1f18,
  0 calc(100% - 34px) 0 #0a1f18,
  calc(100% - 34px) calc(100% - 34px) 0 #0a1f18; }
/* pocket mid-side via pseudo on grid instead */
.bil-grid{
  position:relative; z-index:1;
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(190px, 1fr));
  gap:1rem;
}
.bil-table{
  position:relative;
  display:flex; flex-direction:column;
  min-height:168px;
  padding:0;
  border-radius:16px;
  text-decoration:none; color:inherit;
  background:rgba(4,22,16,.42);
  border:1px solid rgba(255,255,255,.12);
  overflow:hidden;
  cursor:pointer;
  transition:transform .18s ease, border-color .18s ease, background .18s ease;
}
.bil-table:hover{
  transform:translateY(-4px) scale(1.01);
  border-color:rgba(212,160,23,.55);
  color:inherit; text-decoration:none;
  background:rgba(4,22,16,.55);
}
.bil-table:focus-visible{ outline:3px solid rgba(212,160,23,.55); outline-offset:3px; }
.bil-table-felt{
  position:relative;
  margin:12px 12px 0;
  height:78px;
  border-radius:999px;
  background:
    radial-gradient(ellipse at 35% 30%, rgba(255,255,255,.14), transparent 50%),
    linear-gradient(180deg, #218a63, #0d4735);
  border:2px solid rgba(201,164,108,.55);
  box-shadow: inset 0 0 0 3px rgba(8,40,28,.25);
}
.bil-table-felt .pocket{
  position:absolute; width:10px; height:10px; border-radius:50%;
  background:#06150f; border:1px solid rgba(201,164,108,.4);
}
.bil-table-felt .pocket.tl{ left:6px; top:50%; transform:translateY(-50%); }
.bil-table-felt .pocket.tr{ right:6px; top:50%; transform:translateY(-50%); }
.bil-table-felt .pocket.bl{ left:28%; bottom:4px; }
.bil-table-felt .pocket.br{ right:28%; bottom:4px; }
.bil-table-felt .pocket.tm{ left:50%; top:4px; transform:translateX(-50%); }
.bil-table-felt .pocket.bm{ left:50%; bottom:4px; transform:translateX(-50%); }
.bil-table-felt .ball{
  position:absolute; width:12px; height:12px; border-radius:50%;
  left:58%; top:42%;
  background:radial-gradient(circle at 35% 30%, #fff, #e8e8e8 40%, #bbb);
  box-shadow:0 1px 2px rgba(0,0,0,.35);
}
.bil-table-body{ padding:.7rem .9rem .85rem; display:flex; flex-direction:column; gap:.2rem; flex:1; }
.bil-table .t-status{
  position:absolute; top:18px; left:20px; z-index:2;
  font-size:.62rem; font-weight:800; letter-spacing:.1em;
  padding:.28rem .55rem; border-radius:999px;
  background:rgba(0,0,0,.35); color:#d8efe4; backdrop-filter:blur(4px);
}
.bil-table.free .t-status{ background:rgba(21,154,90,.35); color:#b7f5d0; }
.bil-table.busy .t-status{ background:rgba(212,160,23,.35); color:#ffe9a8; }
.bil-table.ending{
  border-color:rgba(198,40,40,.65);
  animation:bilPulse 1.2s ease infinite;
}
.bil-table.ending .t-status{ background:rgba(198,40,40,.45); color:#ffd0d0; }
.bil-table .t-name{
  font-family:var(--bil-display); font-size:1.2rem; font-weight:800;
  color:#f4fff9; letter-spacing:-.03em; line-height:1.1;
}
.bil-table .t-type{
  font-size:.68rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(200,230,216,.65);
}
.bil-table .t-rate{ font-size:.82rem; font-weight:700; color:#f0d078; margin-top:.15rem; }
.bil-table .t-guest{ font-size:.76rem; font-weight:600; color:rgba(244,255,249,.88); }
.bil-table .t-bar{
  margin-top:.45rem; height:4px; border-radius:999px; background:rgba(255,255,255,.12); overflow:hidden;
}
.bil-table .t-bar > span{
  display:block; height:100%; border-radius:inherit;
  background:linear-gradient(90deg, #e8b84a, #fff3c4);
}
.bil-table.ending .t-bar > span{ background:linear-gradient(90deg, #ef4444, #fca5a5); }
.bil-empty-floor{
  grid-column:1 / -1; text-align:center; padding:2.5rem 1rem;
  color:rgba(244,255,249,.8); font-weight:600;
}
.bil-empty-floor a{ color:#f0d078; }

/* Lower boards */
.bil-boards{
  display:grid; grid-template-columns:1.4fr .9fr; gap:.85rem;
}
.bil-board{
  background:var(--bil-white);
  border:1px solid var(--bil-line);
  border-radius:22px;
  padding:1rem 1rem .4rem;
  animation:bilRise .55s ease both;
}
.bil-board:nth-child(2){ animation-delay:.06s; }
.bil-row{
  display:grid; grid-template-columns:minmax(0,1fr) auto;
  gap:.75rem; align-items:center;
  padding:.9rem 0; border-bottom:1px solid var(--bil-line);
}
.bil-row:last-of-type{ border-bottom:0; }
.bil-row .title{ font-weight:700; font-size:.92rem; letter-spacing:-.01em; }
.bil-row .meta{ color:var(--bil-muted); font-size:.76rem; font-weight:500; margin-top:.18rem; }
.bil-row .right{ display:flex; flex-direction:column; align-items:flex-end; gap:.35rem; }
.bil-chip{
  display:inline-flex; align-items:center;
  font-size:.66rem; font-weight:800; letter-spacing:.04em;
  padding:.22rem .55rem; border-radius:999px;
}
.bil-chip--ok{ background:#e6f7ee; color:#0f6b3c; }
.bil-chip--warn{ background:#fff0e0; color:#9a4a0c; }
.bil-chip--info{ background:#e8f0ff; color:#1e4a9a; }
.bil-chip--mute{ background:#eef1ef; color:#526059; }
.bil-chip--danger{ background:#fde8e8; color:#9b1c1c; }
.bil-link{ color:var(--bil-felt-2); font-weight:700; font-size:.78rem; text-decoration:none; }
.bil-link:hover{ color:var(--bil-gold-deep); }
.bil-empty{
  display:grid; place-items:center; text-align:center;
  padding:1.6rem 1rem 1.8rem; color:var(--bil-muted); font-weight:500; font-size:.88rem;
}
.bil-empty .ico{
  width:48px; height:48px; border-radius:14px; margin:0 auto .7rem;
  background:linear-gradient(160deg, #e8f5ef, #f4f1ea);
  border:1px solid var(--bil-line);
  display:grid; place-items:center; color:var(--bil-felt-2); font-size:1.1rem;
}
.bil-fab{
  display:none; position:fixed; right:1rem; bottom:1.1rem; z-index:40;
  width:58px; height:58px; border-radius:50%;
  background:linear-gradient(180deg, #e8b84a, var(--bil-gold-deep));
  color:#1a1204; border:0; cursor:pointer;
  box-shadow:0 12px 28px rgba(184,134,11,.4);
  align-items:center; justify-content:center; font-size:1.3rem;
  text-decoration:none;
}
.bil-fab:hover{ color:#1a1204; }

/* Shared pages */
.bil-page{ max-width:760px; margin:0 auto; }
.bil-page-wide{ max-width:1100px; margin:0 auto; }
.bil-card{
  background:var(--bil-white); border:1px solid var(--bil-line);
  border-radius:22px; padding:1.25rem;
}
.bil-card label{ font-weight:700; font-size:.8rem; color:#3d4f46; margin-bottom:.35rem; display:block; }
.bil-card .form-control,.bil-card .form-select{
  border-radius:12px; border-color:#d5ddd8; min-height:44px; background:#fff;
}
.bil-price{
  background:linear-gradient(135deg, #fff7e0, #fffcf7);
  border:1px solid #ebd59a; border-radius:16px; padding:1rem 1.1rem;
  font-family:var(--bil-display); font-size:1.4rem; font-weight:800; color:#8a6508;
}
.bil-pay-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(88px,1fr)); gap:.5rem; }
.bil-pay-grid label{
  margin:0; border:1px solid #d5ddd8; border-radius:14px; padding:.75rem .4rem;
  text-align:center; cursor:pointer; font-weight:800; font-size:.78rem; min-height:48px;
  display:flex; align-items:center; justify-content:center; background:#fff;
}
.bil-pay-grid input{ position:absolute; opacity:0; pointer-events:none; }
.bil-pay-grid label:has(input:checked){
  border-color:var(--bil-gold); background:#fff7e0; color:#8a6508;
}
.cust-results{
  position:absolute; z-index:20; left:0; right:0;
  background:#fff; border:1px solid #d5ddd8; border-radius:12px;
  max-height:200px; overflow:auto; display:none;
}
.cust-results button{
  display:block; width:100%; text-align:left; border:0; background:#fff;
  padding:10px 12px; border-bottom:1px solid #f0f3f1; min-height:44px; cursor:pointer;
}
.cust-results button:hover{ background:#fff7e0; }

.bil-alert{
  display:none; align-items:flex-start; gap:.75rem;
  margin-bottom:.9rem; padding:.95rem 1.05rem;
  border-radius:16px; background:#fff1f0; border:1px solid #ffc9c5; color:#8a1c1c;
  font-weight:600; font-size:.9rem;
}
.bil-alert.show{ display:flex; }

@keyframes bilRise{ from{ opacity:0; transform:translateY(10px);} to{ opacity:1; transform:none;} }
@keyframes bilBlink{ 50%{ opacity:.35; } }
@keyframes bilPulse{
  0%,100%{ box-shadow:0 0 0 0 rgba(198,40,40,0); }
  50%{ box-shadow:0 0 0 7px rgba(198,40,40,.18); }
}

@media (max-width:991.98px){
  .bil-kpis{ grid-template-columns:repeat(2,minmax(0,1fr)); }
  .bil-boards{ grid-template-columns:1fr; }
  .bil-cmd{ grid-template-columns:1fr; position:static; }
  .bil-actions{ justify-content:stretch; }
  .bil-actions .bil-btn{ flex:1 1 calc(50% - .45rem); }
  .bil-actions .bil-btn--primary{ flex:1 1 100%; }
}
@media (max-width:575.98px){
  .bil-actions .bil-btn--ghost, .bil-actions .bil-btn--felt{ display:none; }
  .bil-fab{ display:inline-flex; }
  .bil-cmd{ padding:.75rem; }
  .bil-mark{ width:48px; height:48px; }
}
@media (prefers-reduced-motion:reduce){
  .bil-kpi,.bil-stage,.bil-board,.bil-table.ending,.bil-live i{ animation:none !important; }
  .bil-btn:hover,.bil-table:hover{ transform:none; }
}
</style>
