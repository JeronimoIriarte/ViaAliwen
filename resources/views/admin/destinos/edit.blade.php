@extends('layouts.admin')

@section('titulo', "Editar {$destino->nombre}")

@section('contenido_admin')
<a href="{{ route('admin.destinos.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Volver a destinos</a>
<h1 class="h3 mt-2 mb-4">{{ $destino->nombre }}</h1>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Datos del destino</h2>
                <form method="POST" action="{{ route('admin.destinos.update', $destino) }}" novalidate>
                    @csrf
                    @method('PUT')
                    @include('admin.destinos._form')
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Guardar cambios</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Galería de fotos <span class="badge text-bg-light">{{ $destino->imagenes->count() }}</span></h2>

                @if ($destino->imagenes->isEmpty())
                    <p class="text-body-secondary">Este destino todavía no tiene fotos.</p>
                @else
                    <div class="row g-3 mb-4">
                        @foreach ($destino->imagenes as $imagen)
                            <div class="col-sm-6 col-lg-4">
                                <figure class="card h-100 mb-0">
                                    <img src="{{ $imagen->url() }}" alt="{{ $imagen->epigrafe ?? $destino->nombre }}"
                                         class="card-img-top object-fit-cover" style="height: 120px" loading="lazy">
                                    <figcaption class="card-body p-2 small">
                                        <div class="fw-semibold">{{ $imagen->epigrafe ?? 'Sin epígrafe' }}</div>
                                        @if ($imagen->credito)
                                            <div class="text-body-secondary text-truncate" title="{{ $imagen->credito }}">{{ $imagen->credito }}</div>
                                        @endif
                                        <form method="POST" action="{{ route('admin.destinos.imagenes.destroy', [$destino, $imagen]) }}" class="mt-2"
                                              onsubmit="return confirm('¿Eliminar esta foto de la galería?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash me-1"></i>Quitar</button>
                                        </form>
                                    </figcaption>
                                </figure>
                            </div>
                        @endforeach
                    </div>
                @endif

                <h3 class="h6 text-uppercase va-detalle">Agregar fotos</h3>
                <form method="POST" action="{{ route('admin.destinos.imagenes.store', $destino) }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="imagenes" class="form-label fw-semibold">Imágenes (JPG, PNG o WEBP, hasta 4 MB cada una)</label>
                        <input type="file" id="imagenes" name="imagenes[]" multiple accept=".jpg,.jpeg,.png,.webp"
                               class="form-control @if ($errors->has('imagenes') || $errors->has('imagenes.*')) is-invalid @endif">
                        @if ($errors->has('imagenes') || $errors->has('imagenes.*'))
                            <div class="invalid-feedback">{{ $errors->first('imagenes') ?: $errors->first('imagenes.*') }}</div>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-campo nombre="epigrafe" etiqueta="Epígrafe (opcional)" maxlength="150" />
                        </div>
                        <div class="col-md-6">
                            <x-campo nombre="credito" etiqueta="Crédito / autor (opcional)" maxlength="255" />
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i>Subir fotos</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
