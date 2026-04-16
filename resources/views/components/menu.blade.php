@php
    $items = [];
    $user = auth()->user();

    if ($user) {
        $items[] = [
            'label' => 'Panel',
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
        ];

        $items[] = [
            'label' => 'Historial',
            'href' => route('history'),
            'active' => request()->routeIs('history'),
        ];

        $items[] = [
            'label' => 'Inversiones',
            'href' => route('investments'),
            'active' => request()->routeIs('investments'),
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

<div class="nav-wrapper">
    <nav class="menu" aria-label="Menu principal">
        <div class="menu-links">
            @foreach ($items as $item)
                <a
                    href="{{ $item['href'] }}"
                    class="menu-link{{ $item['active'] ? ' is-active' : '' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    @if (auth()->check())
        <div class="search-menu-button-container">
            <button id="searchUserButton" type="button" class="menu-search-button" aria-label="Buscar usuarios" title="Buscar usuario" aria-expanded="false" aria-controls="searchPanel">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </button>
        </div>
    @endif
</div>

@if (auth()->check())
    <x-search-users />
@endif

<script src="{{ asset('js/menu.js') }}" defer></script>