@extends('layouts.admin')

@section('titulo', 'Nuevo viaje')

@section('contenido_admin')
<a href="{{ route('admin.viajes.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Volver a viajes</a>
<h1 class="h3 mt-2 mb-4">Nuevo viaje</h1>

<div class="card border-0 shadow-sm" style="max-width: 56rem">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.viajes.store') }}" novalidate data-validacion-en-vivo>
            @csrf
            @include('admin.viajes._form')

            <fieldset class="mb-4">
                <legend class="form-label fw-semibold fs-6">Colectivos asignados</legend>
                <p class="small text-body-secondary mb-2">
                    Sólo se listan los operativos. No se puede elegir un colectivo que ya tenga otro viaje en ese horario.
                </p>
                <div class="row g-2">
                    @foreach ($colectivos as $c)
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-check border rounded p-2 ps-5 h-100">
                                <input type="checkbox" name="colectivos[]" value="{{ $c->id }}"
                                       class="form-check-input @if ($errors->has('colectivos') || $errors->has('colectivos.*')) is-invalid @endif"
                                       @checked(in_array($c->id, old('colectivos', [])))>
                                <span class="fw-semibold">N.º {{ $c->numero_interno }}</span>
                                <span class="small text-body-secondary">· {{ $c->patente }} · {{ $c->capacidad }} butacas</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                @php $hayErrorColectivos = $errors->has('colectivos') || $errors->has('colectivos.*'); @endphp
                {{-- Siempre presente: la validación en vivo escribe acá los errores de los colectivos. --}}
                <div class="invalid-feedback mt-2 @if ($hayErrorColectivos) d-block @endif" id="colectivos-error" data-error-para="colectivos">
                    @foreach ($errors->get('colectivos') as $mensaje)
                        <div>{{ $mensaje }}</div>
                    @endforeach
                    @foreach ($errors->get('colectivos.*') as $mensajesDeUnColectivo)
                        @foreach ($mensajesDeUnColectivo as $mensaje)
                            <div>{{ $mensaje }}</div>
                        @endforeach
                    @endforeach
                </div>
            </fieldset>

            <button type="submit" class="btn btn-success"><i class="bi bi-check2 me-1"></i>Crear viaje</button>
        </form>
    </div>
</div>
@endsection
