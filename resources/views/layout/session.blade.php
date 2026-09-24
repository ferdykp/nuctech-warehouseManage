<script>
(() => {
    const loginUrl = @json(route('login'));
    const authenticated = @json(auth()->check());
    const logoutKey = 'workforce.logout';
    window.addEventListener('pageshow', event => {
        if (event.persisted) window.location.reload();
    });
    if (!authenticated) return;

    let expiresAt = {{ ((int) session('last_activity_at', now()->timestamp) + max(1, (int) config('session.idle_timeout', 30)) * 60) * 1000 }};
    let lastSent = 0;
    let timer;
    let checking = false;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    function exit() { window.location.replace(loginUrl); }
    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(checkSession, Math.max(1000, expiresAt - Date.now()));
    }
    async function checkSession() {
        if (checking) return;
        checking = true;
        try {
            const response = await fetch(@json(route('session.status')), {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            if ([401, 419].includes(response.status)) return exit();
            if (response.ok) expiresAt = (await response.json()).expires_at;
        } catch (_) { /* Retry when connectivity returns. */ }
        finally { checking = false; clearTimeout(timer); timer = setTimeout(checkSession, Math.max(10000, expiresAt - Date.now())); }
    }
    async function activity() {
        if (Date.now() >= expiresAt) return checkSession();
        if (Date.now() - lastSent < 60000) return;
        lastSent = Date.now();
        try {
            const response = await fetch(@json(route('session.activity')), {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}});
            if ([401, 419].includes(response.status)) return exit();
            if (response.ok) await checkSession();
        } catch (_) { lastSent = 0; }
    }
    ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach(name => document.addEventListener(name, activity, {passive: true}));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) checkSession(); });
    window.addEventListener('storage', event => { if (event.key === logoutKey) exit(); });
    document.querySelectorAll('form[action="' + @json(route('auth.logout')) + '"]').forEach(form => {
        form.addEventListener('submit', () => { try { localStorage.setItem(logoutKey, String(Date.now())); } catch (_) {} });
    });
    schedule();
})();
</script>
