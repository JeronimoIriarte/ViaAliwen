@extends('layouts.app')

@section('titulo', 'Mis compras')

@section('contenido')
<div class="container py-5">
    <h1 class="h3 mb-1"><i class="bi bi-ticket-perforated me-2 va-detalle"></i>Mis compras</h1>
    <p class="text-body-secondary mb-4">Tus pasajes y el estado de cada pago.</p>

    @forelse ($compras as $compra)
        @php $lineas = $compra->pasajes->isNotEmpty() ? $compra->pasajes : $compra->reservas; @endphp
        <article class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <p class="small text-body-secondary mb-1">
                        {{ $compra->created_at->isoFormat('D [de] MMMM YYYY, HH:mm') }} · Compra <code>{{ Str::limit($compra->referencia, 8, '') }}</code>
                    </p>
                    @foreach ($lineas as $linea)
                        <div class="fw-semibold">
                            {{ $linea->viaje->origen->nombre }} <i class="bi bi-arrow-right small"></i> {{ $linea->viaje->destino->nombre }}
                            <span class="fw-normal text-body-secondary small">· {{ $linea->viaje->salida->format('d/m/Y H:i') }} · butaca {{ $linea->nro_butaca }}</span>
                            @if ($linea instanceof \App\Models\Pasaje)
                                <a href="{{ route('pasajes.pdf', $linea) }}" class="small fw-normal ms-1" title="Descargar el pasaje {{ $linea->codigo }} en PDF">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="text-end">
                    <span class="badge text-bg-{{ $compra->estado->color() }} mb-2">{{ $compra->estado->etiqueta() }}</span>
                    <div class="fw-bold mb-2">@pesos($compra->total)</div>
                    <a href="{{ route('compras.show', $compra) }}" class="btn btn-sm btn-outline-primary">Ver detalle</a>
                </div>
            </div>
        </article>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-ticket display-4 text-body-tertiary" aria-hidden="true"></i>
                <p class="lead mt-3">Todavía no hiciste compras.</p>
                <a href="{{ route('viajes.index') }}" class="btn btn-primary">Buscar viajes</a>
            </div>
        </div>
    @endforelse

    <div class="mt-3">{{ $compras->links() }}</div>
</div>
@endsection
