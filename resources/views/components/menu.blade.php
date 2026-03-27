@php
    $items = [];
    $user = auth()->user();

    if ($user) {
        $items[] = [
            'label' => 'Panel',
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
        ];

        if ($user->is_verified) {
            $items[] = [
                'label' => 'Historial',
                'href' => route('history'),
                'active' => request()->routeIs('history'),
            ];

            $items[] = [
                'label' => 'Invesiones',
                'href' => route('investments'),
                'active' => request()->routeIs('investments'),
            ];
        }
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