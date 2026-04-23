(() => {
    if (!('navigation' in window)) return;

    const bar = document.createElement('div');
    bar.id = 'nav-progress';
    document.body.prepend(bar);

    let hideTimer = null;

    function show() {
        clearTimeout(hideTimer);
        bar.style.transition = 'none';
        bar.style.width = '0%';
        bar.style.opacity = '1';
        requestAnimationFrame(() => requestAnimationFrame(() => {
            bar.style.transition = 'width 12s cubic-bezier(0.1, 0.05, 0, 1)';
            bar.style.width = '85%';
        }));
    }

    function complete() {
        bar.style.transition = 'width 0.15s ease';
        bar.style.width = '100%';
        hideTimer = setTimeout(() => {
            bar.style.transition = 'opacity 0.3s ease';
            bar.style.opacity = '0';
        }, 150);
    }

    navigation.addEventListener('navigate', (e) => {
        if (!e.canIntercept || e.hashChange || e.downloadRequest) return;
        const dest = new URL(e.destination.url);
        if (dest.origin !== location.origin) return;
        show();
    });

    navigation.addEventListener('navigatesuccess', complete);
    navigation.addEventListener('navigateerror', complete);
})();
