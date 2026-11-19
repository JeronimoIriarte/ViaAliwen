{{-- Layout del panel administrativo: reutiliza el layout general y agrega el menú lateral. --}}
@extends('layouts.app')

@section('contenido')
<div class="container-fluid px-lg-4 py-4">
    <div class="row g-4">
        <aside class="col-lg-2">
            <nav class="va-admin-menu card border-0 shadow-sm" aria-label="Menú administrativo">
                <div class="card-body p-2">
                    <p class="small text-uppercase fw-semibold text-body-secondary px-2 pt-2 mb-2">Administración</p>
                    @php
                        $secciones = [
                            ['admin.dashboard', 'admin.dashboard', 'bi-speedometer2', 'Resumen'],
                            ['admin.viajes.index', 'admin.viajes.*', 'bi-signpost-split', 'Viajes'],
                            ['admin.destinos.index', 'admin.destinos.*', 'bi-geo-alt', 'Destinos'],
                            ['admin.colectivos.index', 'admin.colectivos.*', 'bi-bus-front', 'Colectivos'],
                            ['admin.viajeros.index', 'admin.viajeros.*', 'bi-people', 'Viajeros'],
                        ];
                    @endphp
                    <ul class="nav nav-pills flex-column gap-1">
                        @foreach ($secciones as [$ruta, $patron, $icono, $texto])
                            <li class="nav-item">
                                <a href="{{ route($ruta) }}" class="nav-link @if (request()->routeIs($patron)) active @endif"
                                   @if (request()->routeIs($patron)) aria-current="page" @endif>
                                    <i class="bi {{ $icono }} me-2" aria-hidden="true"></i>{{ $texto }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </nav>
        </aside>

        <div class="col-lg-10">
            @if ($errors->hasAny(['colectivo', 'general']))
                <div class="alert alert-danger">{{ $errors->first('colectivo') ?: $errors->first('general') }}</div>
            @endif

            @yield('contenido_admin')
        </div>
    </div>
</div>
@endsection
