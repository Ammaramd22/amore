<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install QRPOS — cPanel Setup</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1c1410;
            --muted: #78716c;
            --line: #e7e5e4;
            --amber: #f59e0b;
            --amber-deep: #d97706;
            --ok: #15803d;
            --bad: #b91c1c;
            --card: rgba(255,255,255,.92);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh;
            font-family: 'DM Sans', system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 600px at 10% -10%, #fdba74 0%, transparent 55%),
                radial-gradient(900px 500px at 100% 0%, #fed7aa 0%, transparent 50%),
                linear-gradient(165deg, #fff7ed 0%, #fafaf9 40%, #f5f5f4 100%);
        }
        .shell { max-width: 920px; margin: 0 auto; padding: 2rem 1.25rem 3rem; }
        .brand { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.5rem; }
        .brand-mark {
            width: 48px; height: 48px; border-radius: 14px;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            display: grid; place-items: center; color: #fff; font-weight: 800; font-size: 1.1rem;
            box-shadow: 0 10px 30px rgba(234,88,12,.35);
        }
        .brand h1 { margin: 0; font-family: Fraunces, Georgia, serif; font-size: 1.55rem; letter-spacing: -.02em; }
        .brand p { margin: .15rem 0 0; color: var(--muted); font-size: .88rem; }
        .card {
            background: var(--card); backdrop-filter: blur(10px);
            border: 1px solid var(--line); border-radius: 22px;
            box-shadow: 0 20px 50px rgba(28,20,16,.08);
            overflow: hidden;
        }
        .steps {
            display: flex; gap: .35rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--line);
            overflow-x: auto;
        }
        .step-pill {
            flex: 0 0 auto; padding: .4rem .75rem; border-radius: 999px;
            font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
            color: var(--muted); background: #f5f5f4; border: 1px solid transparent;
        }
        .step-pill.on { background: #fff7ed; color: var(--amber-deep); border-color: #fdba74; }
        .step-pill.done { background: #ecfdf5; color: var(--ok); }
        .pane { display: none; padding: 1.5rem 1.35rem 1.75rem; }
        .pane.on { display: block; }
        h2 { margin: 0 0 .35rem; font-family: Fraunces, Georgia, serif; font-size: 1.45rem; }
        .lead { margin: 0 0 1.25rem; color: var(--muted); line-height: 1.5; }
        .grid { display: grid; gap: .85rem; }
        .grid.two { grid-template-columns: 1fr 1fr; }
        @media (max-width: 720px) { .grid.two { grid-template-columns: 1fr; } }
        label { display: block; font-size: .78rem; font-weight: 700; color: #57534e; margin-bottom: .3rem; }
        input[type=text], input[type=password], input[type=email], input[type=url], input[type=number] {
            width: 100%; padding: .7rem .85rem; border-radius: 12px; border: 1px solid var(--line);
            font: inherit; background: #fff;
        }
        input:focus { outline: 2px solid #fdba74; border-color: #f59e0b; }
        .check {
            display: flex; align-items: flex-start; gap: .65rem;
            padding: .75rem .9rem; border-radius: 12px; border: 1px solid var(--line); background: #fff;
        }
        .req { display: flex; justify-content: space-between; gap: 1rem; padding: .55rem 0; border-bottom: 1px dashed #e7e5e4; font-size: .88rem; }
        .req:last-child { border: 0; }
        .badge { font-size: .7rem; font-weight: 800; padding: .2rem .5rem; border-radius: 999px; }
        .badge.ok { background: #dcfce7; color: var(--ok); }
        .badge.bad { background: #fee2e2; color: var(--bad); }
        .badge.opt { background: #f5f5f4; color: var(--muted); }
        .actions { display: flex; justify-content: space-between; gap: .75rem; margin-top: 1.4rem; flex-wrap: wrap; }
        .btn {
            border: 0; border-radius: 12px; padding: .75rem 1.15rem; font: inherit; font-weight: 700;
            cursor: pointer; display: inline-flex; align-items: center; gap: .45rem;
        }
        .btn-ghost { background: #f5f5f4; color: #44403c; }
        .btn-primary { background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; box-shadow: 0 8px 20px rgba(234,88,12,.28); }
        .btn-primary:disabled { opacity: .55; cursor: not-allowed; }
        .alert { padding: .85rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .9rem; }
        .alert.err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert.ok { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
        .hint { font-size: .78rem; color: var(--muted); margin-top: .25rem; }
        .finish { text-align: center; padding: 1rem 0 .5rem; }
        .finish .big { font-family: Fraunces, Georgia, serif; font-size: 1.8rem; margin: .5rem 0; }
        footer { text-align: center; margin-top: 1.25rem; color: var(--muted); font-size: .8rem; }
    </style>
</head>
<body>
<div class="shell">
    <div class="brand">
        <div class="brand-mark">Q</div>
        <div>
            <h1>QRPOS Installer</h1>
            <p>cPanel / shared hosting setup · By Avenque</p>
        </div>
    </div>

    <div class="card">
        <div class="steps" id="stepPills">
            <span class="step-pill on" data-step="0">Welcome</span>
            <span class="step-pill" data-step="1">Server</span>
            <span class="step-pill" data-step="2">Database</span>
            <span class="step-pill" data-step="3">Admin</span>
            <span class="step-pill" data-step="4">Options</span>
            <span class="step-pill" data-step="5">Finish</span>
        </div>

        <div id="flash"></div>

        <section class="pane on" data-pane="0">
            <h2>Welcome</h2>
            <p class="lead">This wizard configures QRPOS on shared hosting: checks PHP extensions, writes <code>.env</code>, runs migrations, and creates your admin login. Point the domain document root to the <strong>public</strong> folder (or use the root <code>.htaccess</code> we ship).</p>
            <ul class="lead" style="padding-left:1.1rem;">
                <li>Create a MySQL database + user in cPanel (or let the installer try CREATE)</li>
                <li>Upload the project files with <code>vendor/</code> included (or run composer on SSH)</li>
                <li>After install, open Settings → Guide (English / தமிழ் / සිංහල)</li>
            </ul>
            <div class="actions">
                <span></span>
                <button type="button" class="btn btn-primary" onclick="go(1)">Check server →</button>
            </div>
        </section>

        <section class="pane" data-pane="1">
            <h2>Server requirements</h2>
            <p class="lead">Required items must pass. Optional items improve SMS/WhatsApp and packaging.</p>
            <div id="reqList">
                @foreach($requirements as $r)
                <div class="req">
                    <div>
                        <strong>{{ $r['name'] }}</strong>
                        <div class="hint">{{ $r['hint'] }}</div>
                    </div>
                    @if($r['ok'])
                        <span class="badge ok">OK</span>
                    @elseif($r['required'])
                        <span class="badge bad">Missing</span>
                    @else
                        <span class="badge opt">Optional</span>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" onclick="go(0)">Back</button>
                <button type="button" class="btn btn-primary" id="btnReqNext" @disabled(!$pass)>Continue →</button>
            </div>
        </section>

        <section class="pane" data-pane="2">
            <h2>Database</h2>
            <p class="lead">Use the database name/user from cPanel → MySQL Databases. Prefixes like <code>cpaneluser_respos</code> are common.</p>
            <div class="grid two">
                <div>
                    <label>Host</label>
                    <input type="text" id="db_host" value="127.0.0.1">
                </div>
                <div>
                    <label>Port</label>
                    <input type="number" id="db_port" value="3306">
                </div>
                <div>
                    <label>Database name</label>
                    <input type="text" id="db_database" placeholder="cpaneluser_qrpos" required>
                </div>
                <div>
                    <label>Username</label>
                    <input type="text" id="db_username" placeholder="cpaneluser_qrpos" required>
                </div>
                <div style="grid-column:1/-1">
                    <label>Password</label>
                    <input type="password" id="db_password" autocomplete="new-password">
                </div>
            </div>
            <div class="check" style="margin-top:1rem">
                <input type="checkbox" id="db_create" style="margin-top:.2rem">
                <div>
                    <strong>Try to create database</strong>
                    <div class="hint">Only works if MySQL user has CREATE privilege. On most cPanel plans, create the DB first in the panel.</div>
                </div>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" onclick="go(1)">Back</button>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <button type="button" class="btn btn-ghost" onclick="testDb()">Test connection</button>
                    <button type="button" class="btn btn-primary" onclick="go(3)">Continue →</button>
                </div>
            </div>
        </section>

        <section class="pane" data-pane="3">
            <h2>Admin account</h2>
            <p class="lead">Restaurant admin login (role: admin). You can change password anytime under Users.</p>
            <div class="grid two">
                <div>
                    <label>Business / restaurant name</label>
                    <input type="text" id="admin_business" value="My Restaurant">
                </div>
                <div>
                    <label>App URL</label>
                    <input type="url" id="app_url" value="{{ $suggestedUrl }}">
                </div>
                <div>
                    <label>Admin full name</label>
                    <input type="text" id="admin_name" value="Administrator">
                </div>
                <div>
                    <label>Admin email</label>
                    <input type="email" id="admin_email" placeholder="admin@yourdomain.com">
                </div>
                <div>
                    <label>Password</label>
                    <input type="password" id="admin_password" autocomplete="new-password">
                </div>
                <div>
                    <label>POS PIN (4 digits)</label>
                    <input type="text" id="admin_pin" value="1111" maxlength="8">
                </div>
                <div>
                    <label>Phone (optional)</label>
                    <input type="text" id="admin_phone" placeholder="07XXXXXXXX">
                </div>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" onclick="go(2)">Back</button>
                <button type="button" class="btn btn-primary" onclick="go(4)">Continue →</button>
            </div>
        </section>

        <section class="pane" data-pane="4">
            <h2>Install options</h2>
            <p class="lead">Choose demo sample data for testing, or a clean fresh start for the customer’s live business.</p>
            <div class="check">
                <input type="radio" name="mode" id="mode_demo" value="demo" style="margin-top:.25rem">
                <div>
                    <strong>Include demo data</strong>
                    <div class="hint">Sample categories/products for training and testing before handover.</div>
                </div>
            </div>
            <div class="check" style="margin-top:.65rem">
                <input type="radio" name="mode" id="mode_fresh" value="fresh" checked style="margin-top:.25rem">
                <div>
                    <strong>Fresh start (recommended for customers)</strong>
                    <div class="hint">No demo menu — empty business ready for their own products, tables, and staff. Guide stays in Settings (EN / TA / SI).</div>
                </div>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" onclick="go(3)">Back</button>
                <button type="button" class="btn btn-primary" id="btnInstall" onclick="runInstall()">Install QRPOS</button>
            </div>
        </section>

        <section class="pane" data-pane="5">
            <div class="finish">
                <div class="big">You’re ready</div>
                <p class="lead" id="finishMsg">Installation complete.</p>
                <p class="hint" id="finishCreds"></p>
                <a class="btn btn-primary" id="finishLogin" href="/login">Go to login</a>
            </div>
        </section>
    </div>
    <footer>QRPOS by Avenque · qrpos@avenque.io · 076 822 2201</footer>
</div>
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
let step = 0;
const passReqs = @json($pass);

function flash(msg, ok=false) {
    const el = document.getElementById('flash');
    el.innerHTML = msg ? `<div class="alert ${ok?'ok':'err'}" style="margin:1rem 1.35rem 0">${msg}</div>` : '';
}

function go(n) {
    if (n === 1 && !passReqs) flash('Fix required server items before continuing.');
    if (n > 1 && !passReqs) return;
    step = n;
    document.querySelectorAll('.pane').forEach(p => p.classList.toggle('on', Number(p.dataset.pane) === n));
    document.querySelectorAll('.step-pill').forEach(p => {
        const s = Number(p.dataset.step);
        p.classList.toggle('on', s === n);
        p.classList.toggle('done', s < n);
    });
    flash('');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('btnReqNext')?.addEventListener('click', () => go(2));

async function testDb() {
    flash('Testing…', true);
    const body = {
        host: db_host.value.trim(),
        port: Number(db_port.value || 3306),
        database: db_database.value.trim(),
        username: db_username.value.trim(),
        password: db_password.value,
        create_database: db_create.checked,
    };
    const r = await fetch('{{ route('install.db') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    });
    const data = await r.json();
    flash(data.message || (data.ok ? 'OK' : 'Failed'), !!data.ok);
}

async function runInstall() {
    const btn = document.getElementById('btnInstall');
    btn.disabled = true;
    btn.textContent = 'Installing…';
    flash('Writing .env, migrating database, creating admin…', true);

    const withDemo = document.getElementById('mode_demo').checked;
    const payload = {
        app: { name: admin_business.value.trim() || 'QRPOS', url: app_url.value.trim() },
        db: {
            host: db_host.value.trim(),
            port: Number(db_port.value || 3306),
            database: db_database.value.trim(),
            username: db_username.value.trim(),
            password: db_password.value,
            create_database: db_create.checked,
        },
        admin: {
            name: admin_name.value.trim(),
            email: admin_email.value.trim(),
            password: admin_password.value,
            phone: admin_phone.value.trim(),
            pin: admin_pin.value.trim() || '1111',
            business_name: admin_business.value.trim(),
        },
        with_demo: withDemo,
    };

    try {
        const r = await fetch('{{ route('install.run') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await r.json();
        if (!data.ok) {
            flash(data.message || 'Install failed');
            btn.disabled = false;
            btn.textContent = 'Install QRPOS';
            if (data.step === 'database') go(2);
            return;
        }
        document.getElementById('finishMsg').textContent = data.message || 'Installation complete.';
        document.getElementById('finishCreds').textContent = 'Login: ' + (data.email || '') + ' · open Guide in Settings after login (EN / தமிழ் / සිංහල).';
        document.getElementById('finishLogin').href = data.login_url || '/login';
        go(5);
    } catch (e) {
        flash(e.message || 'Network error');
        btn.disabled = false;
        btn.textContent = 'Install QRPOS';
    }
}
</script>
</body>
</html>
