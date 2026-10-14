// Comportamientos pequeños de interfaz. La lógica de negocio vive en el servidor.
(() => {
    // Mostrar / ocultar contraseña.
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Ocultar' : 'Mostrar';
            btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });

    // Evita doble envío y muestra estado de carga en formularios.
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[data-loading-text]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = btn.dataset.loadingText;
            }
        });
    });
})();
