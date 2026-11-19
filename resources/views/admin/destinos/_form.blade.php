{{-- Campos compartidos por el alta y la edición de destinos. --}}
<x-campo nombre="nombre" etiqueta="Nombre" :valor="$destino->nombre" maxlength="100" required />

<div class="mb-3">
    <label for="descripcion" class="form-label fw-semibold">Descripción de sus atractivos</label>
    <textarea id="descripcion" name="descripcion" rows="6" maxlength="3000" required
              class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $destino->descripcion) }}</textarea>
    @error('descripcion')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-check form-switch mb-4">
    <input class="form-check-input" type="checkbox" role="switch" id="destacado" name="destacado" value="1"
           @checked(old('destacado', $destino->destacado))>
    <label class="form-check-label" for="destacado">Mostrar como destino destacado en la página de inicio</label>
</div>
