/**
 * Botón "mostrar contraseña" de los campos de clave (componente <x-campo tipo="password">).
 *
 * Alterna el input entre type="password" y type="text". Antes de enviar el formulario la clave
 * vuelve a ocultarse, para que el navegador no la guarde como texto común en su historial.
 */
export function iniciarMostrarClave() {
    document.querySelectorAll('[data-mostrar-clave]').forEach((boton) => {
        const campo = document.getElementById(boton.getAttribute('aria-controls'));
        if (!campo) {
            return;
        }

        const icono = boton.querySelector('i');

        function aplicar(mostrar) {
            campo.type = mostrar ? 'text' : 'password';
            const texto = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
            boton.setAttribute('aria-pressed', String(mostrar));
            boton.setAttribute('aria-label', texto);
            boton.title = texto;
            icono.classList.toggle('bi-eye', !mostrar);
            icono.classList.toggle('bi-eye-slash', mostrar);
        }

        boton.addEventListener('click', () => {
            aplicar(campo.type === 'password');

            // El foco vuelve al campo, con el cursor al final, para seguir escribiendo.
            campo.focus();
            const largo = campo.value.length;
            campo.setSelectionRange(largo, largo);
        });

        campo.form?.addEventListener('submit', () => aplicar(false));
    });
}
