document.addEventListener('DOMContentLoaded', function() {
    const customSelect = document.getElementById('custom-select');
    if (customSelect) {
        const selected = customSelect.querySelector('.select-selected');
        const itemsContainer = customSelect.querySelector('.select-items');
        const hiddenInput = customSelect.querySelector('input[type="hidden"]');
        const items = customSelect.querySelectorAll('.select-items div');

        selected.addEventListener('click', function(e) {
            e.stopPropagation();
            itemsContainer.classList.toggle('show');
            selected.classList.toggle('select-arrow-active');
        });

        items.forEach(item => {
            item.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const text = this.textContent;
                selected.textContent = text;
                hiddenInput.value = value;
                items.forEach(i => i.classList.remove('same-as-selected'));
                this.classList.add('same-as-selected');
                itemsContainer.classList.remove('show');
                selected.classList.remove('select-arrow-active');
            });
        });

        document.addEventListener('click', function() {
            itemsContainer.classList.remove('show');
            selected.classList.remove('select-arrow-active');
        });
    }
    const form = document.getElementById('registro-form');
    const btnRegistro = document.getElementById('btnRegistro');
    const btnText = btnRegistro.querySelector('.btn-text');
    const btnLoader = btnRegistro.querySelector('.btn-loader');
    const mensajeDiv = document.getElementById('mensaje');

    function validarContrasena(password) {
        if (password.length < 6) return { valida: false, mensaje: 'Mínimo 6 caracteres.' };
        const numeros = (password.match(/\d/g) || []).length;
        if (numeros < 2) return { valida: false, mensaje: 'Al menos dos números.' };
        const especiales = (password.match(/[^a-zA-Z0-9\s]/g) || []).length;
        if (especiales < 1) return { valida: false, mensaje: 'Al menos un carácter especial.' };
        return { valida: true };
    }

    function mostrarMensaje(texto, tipo) {
        mensajeDiv.textContent = texto;
        mensajeDiv.className = 'mensaje ' + tipo;
        mensajeDiv.style.display = 'block';
        setTimeout(() => {
            mensajeDiv.style.display = 'none';
        }, 5000);
    }

    function habilitarBoton() {
        btnRegistro.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const rolInput = document.getElementById('rol');
        if (!rolInput.value) {
            mostrarMensaje('Debes seleccionar un tipo de cuenta.', 'error');
            return;
        }

        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            mostrarMensaje('Las contraseñas no coinciden.', 'error');
            return;
        }

        const validaPass = validarContrasena(password);
        if (!validaPass.valida) {
            mostrarMensaje(validaPass.mensaje, 'error');
            return;
        }

        btnRegistro.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline';
        mensajeDiv.style.display = 'none';

        const formData = new FormData(form);

        try {
            const response = await fetch('api/registro.php', {
                method: 'POST',
                body: formData
            });

            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                mostrarMensaje('Error de conexión con el servidor.', 'error');
                habilitarBoton();
                return;
            }

            if (data.ok) {
                mostrarMensaje('Registro exitoso. Redirigiendo...', 'success');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                mostrarMensaje(data.error || 'Error en registro.', 'error');
                habilitarBoton();
            }
        } catch (error) {
            mostrarMensaje('Error de conexión.', 'error');
            habilitarBoton();
        }
    });
});