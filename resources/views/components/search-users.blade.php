<div id="searchPanel" class="search-panel" aria-hidden="true">
    <div class="search-input-wrapper">
        <input
            type="text"
            id="searchInput"
            class="search-input"
            placeholder="Buscar usuario..."
            aria-label="Buscar usuario"
            autocomplete="off"
        >
    </div>

    <div id="searchResults" class="search-results">
        <p class="search-empty">Empieza a escribir para buscar...</p>
    </div>
</div>

<script src="{{ asset('js/search-users.js') }}" defer></script>
