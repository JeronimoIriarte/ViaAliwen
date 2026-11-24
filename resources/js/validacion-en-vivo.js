/**
 * Validación en vivo de formularios (Laravel Precognition + fetch).
 *
 * Se activa en los formularios con el atributo data-validacion-en-vivo (registro, mis datos y
 * los formularios del panel). El inicio de sesión no lo tiene.
 *
 *  1. El usuario escribe en un campo (se espera 400 ms desde la última tecla) o lo cambia.
 *  2. Se envía el formulario con fetch a su misma URL, con los encabezados
 *       Precognition: true
 *       Precognition-Validate-Only: campo1,campo2
 *  3. Laravel ejecuta sólo el Form Request de esa ruta, con las mismas reglas y mensajes que al
 *     guardar, pero NO el controlador: no se guarda nada.
 *  4. Responde 204 si los campos son válidos, o 422 con los errores en JSON.
 *  5. Este script marca cada campo como válido (verde) o inválido (rojo, con el mensaje).
 *
 * Sólo se validan los campos que el usuario ya tocó, así no aparecen errores en campos que
 * todavía no completó. Al enviar, el servidor valida todo de nuevo como siempre.
 */
const ESPERA_MS = 400;

export function iniciarValidacionEnVivo() {
    document.querySelectorAll('form[data-validacion-en-vivo]').forEach(prepararFormulario);
}

function prepararFormulario(formulario) {
    const tocados = new Set();      // campos con los que el usuario ya interactuó
    const pendientes = new Set();   // campos que esperan ser validados
    let temporizador = null;
    let peticion = null;            // { controlador, campos } de la validación en curso

    /** Nombre base de un campo: "colectivos[]" -> "colectivos". */
    const base = (nombre) => nombre.replace(/\[\]$/, '');

    function esValidable(control) {
        return control.name
            && !control.disabled
            && !['hidden', 'submit', 'button', 'file'].includes(control.type)
            && !['_token', '_method'].includes(control.name);
    }

    function controlesDe(campo) {
        return [...formulario.elements].filter((c) => c.name && base(c.name) === campo);
    }

    function tieneValor(campo) {
        return controlesDe(campo).some((c) => (['checkbox', 'radio'].includes(c.type) ? c.checked : c.value.trim() !== ''));
    }

    /** Campos a validar cuando cambia uno: él mismo y sus dependientes (data-dependientes) ya tocados o completos. */
    function camposAValidar(control) {
        const campo = base(control.name);
        const dependientes = (control.dataset.dependientes ?? '')
            .split(',')
            .map((d) => d.trim())
            .filter((d) => d && controlesDe(d).length > 0 && (tocados.has(d) || tieneValor(d)));
        return [campo, ...dependientes];
    }

    function programar(campos, inmediato) {
        campos.forEach((c) => pendientes.add(c));
        clearTimeout(temporizador);
        temporizador = setTimeout(validar, inmediato ? 0 : ESPERA_MS);
    }

    async function validar() {
        // Si había una validación sin terminar, se cancela y sus campos se suman a esta.
        if (peticion) {
            peticion.controlador.abort();
            peticion.campos.forEach((c) => pendientes.add(c));
        }

        const campos = [...pendientes];
        pendientes.clear();
        if (campos.length === 0) {
            return;
        }

        const actual = { controlador: new AbortController(), campos };
        peticion = actual;

        // Para un grupo de casillas (colectivos[]) se validan el arreglo y cada elemento (colectivos.*).
        const soloValidar = campos.flatMap((c) => (controlesDe(c).some((el) => el.name.endsWith('[]')) ? [c, `${c}.*`] : [c]));

        try {
            const respuesta = await fetch(formulario.action, {
                method: 'POST', // los formularios de edición llevan _method=PUT en sus datos
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    Precognition: 'true',
                    'Precognition-Validate-Only': soloValidar.join(','),
                },
                body: new FormData(formulario),
                signal: actual.controlador.signal,
            });

            if (respuesta.status === 204) {
                campos.forEach((c) => mostrar(c, []));
            } else if (respuesta.status === 422) {
                const { errors = {} } = await respuesta.json();
                // Sólo se muestran los errores de los campos pedidos ("colectivos.0" cuenta como "colectivos").
                campos.forEach((c) => mostrar(c, Object.entries(errors)
                    .filter(([clave]) => clave.split('.')[0] === c)
                    .flatMap(([, mensajes]) => mensajes)));
            }
            // Otras respuestas (sesión vencida, demasiadas peticiones, sin conexión): no se cambia nada;
            // el formulario se valida igual al enviarlo.
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.warn('No se pudo validar el formulario en vivo.', error);
            }
        } finally {
            if (peticion === actual) {
                peticion = null;
            }
        }
    }

    /** Marca el campo como válido o inválido y escribe los mensajes en su contenedor de error. */
    function mostrar(campo, mensajes) {
        const controles = controlesDe(campo);
        if (controles.length === 0) {
            return;
        }

        const invalido = mensajes.length > 0;
        const marcarValido = !invalido && tieneValor(campo) && !controles.some((c) => c.type === 'checkbox');

        controles.forEach((control) => {
            control.classList.toggle('is-invalid', invalido);
            control.classList.toggle('is-valid', marcarValido);
            control.setAttribute('aria-invalid', String(invalido));
        });

        const caja = contenedorDeError(campo, controles[0]);
        caja.replaceChildren(...mensajes.map((m) => Object.assign(document.createElement('div'), { textContent: m })));
        caja.classList.toggle('d-block', invalido);
    }

    function contenedorDeError(campo, control) {
        let caja = formulario.querySelector(`[data-error-para="${campo}"]`);
        if (!caja) {
            caja = Object.assign(document.createElement('div'), { className: 'invalid-feedback', id: `${campo}-error` });
            caja.dataset.errorPara = campo;
            (control.closest('.input-group') ?? control).insertAdjacentElement('afterend', caja);
        }
        return caja;
    }

    // Escribir: se valida al dejar de tipear. Listas, casillas y fechas: al cambiar.
    formulario.addEventListener('input', (evento) => {
        const control = evento.target;
        if (!esValidable(control) || ['checkbox', 'radio', 'select-one'].includes(control.type)) {
            return;
        }
        tocados.add(base(control.name));
        programar(camposAValidar(control), false);
    });

    formulario.addEventListener('change', (evento) => {
        const control = evento.target;
        if (!esValidable(control)) {
            return;
        }
        tocados.add(base(control.name));
        programar(camposAValidar(control), true);
    });

    // Al salir de un campo ya tocado se valida enseguida (sin esperar los 400 ms).
    formulario.addEventListener('focusout', (evento) => {
        const control = evento.target;
        if (esValidable(control) && tocados.has(base(control.name)) && pendientes.size > 0) {
            programar([], true);
        }
    });
}
