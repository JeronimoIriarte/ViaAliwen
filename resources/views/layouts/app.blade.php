<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('descripcion', 'VíaAliwén: pasajes de colectivo a los destinos más lindos de Neuquén.')">
    <title>@hasSection('titulo')@yield('titulo') · @endif{{ config('app.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('estilos')
</head>
<body>
    @include('partials.header')

    <main>
        @include('partials.alertas')
        @yield('contenido')
    </main>

    @include('partials.footer')

    @stack('scripts')
</body>
</html>
