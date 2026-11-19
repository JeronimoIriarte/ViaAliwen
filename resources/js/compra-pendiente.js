/**
 * Página de una compra con el pago pendiente.
 * El checkout de Mercado Pago se abre en otra pestaña; mientras tanto, esta página consulta con
 * fetch el estado de la compra (GET /mis-compras/{id}/estado, JSON). En cuanto deja de estar
 * pendiente (pago aprobado, compra vencida, etc.) se recarga sola para mostrar el resultado.
 */
export function iniciarCompraPendiente() {
    const tarjeta = document.querySelector('[data-estado-url]');
    if (!tarjeta) {
        return;
    }

    const INTERVALO = 8000; // cada 8 segundos mientras la pestaña está visible
    let consultando = false;

    async function consultar() {
        if (consultando || document.hidden) {
            return;
        }
        consultando = true;
        try {
            const respuesta = await fetch(tarjeta.dataset.estadoUrl, { headers: { Accept: 'application/json' } });
            if (respuesta.ok) {
                const datos = await respuesta.json();
                if (!datos.pendiente) {
                    window.location.reload();
                }
            }
        } catch {
            // Sin conexión momentánea: se reintenta en la próxima vuelta.
        } finally {
            consultando = false;
        }
    }

    setInterval(consultar, INTERVALO);

    // Al volver a esta pestaña (después de pagar en la otra) se consulta enseguida.
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            consultar();
        }
    });

    // Cuando vence la reserva, se recarga para mostrar que la compra venció.
    const msHastaVencer = new Date(tarjeta.dataset.expira).getTime() - Date.now();
    if (msHastaVencer > 0) {
        setTimeout(() => window.location.reload(), msHastaVencer + 2000);
    }
}
