{{-- Barra de navegación. Los enlaces se adaptan al perfil: visitante, viajero o administrador. --}}
<header>
    <nav class="navbar navbar-expand-lg navbar-dark va-navbar shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
                <i class="bi bi-bus-front-fill fs-4" aria-hidden="true"></i>
                <span>VíaAliwén</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                    aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link @if (request()->routeIs('home')) active @endif" href="{{ route('home') }}">Inicio</a>
                    </li>
                    @if (Route::has('destinos.index'))
                        <li class="nav-item">
                            <a class="nav-link @if (request()->routeIs('destinos.*')) active @endif" href="{{ route('destinos.index') }}">Destinos</a>
                        </li>
                    @endif
                    @if (Route::has('viajes.index'))
                        <li class="nav-item">
                            <a class="nav-link @if (request()->routeIs('viajes.*')) active @endif" href="{{ route('viajes.index') }}">Viajes</a>
                        </li>
                    @endif
                </ul>

                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}"><i class="bi bi-person-circle me-1"></i>Ingresar</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-success btn-sm px-3" href="{{ route('registro') }}">Crear cuenta</a>
                        </li>
                    @endguest

                    @auth
                        @if (auth()->user()->esAdmin())
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('admin.*')) active @endif" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-speedometer2 me-1"></i>Panel administrativo
                                </a>
                            </li>
                        @else
                            @if (Route::has('carrito.index'))
                                <li class="nav-item">
                                    @php $enCarrito = count(session('carrito', [])); @endphp
                                    <a class="nav-link @if (request()->routeIs('carrito.*')) active @endif" href="{{ route('carrito.index') }}">
                                        <i class="bi bi-cart3 me-1"></i>Carrito
                                        @if ($enCarrito > 0)
                                            <span class="badge rounded-pill text-bg-success ms-1">{{ $enCarrito }}<span class="visually-hidden"> viajes en el carrito</span></span>
                                        @endif
                                    </a>
                                </li>
                            @endif
                            @if (Route::has('compras.index'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('compras.index') }}"><i class="bi bi-ticket-perforated me-1"></i>Mis compras</a>
                                </li>
                            @endif
                        @endif

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->nombreVisible() }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><span class="dropdown-item-text small text-body-secondary">{{ auth()->user()->rol->etiqueta() }}</span></li>
                                @if (auth()->user()->esViajero())
                                    <li><a class="dropdown-item" href="{{ route('perfil.edit') }}"><i class="bi bi-person-vcard me-2"></i>Mis datos</a></li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>
</header>
