document.addEventListener('DOMContentLoaded', function() {
    let moveX = 0;
    let moveY = 0;

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
                actualizarEstadoBoton();
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

    function obtenerMensajeCamposFaltantes() {
        const camposFaltantes = [];

        if (!document.getElementById('nombre').value.trim()) camposFaltantes.push('nombre');
        if (!document.getElementById('email').value.trim()) camposFaltantes.push('correo');
        if (!document.getElementById('password').value.trim()) camposFaltantes.push('contraseña');
        if (!document.getElementById('confirm_password').value.trim()) camposFaltantes.push('confirmación de contraseña');
        if (!document.getElementById('rol').value.trim()) camposFaltantes.push('tipo de cuenta');

        if (camposFaltantes.length === 0) return '';
        if (camposFaltantes.length === 1) return 'Falta completar: ' + camposFaltantes[0] + '.';
        return 'Faltan datos obligatorios. Completa todos los campos.';
    }

    function camposCompletos() {
        return obtenerMensajeCamposFaltantes() === '';
    }

    function mostrarWarningCampos() {
        const texto = obtenerMensajeCamposFaltantes();
        if (!texto) {
            return;
        }

        mensajeDiv.textContent = texto;
        mensajeDiv.className = 'mensaje warning';
        mensajeDiv.style.display = 'block';
    }

    function ocultarWarningCampos() {
        if (mensajeDiv.classList.contains('warning')) {
            mensajeDiv.style.display = 'none';
            mensajeDiv.className = 'mensaje';
            mensajeDiv.textContent = '';
        }
    }

    function moverBotonRegistro() {
        const maxX = 85;
        const maxY = 55;

        moveX = (Math.random() * 2 - 1) * maxX;
        moveY = (Math.random() * 2 - 1) * maxY;

        btnRegistro.style.transform = `translate(${moveX}px, ${moveY}px)`;
    }

    function actualizarEstadoBoton() {
        if (camposCompletos()) {
            btnRegistro.classList.add('ready');
            btnRegistro.classList.remove('locked');
            btnRegistro.style.transform = 'translate(0px, 0px)';
            moveX = 0;
            moveY = 0;
            ocultarWarningCampos();
        } else {
            btnRegistro.classList.remove('ready');
            btnRegistro.classList.add('locked');
        }
    }

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

        if (tipo === 'warning') {
            return;
        }

        setTimeout(() => {
            mensajeDiv.style.display = 'none';
        }, 5000);
    }

    function habilitarBoton() {
        btnRegistro.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        actualizarEstadoBoton();
    }

    const camposParaValidar = [
        document.getElementById('nombre'),
        document.getElementById('email'),
        document.getElementById('password'),
        document.getElementById('confirm_password')
    ];

    camposParaValidar.forEach((campo) => {
        campo.addEventListener('input', actualizarEstadoBoton);
    });

    btnRegistro.addEventListener('mouseenter', function() {
        if (!camposCompletos() && !btnRegistro.disabled) {
            mostrarWarningCampos();
            moverBotonRegistro();
        }
    });

    btnRegistro.addEventListener('click', function(e) {
        if (!camposCompletos() && !btnRegistro.disabled) {
            e.preventDefault();
            mostrarWarningCampos();
            moverBotonRegistro();
        }
    });

    document.addEventListener('mousemove', function(e) {
        if (camposCompletos() || btnRegistro.disabled) {
            return;
        }

        const rect = btnRegistro.getBoundingClientRect();
        const distanciaX = e.clientX - (rect.left + rect.width / 2);
        const distanciaY = e.clientY - (rect.top + rect.height / 2);
        const distancia = Math.sqrt(distanciaX * distanciaX + distanciaY * distanciaY);

        if (distancia < 120) {
            mostrarWarningCampos();
            moverBotonRegistro();
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!camposCompletos()) {
            mostrarWarningCampos();
            moverBotonRegistro();
            return;
        }

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
        btnRegistro.style.transform = 'translate(0px, 0px)';
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
                    window.location.href = 'login.html';
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

    actualizarEstadoBoton();
});