window.addEventListener('load', function() {
    setTimeout(() => {
        const loader = document.getElementById('loader-wrapper');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.visibility = 'hidden';
            }, 800);
        }
    }, 500);
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('recetaForm');
    const btnPublicar = document.getElementById('btnPublicar');
    const btnText = btnPublicar.querySelector('.btn-text');
    const btnLoader = btnPublicar.querySelector('.btn-loader');
    const mensajeDiv = document.getElementById('mensaje');
    const videoInput = document.getElementById('video');
    const fileInfo = document.getElementById('file-info');
    const imagenesInput = document.getElementById('imagenes');
    const imagenesPreview = document.getElementById('imagenes-preview');

    function mostrarMensaje(texto, tipo) {
        mensajeDiv.textContent = texto;
        mensajeDiv.className = `mensaje ${tipo}`;
        setTimeout(() => {
            mensajeDiv.style.display = 'none';
        }, 5000);
    }

    imagenesInput.addEventListener('change', function() {
        imagenesPreview.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                imagenesPreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });

    videoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 500 * 1024 * 1024) {
                mostrarMensaje('El archivo es demasiado grande. Máximo 500MB', 'error');
                this.value = '';
                fileInfo.innerHTML = '';
                return;
            }
            if (file.type !== 'video/mp4') {
                mostrarMensaje('Solo se permiten archivos MP4', 'error');
                this.value = '';
                fileInfo.innerHTML = '';
                return;
            }
            fileInfo.innerHTML = `
                <i class="fa-solid fa-check-circle" style="color: green;"></i>
                Archivo seleccionado: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
            `;
        } else {
            fileInfo.innerHTML = '';
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Limpiar mensaje anterior
        mensajeDiv.style.display = 'none';
        mensajeDiv.textContent = '';

        btnPublicar.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline';

        const formData = new FormData(form);

        try {
            const response = await fetch('api/crear_receta.php', {
                method: 'POST',
                body: formData
            });

            const responseText = await response.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Error al parsear JSON:', jsonError);
                mostrarMensaje('Error: respuesta del servidor no es JSON válido', 'error');
                return;
            }

            if (data.ok) {
                mostrarMensaje('¡Receta publicada exitosamente!', 'success');
                form.reset();
                imagenesPreview.innerHTML = '';
                fileInfo.innerHTML = '';
                setTimeout(() => {
                    window.location.href = 'receta.html?id=' + data.receta_id;
                }, 2000);
            } else {
                mostrarMensaje(data.error || 'Error al publicar la receta', 'error');
            }
        } catch (error) {
            console.error('Error en catch:', error);
            mostrarMensaje('Error de conexión al servidor', 'error');
        } finally {
            btnPublicar.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
        }
    });
});