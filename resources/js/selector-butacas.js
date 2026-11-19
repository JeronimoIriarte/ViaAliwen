/**
 * Selector de butacas (página de un viaje).
 *  - Habilita "Agregar al carrito" cuando el viajero elige una butaca.
 *  - Cada 20 segundos (y al volver a la pestaña) consulta con fetch el estado de las butacas
 *    (GET /viajes/{id}/butacas, respuesta JSON) y marca como ocupadas las que otra persona
 *    compró o reservó, sin recargar la página.
 * El servidor vuelve a validar todo al agregar al carrito y al pagar: esto sólo mejora la experiencia.
 */
export function iniciarSelectorButacas() {
    const contenedor = document.querySelector('[data-butacas-url]');
    if (!contenedor) {
        return;
    }

    const aviso = contenedor.querySelector('[data-aviso-butaca]');

    contenedor.querySelectorAll('form.va-form-butaca').forEach((formulario) => {
        formulario.addEventListener('change', (evento) => {
            if (evento.target.name !== 'nro_butaca') {
                return;
            }
            formulario.querySelector('button[type="submit"]').disabled = false;
            formulario.querySelector('[data-seleccion]').textContent = `Butaca ${evento.target.value} seleccionada.`;
        });
    });

    async function refrescar() {
        try {
            const respuesta = await fetch(contenedor.dataset.butacasUrl, { headers: { Accept: 'application/json' } });
            if (!respuesta.ok) {
                return;
            }
            const datos = await respuesta.json();
            datos.colectivos.forEach(actualizarColectivo);
        } catch {
            // Sin conexión: se mantiene el mapa actual; el servidor valida igual al enviar.
        }
    }

    function actualizarColectivo(colectivo) {
        const mapa = contenedor.querySelector(`[data-colectivo="${colectivo.id}"]`);
        if (!mapa) {
            return;
        }

        const ocupadas = new Map([
            ...colectivo.vendidas.map((n) => [n, 'vendida']),
            ...colectivo.reservadas.map((n) => [n, 'reservada']),
        ]);

        mapa.querySelectorAll('[data-butaca].va-butaca-libre').forEach((butaca) => {
            const estado = ocupadas.get(Number(butaca.dataset.butaca));
            if (!estado) {
                return;
            }

            const radio = butaca.querySelector('input');
            if (radio) {
                if (radio.checked) {
                    radio.checked = false;
                    const formulario = radio.form;
                    formulario.querySelector('button[type="submit"]').disabled = true;
                    formulario.querySelector('[data-seleccion]').textContent = 'Ninguna butaca elegida todavía.';
                    aviso.textContent = `La butaca ${butaca.dataset.butaca} que elegiste acaba de ser ${estado} por otra persona. Elegí otra.`;
                    aviso.classList.remove('d-none');
                }
                radio.disabled = true;
            }

            butaca.classList.replace('va-butaca-libre', `va-butaca-${estado}`);
            butaca.title = `Butaca ${butaca.dataset.butaca} ${estado}`;
        });

        const contador = contenedor.querySelector(`[data-libres="${colectivo.id}"]`);
        if (contador) {
            contador.textContent = colectivo.libres > 0 ? `${colectivo.libres} libres` : 'Completo';
        }
    }

    setInterval(refrescar, 20000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refrescar();
        }
    });
}
