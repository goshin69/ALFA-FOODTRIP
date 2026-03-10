document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const loader = document.getElementById('loader-wrapper');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.visibility = 'hidden';
            }, 800);
        }
    }, 1000);

    const form = document.getElementById('recetaForm');
    const videoUpload = document.getElementById('video-upload');
    const fileInfo = document.getElementById('file-info');
    const btnPublicar = document.getElementById('btnPublicar');
    const btnText = btnPublicar.querySelector('.btn-text');
    const btnLoader = btnPublicar.querySelector('.btn-loader');
    const mensajeDiv = document.getElementById('mensaje');
    const uploadBox = document.getElementById('uploadBox');

    videoUpload.addEventListener('change', function(e) {
        const file = e.target.files[0];
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

    function mostrarMensaje(texto, tipo) {
        mensajeDiv.textContent = texto;
        mensajeDiv.className = `mensaje ${tipo}`;
        setTimeout(() => {
            mensajeDiv.style.display = 'none';
        }, 5000);
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (videoUpload.files.length === 0) {
            mostrarMensaje('Debes seleccionar un video', 'error');
            return;
        }

        btnPublicar.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline';

        const formData = new FormData(form);

        try {
            const response = await fetch('/api/crear_receta.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.ok) {
                mostrarMensaje('¡Receta publicada exitosamente!', 'success');
                form.reset();
                fileInfo.innerHTML = '';
                setTimeout(() => {
                    window.location.href = 'receta.html?id=' + data.receta_id;
                }, 2000);
            } else {
                mostrarMensaje(data.error || 'Error al publicar la receta', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarMensaje('Error de conexión al servidor', 'error');
        } finally {
            btnPublicar.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
        }
    });

    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        if (item.getAttribute('href') === 'crear_receta.html') {
            item.classList.add('active');
        }
    });

    uploadBox.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#F0F0F0';
    });

    uploadBox.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#FAFAFA';
    });

    uploadBox.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#FAFAFA';
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            videoUpload.files = files;
            const event = new Event('change', { bubbles: true });
            videoUpload.dispatchEvent(event);
        }
    });
});