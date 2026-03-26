// transicionar los botones
document.querySelectorAll('.menu-link').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault(); // bloqueamos el evento de navegar

        // le quitamos el activo a el botón de la página actual
        const activeLink = document.querySelector('.menu-link.is-active');
        if (activeLink)
            activeLink.classList.remove('is-active');

        // activamos el botón presionado
        const href = link.href;
        link.classList.add('is-active');

        const menu = document.querySelector('.menu');
        menu.classList.add('block');

        setTimeout(() => {
            link.blur(); // desfocusear para quitar el hover
        }, 10);

        setTimeout(() => {
            window.location.href = href; // navegar
        }, 300);

        // por si se vuelve a la página desbloqueamos el menú
        setTimeout(() => {
            menu.classList.remove('block');
        }, 400);
    });
});