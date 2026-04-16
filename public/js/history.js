(() => {
    const timeNodes = document.querySelectorAll('.history-item-time[data-occurred-at-utc]');

    if (!timeNodes.length) {
        return;
    }

    timeNodes.forEach((node) => {
        const utcIso = node.getAttribute('data-occurred-at-utc');
        if (!utcIso) {
            return;
        }

        const date = new Date(utcIso);
        if (Number.isNaN(date.getTime())) {
            return;
        }

        node.textContent = date.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    });
})();
