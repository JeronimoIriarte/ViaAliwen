// Componentes interactivos de Bootstrap (menús desplegables, alertas descartables, modales, carruseles).
import * as bootstrap from 'bootstrap';
import { iniciarBusquedaViajes } from './busqueda-viajes';
import { iniciarCompraPendiente } from './compra-pendiente';
import { iniciarMostrarClave } from './mostrar-clave';
import { iniciarSelectorButacas } from './selector-butacas';
import { iniciarValidacionEnVivo } from './validacion-en-vivo';

window.bootstrap = bootstrap;

// Cada módulo verifica si su sección está en la página; si no, no hace nada.
document.addEventListener('DOMContentLoaded', () => {
    iniciarBusquedaViajes();
    iniciarSelectorButacas();
    iniciarCompraPendiente();
    iniciarMostrarClave();
    iniciarValidacionEnVivo();
});
