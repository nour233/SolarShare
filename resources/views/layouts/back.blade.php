<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title') · SolarShare</title>

    <link
        href="{{ asset('front/css/bootstrap.min.css') }}"
        rel="stylesheet"
    >

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f4f6f3;
            color: #18342a;
            font-family: Arial, sans-serif;
        }

        .office {
            display: flex;
            min-height: 100vh;
        }

        .side {
            width: 240px;
            background: #17372c;
            color: white;
            padding: 30px 22px;
            flex-shrink: 0;
        }

        .side h2 {
            color: #bce69c;
            font-size: 27px;
            margin-bottom: 8px;
        }

        .side small {
            color: #96b5a6;
            font-size: 10px;
            letter-spacing: 2px;
        }

        .side nav {
            margin-top: 45px;
        }

        .side a {
            display: block;
            color: #cee1d7;
            padding: 14px;
            margin-bottom: 8px;
            border-radius: 10px;
            text-decoration: none;
        }

        .side a.active {
            background: #bce69c;
            color: #17372c;
            font-weight: bold;
        }

        .workspace {
            flex: 1;
            min-width: 0;
            padding: 32px 40px;
        }

        .office-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .office-top small {
            font-size: 11px;
            letter-spacing: 2px;
            color: #728677;
        }

        .office-top h1 {
            font-size: 30px;
            margin-top: 8px;
        }

        .surface {
            background: white;
            padding: 26px;
            border-radius: 16px;
            border: 1px solid #e5ebe3;
        }

        .btn-primary {
            background: #278658;
            border-color: #278658;
        }

        .table td {
            vertical-align: middle;
        }

        .thumb {
            width: 58px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
        }

        .form-control,
        .form-select {
            padding: 12px;
            background: #fafcf9;
            border-color: #dfe7dc;
        }

        .form-label {
            font-size: 13px;
            font-weight: bold;
        }

        .stat-box {
            padding: 25px;
            border-radius: 15px;
            background: white;
            border: 1px solid #e5ebe3;
        }

        .stat-box strong {
            display: block;
            font-size: 35px;
        }

        .office-banner {
            padding: 30px;
            background: #dfeecd;
            border-radius: 16px;
            margin-bottom: 25px;
        }

        .office-banner h2 {
            font-size: 27px;
            color: #17372c;
        }

        .gallery {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .gallery img {
            width: 110px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .actions form {
            margin: 0;
        }

        @media(max-width: 800px) {
            .office {
                display: block;
            }

            .side {
                width: 100%;
                padding: 20px;
            }

            .side nav {
                margin-top: 16px;
                display: flex;
                flex-wrap: wrap;
            }

            .side a {
                padding: 10px;
            }

            .workspace {
                padding: 22px;
            }

            .office-top {
                flex-wrap: wrap;
                gap: 12px;
            }
        }
    </style>

    <link
        href="{{ asset('front/css/admin.css') }}"
        rel="stylesheet"
    >
</head>


<body>

<div class="office">

    {{-- ========================================================= --}}
    {{-- SIDEBAR --}}
    {{-- ========================================================= --}}

    <aside class="side">

        <h2>☀ SolarShare</h2>

        <nav>

            {{-- Dashboard --}}
            <a
                class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                href="{{ route('admin.dashboard') }}"
            >
                <i class="fa fa-th-large me-2"></i>
                Tableau de bord
            </a>


            {{-- Equipments --}}
            <a
                class="{{ request()->routeIs('admin.equipments.*') ? 'active' : '' }}"
                href="{{ route('admin.equipments.index') }}"
            >
                <i class="fa fa-solar-panel me-2"></i>
                Équipements
            </a>


            {{-- Categories --}}
            <a
                class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                href="{{ route('admin.categories.index') }}"
            >
                <i class="fa fa-layer-group me-2"></i>
                Catégories
            </a>


            {{-- Rentals --}}
            <a
                class="{{ request()->routeIs('admin.rentals.*') ? 'active' : '' }}"
                href="{{ route('admin.rentals.index') }}"
            >
                <i class="fa fa-calendar-alt me-2"></i>
                Locations
            </a>


            {{-- ================================================= --}}
            {{-- ZIED - DELIVERIES --}}
            {{-- ================================================= --}}

            <a
                class="{{ request()->routeIs('admin.deliveries.*') ? 'active' : '' }}"
                href="{{ route('admin.deliveries.index') }}"
            >
                <i class="fa fa-truck me-2"></i>
                Livraisons
            </a>


            {{-- ================================================= --}}
            {{-- ZIED - PICKUP POINTS --}}
            {{-- ================================================= --}}

            <a
                class="{{ request()->routeIs('admin.pickup-points.*') ? 'active' : '' }}"
                href="{{ route('admin.pickup-points.index') }}"
            >
                <i class="fa fa-map-marker-alt me-2"></i>
                Points de retrait
            </a>


            {{-- Maintenance --}}
            <a
                class="{{ request()->routeIs('admin.maintenances.*') ? 'active' : '' }}"
                href="{{ route('admin.maintenances.index') }}"
            >
                <i class="fa fa-tools me-2"></i>
                Maintenance
            </a>


            {{-- Reclamations --}}
            <a
                class="{{ request()->routeIs('admin.reclamations.*') ? 'active' : '' }}"
                href="{{ route('admin.reclamations.index') }}"
            >
                <i class="fa fa-comments me-2"></i>
                Réclamations
            </a>


            {{-- Reputation --}}
            <a
                class="{{ request()->routeIs('admin.reputation.*') ? 'active' : '' }}"
                href="{{ route('admin.reputation.index') }}"
            >
                <i class="fa fa-chart-line me-2"></i>
                Réputation
            </a>


            {{-- Incidents --}}

            @php(
                $unreadIncidents =
                    \App\Models\IncidentMessage::unreadCountFor(
                        auth()->user()
                    )
            )

            <a
                class="{{ request()->routeIs('admin.incidents.*') ? 'active' : '' }}"
                href="{{ route('admin.incidents.index') }}"
            >
                <i class="fa fa-exclamation-triangle me-2"></i>

                Incidents

                <span
                    class="unread-badge"
                    data-unread-badge
                    title="Messages non lus"
                    @if(!$unreadIncidents) hidden @endif
                >
                    {{ $unreadIncidents }}
                </span>
            </a>


            {{-- Front office --}}
            <a href="{{ route('home') }}">
                <i class="fa fa-external-link-alt me-2"></i>
                Voir le site ↗
            </a>

        </nav>

    </aside>


    {{-- ========================================================= --}}
    {{-- MAIN WORKSPACE --}}
    {{-- ========================================================= --}}

    <main class="workspace">

        <nav
            class="office-top admin-navbar"
            aria-label="Navigation administrateur"
        >

            <div>
                <h1>@yield('title')</h1>
            </div>


            <div class="d-flex align-items-center gap-3">

                <span>
                    {{ auth()->user()->name }}
                </span>


                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="btn btn-outline-dark btn-sm"
                    >
                        Déconnexion
                    </button>

                </form>

            </div>

        </nav>


        {{-- Success message --}}
        @if(session('status'))

            <div
                class="alert alert-success"
                role="status"
            >
                {{ session('status') }}
            </div>

        @endif


        {{-- Validation errors --}}
        @if($errors->any())

            <div
                class="alert alert-danger"
                role="alert"
            >

                <ul class="mb-0">

                    @foreach($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        @yield('content')

    </main>

</div>


@include('incidents.partials.unread-poll')

</body>

</html>