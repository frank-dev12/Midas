window.Midas = {
    formatMoney(n) {
        return 'S/ ' + Number(n).toFixed(2);
    },
    beep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.connect(g);
            g.connect(ctx.destination);
            o.frequency.value = 880;
            g.gain.value = 0.08;
            o.start();
            setTimeout(() => { o.stop(); ctx.close(); }, 80);
        } catch (_) {}
    },
    getTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    },
    setTheme(theme) {
        const next = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        try { localStorage.setItem('midas-theme', next); } catch (_) {}
        const meta = document.getElementById('meta-theme-color');
        if (meta) {
            meta.setAttribute('content', next === 'dark' ? '#0b0d14' : '#eef2f7');
        }
    },
    toggleTheme() {
        this.setTheme(this.getTheme() === 'dark' ? 'light' : 'dark');
    }
};

document.querySelectorAll('.theme-toggle').forEach((btn) => {
    btn.addEventListener('click', () => Midas.toggleTheme());
});

// Sync meta theme color on load
Midas.setTheme(Midas.getTheme());
