<footer class="va-footer mt-5 pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <p class="h4 text-white d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-bus-front-fill" style="color:#7fd1a0" aria-hidden="true"></i>VíaAliwén
                </p>
                <p class="mb-0">
                    Viajamos con nuestros propios colectivos a los lagos, volcanes y termas de la provincia del Neuquén.
                    "Aliwén" significa "árbol" en mapuzungun.
                </p>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="mb-3">Navegación</h2>
                <ul class="list-unstyled mb-0">
                    <li><a href="{{ route('home') }}">Inicio</a></li>
                    @if (Route::has('destinos.index'))<li><a href="{{ route('destinos.index') }}">Destinos</a></li>@endif
                    @if (Route::has('viajes.index'))<li><a href="{{ route('viajes.index') }}">Viajes disponibles</a></li>@endif
                    @guest<li><a href="{{ route('registro') }}">Crear cuenta</a></li>@endguest
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <h2 class="mb-3">Contacto</h2>
                <ul class="list-unstyled mb-0">
                    <li><i class="bi bi-geo-alt me-2"></i>Terminal de Ómnibus de Neuquén</li>
                    <li><i class="bi bi-clock me-2"></i>Boletería: todos los días de 6 a 22 h</li>
                    <li><i class="bi bi-envelope me-2"></i>contacto@viaaliwen.test</li>
                </ul>
            </div>
        </div>
        <hr class="border-light opacity-25 my-4">
        <p class="small mb-1 text-center">
            © {{ date('Y') }} VíaAliwén · Empresa ficticia creada para el Trabajo Final de Programación Web Dinámica.
        </p>
        <p class="small mb-0 text-center opacity-75">
            Fotos de Wikimedia Commons con licencias libres; el autor de cada una figura en su galería.
            Portada: lago Lácar, por Marco Antonio Correa Flores (CC BY-SA 4.0).
        </p>
    </div>
</footer>
