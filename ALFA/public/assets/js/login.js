document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login-form');
    const btnLogin = document.getElementById('btnLogin');
    const btnText = btnLogin.querySelector('.btn-text');
    const btnLoader = btnLogin.querySelector('.btn-loader');
    const mensajeDiv = document.getElementById('mensaje');

    // Mostrar/ocultar contraseña
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    function mostrarMensaje(texto, tipo) {
        mensajeDiv.textContent = texto;
        mensajeDiv.className = 'mensaje ' + tipo;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        btnLogin.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline';
        mensajeDiv.style.display = 'none';

        const formData = new FormData(form);

        try {
            const response = await fetch('api/login.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                mostrarMensaje('Error de conexión con el servidor.', 'error');
                return;
            }

            if (data.ok) {
                mostrarMensaje('¡Bienvenido! Redirigiendo...', 'success');
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 1500);
            } else {
                mostrarMensaje(data.error || 'Credenciales incorrectas.', 'error');
            }
        } catch (error) {
            mostrarMensaje('No se pudo conectar al servidor.', 'error');
        } finally {
            btnLogin.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
        }
    });
});