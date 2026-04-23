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
            'active' => request()->routeIs('login') || request()->routeIs('two-factor.*'),
        ];

        $items[] = [
            'label' => 'Registro',
            'href' => route('register'),
            'active' => request()->routeIs('register'),
        ];
    }
@endphp

<div class="nav-wrapper">
    <nav id="mainMenu" class="menu">
        <div class="menu-links">
            @foreach ($items as $index => $item)
                <a
                    href="{{ $item['href'] }}"
                    class="menu-link{{ $item['active'] ? ' is-active' : '' }}"
                    style="transition-delay: {{ 0.05 + ($index * 0.03) }}s"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    @if (auth()->check())
        <div class="search-menu-button-container">
            <button id="searchUserButton" type="button" class="menu-search-button" title="Buscar usuario">
                <x-heroicon-o-magnifying-glass class="menu-search-icon" />
            </button>

            <div id="searchPanel" class="search-panel">
                <div class="search-input-wrapper">
                    <input
                        type="text"
                        id="searchInput"
                        class="search-input"
                        placeholder="Buscar usuario..."
                        autocomplete="off"
                    >
                </div>

                <div id="searchResults" class="search-results">
                    <p class="search-empty">Empieza a escribir para buscar...</p>
                </div>
            </div>

            <template id="searchActionSendIconTemplate">
                <x-heroicon-o-paper-airplane class="search-result-action-icon" />
            </template>

            <template id="searchActionReceiveIconTemplate">
                <x-heroicon-o-arrow-down-left class="search-result-action-icon" />
            </template>
        </div>
    @endif
</div>

<script>
(function() {
    window.addEventListener('load', function() {
        var menu = document.getElementById('mainMenu');
        if (menu) {
            menu.classList.add('loaded');
        }

        var searchButton = document.getElementById('searchUserButton');
        if (searchButton) {
            searchButton.classList.add('loaded');
        }
    });
})();
</script>

@if (auth()->check())
    <x-search-users />
@endif
