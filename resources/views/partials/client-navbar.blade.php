<nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0">
    <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center border-end px-4 px-lg-5">
        <h2 class="m-0 text-primary">SolarShare</h2>
    </a>
    <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto p-4 p-lg-0">
            <a href="{{ route('home') }}" class="nav-item nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('equipments.index') }}" class="nav-item nav-link {{ request()->routeIs('equipments.*') ? 'active' : '' }}">Équipements</a>
            <a href="{{ route('reviews.index') }}" class="nav-item nav-link {{ request()->routeIs('reviews.*') ? 'active' : '' }}">Avis</a>
            @guest
                <a href="{{ route('login') }}" class="nav-item nav-link {{ request()->routeIs('login') ? 'active' : '' }}">Login</a>
            @endguest
            @auth
                <a href="{{ route('rentals.index') }}" class="nav-item nav-link {{ request()->routeIs('rentals.*') ? 'active' : '' }}">Mes réservations</a>
                <a href="{{ route('reclamations.index') }}" class="nav-item nav-link {{ request()->routeIs('reclamations.*') ? 'active' : '' }}">Réclamations</a>
                @php($unreadIncidents = \App\Models\IncidentMessage::unreadCountFor(auth()->user()))
                <a href="{{ auth()->user()->isAdmin() ? route('admin.incidents.index') : route('incidents.index') }}" class="nav-item nav-link {{ request()->routeIs('incidents.*') ? 'active' : '' }}">{{ auth()->user()->isAdmin() ? 'Incidents' : 'Mes signalements' }}<span class="unread-badge" data-unread-badge title="Messages non lus" @if(!$unreadIncidents) hidden @endif>{{ $unreadIncidents }}</span></a>
                <span class="nav-item nav-link">{{ auth()->user()->name }}</span>
            @endauth
        </div>
        @guest
            <a href="{{ route('register') }}" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">Register<i class="fa fa-arrow-right ms-3"></i></a>
        @endguest
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-primary rounded-0 py-4 px-lg-5">Logout<i class="fa fa-sign-out-alt ms-3"></i></button>
            </form>
        @endauth
    </div>
</nav>
