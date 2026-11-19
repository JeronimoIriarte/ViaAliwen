/**
 * Búsqueda dinámica de viajes (AJAX con fetch).
 *
 *  1. El usuario escribe o cambia un filtro.
 *  2. Se envía una petición asíncrona a Laravel: GET /viajes/buscar?q=...&destino_id=...
 *  3. Laravel valida los parámetros y consulta la base con Eloquent.
 *  4. Responde JSON con los viajes disponibles.
 *  5. Este script dibuja los resultados sin recargar la página.
 *
 * Contempla: indicador de carga, cantidad de resultados, mensaje sin resultados,
 * errores de validación (422) y errores de red o del servidor.
 */
export function iniciarBusquedaViajes() {
    const formulario = document.getElementById('form-busqueda');
    if (!formulario) {
        return;
    }

    const resultados = document.getElementById('resultados-viajes');
    const estado = document.getElementById('estado-busqueda');
    const cargando = estado.querySelector('[data-cargando]');
    const textoEstado = estado.querySelector('[data-texto]');
    const cajaError = document.getElementById('error-busqueda');
    const plantillaDia = document.getElementById('plantilla-dia');
    const plantillaViaje = document.getElementById('plantilla-viaje');
    const plantillaVacio = document.getElementById('plantilla-vacio');

    let espera = null;            // temporizador para no consultar en cada tecla
    let peticionEnCurso = null;   // AbortController de la búsqueda anterior

    function parametros() {
        const datos = new URLSearchParams();
        new FormData(formulario).forEach((valor, clave) => {
            if (String(valor).trim() !== '') {
                datos.set(clave, String(valor).trim());
            }
        });
        return datos;
    }

    async function buscar() {
        const params = parametros();

        // Validación simple antes de enviar (el servidor valida igual).
        const texto = params.get('q') ?? '';
        if (texto.length === 1) {
            mostrarEstado('Escribí al menos 2 letras para buscar.');
            return;
        }

        peticionEnCurso?.abort(); // si había una búsqueda anterior sin terminar, se cancela
        peticionEnCurso = new AbortController();

        cargando.classList.remove('d-none');
        textoEstado.textContent = 'Buscando viajes…';
        ocultarError();

        try {
            const respuesta = await fetch(`${formulario.dataset.buscarUrl}?${params}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: peticionEnCurso.signal,
            });

            if (respuesta.status === 422) {
                const datos = await respuesta.json();
                mostrarError(Object.values(datos.errors ?? {}).flat());
                mostrarEstado('Revisá los filtros de búsqueda.');
                return;
            }

            if (!respuesta.ok) {
                throw new Error(`HTTP ${respuesta.status}`);
            }

            const datos = await respuesta.json();
            dibujar(datos);

            // La URL refleja la búsqueda: se puede recargar o compartir.
            const consulta = params.toString();
            history.replaceState(null, '', consulta ? `${formulario.action}?${consulta}` : formulario.action);
        } catch (error) {
            if (error.name === 'AbortError') {
                return; // se reemplazó por una búsqueda más nueva
            }
            mostrarError(['No pudimos buscar los viajes en este momento. Revisá tu conexión e intentá de nuevo.']);
            mostrarEstado('');
        } finally {
            cargando.classList.add('d-none');
        }
    }

    function dibujar({ total, limite, viajes }) {
        resultados.replaceChildren();

        if (total === 0) {
            resultados.append(plantillaVacio.content.cloneNode(true));
            mostrarEstado('No se encontraron viajes.');
            return;
        }

        // Agrupa por día, igual que el listado inicial.
        const porDia = new Map();
        viajes.forEach((viaje) => {
            if (!porDia.has(viaje.dia)) {
                porDia.set(viaje.dia, { titulo: viaje.dia_texto, viajes: [] });
            }
            porDia.get(viaje.dia).viajes.push(viaje);
        });

        porDia.forEach(({ titulo, viajes: delDia }) => {
            const seccion = plantillaDia.content.cloneNode(true);
            seccion.querySelector('[data-campo="dia"]').textContent = titulo;
            const contenedor = seccion.querySelector('[data-tarjetas]');
            delDia.forEach((viaje) => contenedor.append(tarjeta(viaje)));
            resultados.append(seccion);
        });

        let mensaje = total === 1 ? 'Se encontró 1 viaje.' : `Se encontraron ${total} viajes.`;
        if (total > limite) {
            mensaje += ` Se muestran los primeros ${limite}: afiná la búsqueda para ver otros.`;
        }
        mostrarEstado(mensaje);
    }

    /** Arma una tarjeta con textContent (nunca innerHTML con datos), así no hay riesgo de inyectar HTML. */
    function tarjeta(viaje) {
        const nodo = plantillaViaje.content.cloneNode(true);
        const campo = (nombre) => nodo.querySelector(`[data-campo="${nombre}"]`);

        campo('origen').textContent = viaje.origen;
        campo('destino').textContent = viaje.destino;
        campo('precio').textContent = viaje.precio_texto;
        campo('salida').textContent = viaje.salida;
        campo('llegada').textContent = viaje.llegada;
        campo('duracion').textContent = viaje.duracion;
        campo('url').href = viaje.url;

        const libres = campo('libres');
        if (viaje.libres <= 5) {
            libres.textContent = `¡Quedan ${viaje.libres}!`;
            libres.classList.add('text-danger', 'fw-semibold');
        } else {
            libres.textContent = `${viaje.libres} butacas libres`;
            libres.classList.add('text-body-secondary');
        }

        return nodo;
    }

    function mostrarEstado(texto) {
        textoEstado.textContent = texto;
    }

    function mostrarError(mensajes) {
        cajaError.replaceChildren(...mensajes.map((m) => Object.assign(document.createElement('div'), { textContent: m })));
        cajaError.classList.remove('d-none');
    }

    function ocultarError() {
        cajaError.classList.add('d-none');
        cajaError.replaceChildren();
    }

    // Texto: espera 300 ms desde la última tecla. Listas y fecha: búsqueda inmediata.
    formulario.addEventListener('input', (evento) => {
        if (evento.target.name !== 'q') {
            return;
        }
        clearTimeout(espera);
        espera = setTimeout(buscar, 300);
    });
    formulario.addEventListener('change', (evento) => {
        if (evento.target.name !== 'q') {
            buscar();
        }
    });
    formulario.addEventListener('submit', (evento) => {
        evento.preventDefault();
        clearTimeout(espera);
        buscar();
    });

    // "Quitar filtros": limpia el formulario y busca de nuevo sin recargar.
    document.addEventListener('click', (evento) => {
        const enlace = evento.target.closest('[data-limpiar]');
        if (!enlace) {
            return;
        }
        evento.preventDefault();
        formulario.reset();
        formulario.querySelectorAll('input, select').forEach((campo) => {
            if (campo.type !== 'hidden') {
                campo.value = '';
            }
        });
        buscar();
    });
}
