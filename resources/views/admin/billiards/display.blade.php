<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $company }} — Billiards Display</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #07140f;
            --cream: #fff7ed;
            --muted: #9ca89f;
            --amber: #e8a317;
            --amber2: #c45c12;
            --green: #34d399;
            --red: #f87171;
            --felt: #0a3326;
            --felt-2: #0f4a37;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; color: var(--cream);
            font-family: Outfit, system-ui, sans-serif;
            background:
                radial-gradient(ellipse 55% 45% at 50% -5%, rgba(232,163,23,.16), transparent 55%),
                radial-gradient(ellipse 40% 35% at 100% 80%, rgba(15,74,55,.35), transparent 50%),
                var(--bg);
            overflow-x: hidden;
        }

        /* App icon (premium) + 8-ball (playing) */
        .app-icon {
            width: 56px; height: 56px; flex-shrink: 0;
            border-radius: 14px;
            background: url('{{ asset('images/billiards-icon.png') }}') center / cover no-repeat;
            box-shadow:
                0 0 0 2px rgba(232,163,23,.35),
                0 10px 24px rgba(0,0,0,.45);
        }
        .app-icon--pulse { animation: iconPulse 2s ease-in-out infinite; }
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 2px rgba(232,163,23,.35), 0 10px 24px rgba(0,0,0,.45); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 4px rgba(232,163,23,.2), 0 14px 28px rgba(52,211,153,.25); }
        }
        .eight {
            width: 52px; height: 52px; flex-shrink: 0;
            border-radius: 50%;
            background: url('{{ asset('images/billiards-8ball.png') }}') center / cover no-repeat;
            box-shadow:
                0 0 0 2px rgba(232,163,23,.35),
                0 8px 20px rgba(0,0,0,.45),
                inset 0 1px 0 rgba(255,255,255,.15);
        }
        .eight--lg { width: 88px; height: 88px; }
        .eight--sm { width: 36px; height: 36px; }
        .eight--spin { animation: ballSpin 2.8s linear infinite; }
        .eight--roll { animation: ballRoll 1.8s ease-in-out infinite; }
        .eight--idle { animation: ballIdle 4s ease-in-out infinite; }
        @keyframes ballSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes ballRoll {
            0%, 100% { transform: translateX(0) rotate(0deg); }
            50% { transform: translateX(8px) rotate(180deg); }
        }
        @keyframes ballIdle {
            0%, 100% { transform: translateY(0) rotate(-6deg); }
            50% { transform: translateY(-6px) rotate(6deg); }
        }

        .top {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid rgba(232,163,23,.4);
            background:
                linear-gradient(180deg, rgba(255,255,255,.04), transparent),
                linear-gradient(135deg, #0c1f18, #14110f 60%);
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap;
            box-shadow: 0 12px 28px rgba(0,0,0,.25);
        }
        .brand { display: flex; align-items: center; gap: .9rem; }
        .top h1 {
            margin: 0; font-size: clamp(1.35rem, 2.5vw, 1.7rem);
            font-weight: 800; letter-spacing: -.02em;
        }
        .top .sub { color: var(--muted); font-size: .88rem; font-weight: 600; margin-top: .15rem; }
        .live-pill {
            display: inline-flex; align-items: center; gap: .4rem;
            margin-left: .5rem; padding: .2rem .55rem; border-radius: 999px;
            font-size: .68rem; font-weight: 800; letter-spacing: .08em;
            background: rgba(16,185,129,.2); color: #6ee7b7; vertical-align: middle;
        }
        .live-pill i {
            width: 7px; height: 7px; border-radius: 50%; background: currentColor;
            animation: liveBlink 1.2s ease infinite;
        }
        @keyframes liveBlink { 50% { opacity: .25; } }
        .clock {
            font-variant-numeric: tabular-nums;
            font-size: clamp(1.35rem, 3vw, 1.85rem);
            font-weight: 800; color: #f0d078; letter-spacing: .02em;
        }

        .wrap { padding: 1.25rem 1.5rem 1.75rem; }
        .stats {
            display: grid; grid-template-columns: repeat(3, minmax(0,1fr));
            gap: .85rem; margin-bottom: 1.15rem;
        }
        .stat {
            border-radius: 16px; padding: .9rem 1.1rem;
            background: rgba(10,51,38,.45);
            border: 1px solid rgba(255,247,237,.08);
            display: flex; align-items: center; gap: .85rem;
        }
        .stat .n {
            font-size: 1.75rem; font-weight: 800; letter-spacing: -.03em; line-height: 1;
        }
        .stat .t {
            font-size: .72rem; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: var(--muted);
        }
        .stat.play .n { color: var(--green); }
        .stat.book .n { color: #fbbf24; }
        .stat.free .n { color: #93c5fd; }

        .cols {
            display: grid; grid-template-columns: 1.35fr 1fr;
            gap: 1.15rem; min-height: calc(100vh - 220px);
        }
        .panel {
            border-radius: 22px; padding: 0;
            background: linear-gradient(165deg, rgba(15,74,55,.35), rgba(7,20,15,.95));
            border: 1px solid rgba(255,247,237,.08);
            display: flex; flex-direction: column;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(0,0,0,.2);
        }
        .panel-head {
            padding: 1rem 1.2rem;
            display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            border-bottom: 1px solid rgba(255,247,237,.08);
            background: rgba(0,0,0,.2);
        }
        .panel-head h2 {
            margin: 0; font-size: 1.15rem; font-weight: 800;
            display: flex; align-items: center; gap: .55rem;
        }
        .panel.play .panel-head h2 { color: var(--green); }
        .panel.next .panel-head h2 { color: #fbbf24; }
        .panel-body {
            padding: 1rem 1.1rem 1.2rem;
            flex: 1; display: flex; flex-direction: column; gap: .85rem;
            background:
                radial-gradient(ellipse 80% 60% at 50% 0%, rgba(255,255,255,.04), transparent 55%),
                linear-gradient(180deg, transparent, rgba(0,0,0,.15));
        }

        .card {
            position: relative;
            border-radius: 18px; padding: 1rem 1.15rem;
            background: linear-gradient(145deg, rgba(20,40,32,.9), rgba(12,24,20,.95));
            border: 1px solid rgba(255,247,237,.1);
            display: grid; grid-template-columns: auto 1fr auto;
            gap: .9rem; align-items: center;
            overflow: hidden;
        }
        .card.playing::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent, rgba(52,211,153,.08), transparent);
            animation: playSweep 2.4s ease-in-out infinite;
            pointer-events: none;
        }
        @keyframes playSweep {
            0% { transform: translateX(-40%); opacity: 0; }
            40% { opacity: 1; }
            100% { transform: translateX(40%); opacity: 0; }
        }
        .card.ending {
            border-color: rgba(239,68,68,.5);
            animation: pulseRed 1.4s ease-in-out infinite;
        }
        .card.overtime {
            border-color: rgba(239,68,68,.75);
            background: linear-gradient(160deg, #3f1515, #1a1210);
        }
        .table-name { font-size: clamp(1.25rem, 2.5vw, 1.55rem); font-weight: 800; letter-spacing: -.02em; line-height: 1.1; }
        .meta { color: var(--muted); font-size: .9rem; font-weight: 600; margin-top: .3rem; }
        .run-tag {
            display: inline-flex; align-items: center; gap: .35rem;
            margin-top: .45rem; font-size: .68rem; font-weight: 800;
            letter-spacing: .1em; text-transform: uppercase; color: #6ee7b7;
        }
        .run-tag .dot {
            width: 7px; height: 7px; border-radius: 50%; background: currentColor;
            animation: liveBlink 1s ease infinite;
        }
        .timer {
            font-variant-numeric: tabular-nums;
            font-size: clamp(1.7rem, 4vw, 2.55rem);
            font-weight: 800; letter-spacing: .02em;
            color: var(--green); text-align: right; line-height: 1;
        }
        .timer.soon { color: #fbbf24; }
        .timer.over { color: var(--red); }
        .timer-label {
            text-align: right; font-size: .7rem; font-weight: 700;
            letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-top: .3rem;
        }

        .empty {
            color: var(--muted); padding: 2.5rem 1rem; text-align: center;
            display: flex; flex-direction: column; align-items: center; gap: .85rem;
            flex: 1; justify-content: center;
        }
        .empty p { margin: 0; font-weight: 600; font-size: 1rem; }

        .free-bar {
            margin-top: 1.15rem; border-radius: 20px; padding: 1rem 1.25rem;
            background: linear-gradient(165deg, rgba(15,74,55,.3), rgba(7,20,15,.9));
            border: 1px solid rgba(255,247,237,.08);
        }
        .free-bar h3 {
            margin: 0 0 .85rem; font-size: 1rem; font-weight: 800; color: var(--muted);
            display: flex; align-items: center; gap: .45rem;
        }
        .chip-row { display: flex; flex-wrap: wrap; gap: .65rem; }
        .chip {
            min-width: 108px; padding: .85rem 1rem; border-radius: 14px; text-align: center;
            background: rgba(20,40,32,.8); border: 1px solid rgba(52,211,153,.2);
        }
        .chip .n { font-size: 1.1rem; font-weight: 800; color: #a7f3d0; }
        .chip .t {
            font-size: .7rem; font-weight: 700; color: var(--muted);
            letter-spacing: .06em; text-transform: uppercase; margin-top: .15rem;
        }

        @keyframes pulseRed {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.35); }
            50% { box-shadow: 0 0 0 10px rgba(239,68,68,0); }
        }
        @media (max-width: 900px) {
            .cols, .stats { grid-template-columns: 1fr; }
            .card { grid-template-columns: auto 1fr; }
            .card .timer-col { grid-column: 1 / -1; text-align: left; }
            .timer, .timer-label { text-align: left; }
        }
        @media (prefers-reduced-motion: reduce) {
            .eight--spin, .eight--roll, .eight--idle, .app-icon--pulse,
            .card.playing::before, .card.ending, .live-pill i, .run-tag .dot { animation: none !important; }
        }
    </style>
</head>
<body>
<header class="top">
    <div class="brand">
        <div class="app-icon" id="brandIcon" aria-hidden="true"></div>
        <div>
            <h1>
                Billiards Status
                <span class="live-pill" id="livePill" hidden><i></i> LIVE</span>
            </h1>
            <div class="sub">{{ $company }} · live countdown while tables are playing</div>
        </div>
    </div>
    <div class="clock" id="clock"></div>
</header>

<div class="wrap">
    <section class="stats" aria-label="Floor counts">
        <article class="stat play">
            <div class="eight eight--sm" id="statPlayBall" aria-hidden="true"></div>
            <div>
                <div class="n" id="statPlay">0</div>
                <div class="t">Playing</div>
            </div>
        </article>
        <article class="stat book">
            <div>
                <div class="n" id="statBook">0</div>
                <div class="t">Booked</div>
            </div>
        </article>
        <article class="stat free">
            <div>
                <div class="n" id="statFree">0</div>
                <div class="t">Free</div>
            </div>
        </article>
    </section>

    <div class="cols">
        <section class="panel play">
            <div class="panel-head">
                <h2><i class="fas fa-play"></i> Playing <span id="playCount" style="opacity:.65;font-weight:700"></span></h2>
                <div class="eight eight--sm eight--roll" id="playBall" hidden aria-hidden="true"></div>
            </div>
            <div id="playingList" class="panel-body"></div>
        </section>
        <section class="panel next">
            <div class="panel-head">
                <h2><i class="fas fa-clock"></i> Booked <span id="bookCount" style="opacity:.65;font-weight:700"></span></h2>
            </div>
            <div id="bookedList" class="panel-body"></div>
        </section>
    </div>

    <section class="free-bar">
        <h3><i class="fas fa-check-circle"></i> Free tables</h3>
        <div id="freeList" class="chip-row"></div>
    </section>
</div>

<script>
(function () {
    const feedUrl = @json($feedUrl);
    const alertMins = @json((int) $endAlertMinutes);
    const eightUrl = @json(asset('images/billiards-8ball.png'));
    let offsetMs = 0;
    let playing = [];
    let booked = [];
    let free = [];

    function nowMs() { return Date.now() + offsetMs; }

    function fmt(secs) {
        const over = secs < 0;
        const abs = Math.abs(Math.floor(secs));
        const h = Math.floor(abs / 3600);
        const m = Math.floor((abs % 3600) / 60);
        const s = abs % 60;
        const pad = (n) => String(n).padStart(2, '0');
        const body = h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
        return over ? `+${body}` : body;
    }

    function labelFor(secs, endingSoon) {
        if (secs < 0) return 'Overtime';
        if (endingSoon) return 'Ending soon';
        return 'Time left';
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function cardHtml(row, withLiveTimer) {
        const secs = withLiveTimer && row.ends_ms
            ? Math.floor((row.ends_ms - nowMs()) / 1000)
            : row.seconds_left;
        const ending = secs >= 0 && secs <= (alertMins * 60);
        const over = secs < 0;
        const cls = [
            withLiveTimer ? 'playing' : '',
            over ? 'overtime' : (ending ? 'ending' : ''),
        ].filter(Boolean).join(' ');
        const tCls = over ? 'over' : (ending ? 'soon' : '');
        const start = row.starts_at ? new Date(row.starts_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '';
        const end = row.ends_at ? new Date(row.ends_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '';

        return `<article class="card ${cls}" data-id="${row.id}">
            <div class="eight eight--sm ${withLiveTimer ? 'eight--spin' : 'eight--idle'}" style="background-image:url('${eightUrl}')" aria-hidden="true"></div>
            <div>
                <div class="table-name">${escapeHtml(row.table)}</div>
                <div class="meta">${escapeHtml(row.customer)} · ${escapeHtml(row.number)} · ${start}–${end}</div>
                ${withLiveTimer ? '<div class="run-tag"><span class="dot"></span> Running · in play</div>' : ''}
            </div>
            <div class="timer-col">
                <div class="timer ${tCls}" data-ends="${row.ends_ms || ''}">${withLiveTimer ? fmt(secs) : (start || '—')}</div>
                <div class="timer-label">${withLiveTimer ? labelFor(secs, ending) : 'Starts'}</div>
            </div>
        </article>`;
    }

    function bookedHtml(row) {
        const start = row.starts_at ? new Date(row.starts_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '';
        const end = row.ends_at ? new Date(row.ends_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '';
        return `<article class="card">
            <div class="eight eight--sm eight--idle" style="background-image:url('${eightUrl}')" aria-hidden="true"></div>
            <div>
                <div class="table-name">${escapeHtml(row.table)}</div>
                <div class="meta">${escapeHtml(row.customer)} · ${escapeHtml(row.number)} · ${row.hours}h</div>
            </div>
            <div class="timer-col">
                <div class="timer soon" style="font-size:1.55rem">${start}</div>
                <div class="timer-label">Until ${end}</div>
            </div>
        </article>`;
    }

    function setBallMotion(hasPlaying) {
        const brand = document.getElementById('brandIcon');
        const playBall = document.getElementById('playBall');
        const livePill = document.getElementById('livePill');
        const statBall = document.getElementById('statPlayBall');
        brand.classList.toggle('app-icon--pulse', hasPlaying);
        playBall.hidden = !hasPlaying;
        livePill.hidden = !hasPlaying;
        statBall.classList.toggle('eight--spin', hasPlaying);
        statBall.classList.toggle('eight--idle', !hasPlaying);
    }

    function render() {
        const playEl = document.getElementById('playingList');
        const bookEl = document.getElementById('bookedList');
        const freeEl = document.getElementById('freeList');
        document.getElementById('playCount').textContent = playing.length ? `(${playing.length})` : '';
        document.getElementById('bookCount').textContent = booked.length ? `(${booked.length})` : '';
        document.getElementById('statPlay').textContent = playing.length;
        document.getElementById('statBook').textContent = booked.length;
        document.getElementById('statFree').textContent = free.length;
        setBallMotion(playing.length > 0);

        playEl.innerHTML = playing.length
            ? playing.map(r => cardHtml(r, true)).join('')
            : `<div class="empty">
                <div class="eight eight--lg eight--idle" style="background-image:url('${eightUrl}')"></div>
                <p>No tables playing</p>
               </div>`;

        bookEl.innerHTML = booked.length
            ? booked.map(bookedHtml).join('')
            : `<div class="empty"><p>No upcoming bookings</p></div>`;

        freeEl.innerHTML = free.length
            ? free.map(t => `<div class="chip"><div class="n">${escapeHtml(t.name)}</div><div class="t">${escapeHtml(t.type)}</div></div>`).join('')
            : '<div class="empty" style="padding:1rem;flex:none">All tables in use</div>';
    }

    function tickTimers() {
        document.querySelectorAll('#playingList .timer[data-ends]').forEach(el => {
            const ends = Number(el.getAttribute('data-ends'));
            if (!ends) return;
            const secs = Math.floor((ends - nowMs()) / 1000);
            const ending = secs >= 0 && secs <= (alertMins * 60);
            const over = secs < 0;
            el.textContent = fmt(secs);
            el.classList.toggle('soon', ending && !over);
            el.classList.toggle('over', over);
            const card = el.closest('.card');
            if (card) {
                card.classList.toggle('ending', ending && !over);
                card.classList.toggle('overtime', over);
            }
            const label = el.parentElement.querySelector('.timer-label');
            if (label) label.textContent = labelFor(secs, ending);
        });
    }

    function tickClock() {
        document.getElementById('clock').textContent = new Date().toLocaleTimeString([], {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }

    async function loadFeed() {
        try {
            const r = await fetch(feedUrl, { headers: { 'Accept': 'application/json' } });
            if (!r.ok) return;
            const data = await r.json();
            if (data.server_ms) offsetMs = data.server_ms - Date.now();
            playing = data.playing || [];
            booked = data.booked || [];
            free = data.free || [];
            render();
        } catch (e) {}
    }

    tickClock();
    setInterval(tickClock, 1000);
    setInterval(tickTimers, 1000);
    loadFeed();
    setInterval(loadFeed, 8000);
})();
</script>
</body>
</html>
