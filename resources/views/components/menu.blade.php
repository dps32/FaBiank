@php
    $items = [];

    if (auth()->check()) { // el auth check supongo que servirá para comprobar que el usuario esté logueado
        $items[] = [
            'label' => 'Panel',
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
        ];

    } else {
        $items[] = [
            'label' => 'Inicio de sesión',
            'href' => route('login'),
            'active' => request()->routeIs('login'),
        ];

        $items[] = [
            'label' => 'Registro',
            'href' => route('register'),
            'active' => request()->routeIs('register'),
        ];
    }
@endphp

<nav class="menu" aria-label="Menu principal">
    @foreach ($items as $item)
        <a
            href="{{ $item['href'] }}"
            class="menu-link{{ $item['active'] ? ' is-active' : '' }}"
        >
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>


<script src="{{ asset('js/menu.js') }}"></script>