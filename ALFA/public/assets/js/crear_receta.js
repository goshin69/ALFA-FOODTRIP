function mostrarMensaje(texto, tipo) {
    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.innerHTML = texto;
    mensajeDiv.className = `mensaje ${tipo}`;
    mensajeDiv.style.display = 'block';
    setTimeout(() => mensajeDiv.style.display = 'none', 5000);
}

function cargarBorrador() {
    const borradorGuardado = localStorage.getItem('borrador_receta');
    if (borradorGuardado) {
        const borrador = JSON.parse(borradorGuardado);
        if (borrador.titulo) document.getElementById('titulo').value = borrador.titulo;
        if (borrador.descripcion) document.getElementById('descripcion').value = borrador.descripcion;
        if (borrador.etiquetas && typeof cargarEtiquetasDesdeIds === 'function') {
            cargarEtiquetasDesdeIds(borrador.etiquetas.split(','));
        }
        if (borrador.receta_id) {
            document.getElementById('receta_id_temp').value = borrador.receta_id;
            window.recetaIdTemp = borrador.receta_id;
        }
        mostrarMensaje('Borrador cargado automáticamente', 'info');
    }
}

window.addEventListener('load', function() {
    setTimeout(() => {
        const loader = document.getElementById('loader-wrapper');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => loader.style.visibility = 'hidden', 300);
        }
    }, 200);
    cargarBorrador();
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('recetaForm');
    const btnPublicar = document.getElementById('btnPublicar');
    const btnGuardarBorrador = document.getElementById('btnGuardarBorrador');
    const confirmModal = document.getElementById('confirmModal');
    const progressOverlay = document.getElementById('progressOverlay');
    const fileInput = document.getElementById('archivos');
    const uploadBox = document.getElementById('uploadBox');
    const previewContainer = document.getElementById('preview-container');
    const fileInfo = document.getElementById('file-info');

    let selectedFiles = [];
    let objectURLs = [];
    let videoCount = 0;
    let imageCount = 0;
    let recetaIdTemp = document.getElementById('receta_id_temp').value || null;
    const MAX_VIDEOS = 1;
    const MAX_IMAGES = 5;

    function guardarBorradorLocal() {
        const borrador = {
            titulo: document.getElementById('titulo').value,
            descripcion: document.getElementById('descripcion').value,
            etiquetas: document.getElementById('etiquetas-hidden').value,
            receta_id: recetaIdTemp,
            timestamp: new Date().getTime()
        };
        localStorage.setItem('borrador_receta', JSON.stringify(borrador));
        mostrarMensaje('Borrador guardado localmente', 'success');
    }

    function actualizarContadores() {
        videoCount = selectedFiles.filter(f => f.type.startsWith('video/')).length;
        imageCount = selectedFiles.filter(f => f.type.startsWith('image/')).length;
    }

    function limpiarPreviews() {
        for (const url of objectURLs) URL.revokeObjectURL(url);
        objectURLs = [];
        previewContainer.innerHTML = '';
        fileInfo.innerHTML = '';
    }

    function actualizarPreviews() {
        limpiarPreviews();
        if (selectedFiles.length === 0) {
            fileInput.value = '';
            return;
        }

        fileInfo.innerHTML = `<strong>${selectedFiles.length}</strong> archivo(s) seleccionado(s)<br>
                              <small>Videos: ${videoCount}/${MAX_VIDEOS} | Imágenes: ${imageCount}/${MAX_IMAGES}</small>`;

        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;

        selectedFiles.forEach((file, idx) => {
            const isVideo = file.type.startsWith('video/');
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-file';
            removeBtn.innerHTML = '✕';
            removeBtn.onclick = (e) => {
                e.stopPropagation();
                selectedFiles.splice(idx, 1);
                actualizarContadores();
                actualizarPreviews();
                guardarBorradorLocal();
            };
            previewItem.appendChild(removeBtn);

            const typeBadge = document.createElement('div');
            typeBadge.className = 'file-type-badge';
            typeBadge.textContent = isVideo ? `Video ${videoCount}/${MAX_VIDEOS}` : `Imagen ${imageCount}/${MAX_IMAGES}`;
            previewItem.appendChild(typeBadge);

            const blobUrl = URL.createObjectURL(file);
            objectURLs.push(blobUrl);

            if (isVideo) {
                const video = document.createElement('video');
                video.muted = true;
                video.preload = 'metadata';
                video.src = blobUrl;
                video.onloadedmetadata = () => {
                    const duration = video.duration;
                    const minutes = Math.floor(duration / 60);
                    const seconds = Math.floor(duration % 60);
                    const durationText = document.createElement('div');
                    durationText.className = 'video-duration';
                    durationText.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    previewItem.appendChild(durationText);
                    if (duration > 2400) {
                        mostrarMensaje(`El video "${file.name}" excede los 40 minutos`, 'error');
                        setTimeout(() => removeBtn.click(), 100);
                    }
                };
                video.onerror = () => {
                    const errorMsg = document.createElement('div');
                    errorMsg.textContent = 'Error al cargar video';
                    errorMsg.style.cssText = 'color:red;font-size:12px;';
                    previewItem.appendChild(errorMsg);
                };
                previewItem.appendChild(video);
            } else {
                const img = document.createElement('img');
                img.src = blobUrl;
                img.onerror = () => {
                    const errorMsg = document.createElement('div');
                    errorMsg.textContent = 'Error al cargar imagen';
                    errorMsg.style.cssText = 'color:red;font-size:12px;';
                    previewItem.appendChild(errorMsg);
                };
                previewItem.appendChild(img);
            }
            previewContainer.appendChild(previewItem);
        });
    }

    function esArchivoDuplicado(nuevoFile) {
        return selectedFiles.some(existente =>
            existente.name === nuevoFile.name &&
            existente.size === nuevoFile.size &&
            existente.lastModified === nuevoFile.lastModified
        );
    }

    function handleFiles(newFiles) {
        const filesArray = Array.from(newFiles);
        const validFiles = filesArray.filter(f => f.type.startsWith('image/') || f.type.startsWith('video/'));

        if (validFiles.length !== filesArray.length) {
            mostrarMensaje('Solo se permiten imágenes y videos. Algunos archivos fueron omitidos.', 'error');
        }

        let remainingImages = MAX_IMAGES - imageCount;
        let remainingVideos = MAX_VIDEOS - videoCount;
        let addedImages = 0;
        let addedVideos = 0;
        const filesToAdd = [];

        for (const file of validFiles) {
            if (esArchivoDuplicado(file)) {
                mostrarMensaje(`El archivo "${file.name}" ya está agregado.`, 'warning');
                continue;
            }

            const isVideo = file.type.startsWith('video/');
            if (isVideo) {
                if (addedVideos < remainingVideos) {
                    filesToAdd.push(file);
                    addedVideos++;
                } else {
                    mostrarMensaje(`Solo se permite ${MAX_VIDEOS} video. El video "${file.name}" no se agregó.`, 'warning');
                }
            } else {
                if (addedImages < remainingImages) {
                    filesToAdd.push(file);
                    addedImages++;
                } else {
                    mostrarMensaje(`Solo se permiten ${MAX_IMAGES} imágenes. La imagen "${file.name}" no se agregó.`, 'warning');
                }
            }
        }

        if (filesToAdd.length > 0) {
            selectedFiles = [...selectedFiles, ...filesToAdd];
            actualizarContadores();
            actualizarPreviews();
            guardarBorradorLocal();
        }
    }

    fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

    uploadBox.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadBox.classList.add('drag-over');
    });
    uploadBox.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadBox.classList.remove('drag-over');
    });
    uploadBox.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadBox.classList.remove('drag-over');
        if (e.dataTransfer.files.length) handleFiles(e.dataTransfer.files);
    });

    async function enviarFormulario(accion) {
        const titulo = document.getElementById('titulo').value.trim();
        const descripcion = document.getElementById('descripcion').value.trim();

        if (accion === 'publicar') {
            if (!titulo || !descripcion) {
                mostrarMensaje('Título y descripción son obligatorios para publicar', 'error');
                return false;
            }
            if (selectedFiles.length === 0) {
                mostrarMensaje('Debes seleccionar al menos una imagen o video', 'error');
                return false;
            }
            if (videoCount > MAX_VIDEOS || imageCount > MAX_IMAGES) {
                mostrarMensaje(`No se pueden enviar más de ${MAX_VIDEOS} video y ${MAX_IMAGES} imágenes.`, 'error');
                return false;
            }
        } else {
            if (!titulo && !descripcion && selectedFiles.length === 0) {
                mostrarMensaje('No hay datos para guardar', 'error');
                return false;
            }
        }

        progressOverlay.style.display = 'flex';
        const formData = new FormData();
        formData.append('titulo', titulo);
        formData.append('descripcion', descripcion);
        formData.append('etiquetas', document.getElementById('etiquetas-hidden').value);
        if (recetaIdTemp) formData.append('receta_id_temp', recetaIdTemp);
        selectedFiles.forEach(file => formData.append('archivos[]', file));

        try {
            const url = accion === 'publicar' ? 'api/crear_receta.php' : 'api/guardar_borrador.php';
            const response = await fetch(url, { method: 'POST', credentials: 'include', body: formData });
            const data = await response.json();

            if (data.ok) {
                if (accion === 'publicar') {
                    mostrarMensaje('¡Receta publicada exitosamente!', 'success');
                    localStorage.removeItem('borrador_receta');
                    selectedFiles = [];
                    actualizarContadores();
                    actualizarPreviews();
                    form.reset();
                    setTimeout(() => window.location.href = 'receta.html?id=' + data.receta_id, 2000);
                } else {
                    mostrarMensaje('Borrador guardado exitosamente', 'success');
                    recetaIdTemp = data.receta_id;
                    document.getElementById('receta_id_temp').value = recetaIdTemp;
                    guardarBorradorLocal();
                }
                return true;
            } else {
                mostrarMensaje(data.error || `Error al ${accion === 'publicar' ? 'publicar' : 'guardar'} la receta`, 'error');
                return false;
            }
        } catch (error) {
            console.error(error);
            mostrarMensaje('Error de conexión al servidor', 'error');
            return false;
        } finally {
            progressOverlay.style.display = 'none';
        }
    }

    btnGuardarBorrador.addEventListener('click', async function() {
        this.disabled = true;
        await enviarFormulario('borrador');
        this.disabled = false;
    });

    btnPublicar.addEventListener('click', () => confirmModal.style.display = 'block');

    document.getElementById('confirmarPublicacion').onclick = async function() {
        confirmModal.style.display = 'none';
        btnPublicar.disabled = true;
        const success = await enviarFormulario('publicar');
        if (!success) btnPublicar.disabled = false;
    };
    document.getElementById('cancelarPublicacion').onclick = () => confirmModal.style.display = 'none';
    document.querySelector('.close-modal').onclick = () => confirmModal.style.display = 'none';
    window.onclick = (event) => { if (event.target === confirmModal) confirmModal.style.display = 'none'; };

    setInterval(() => {
        const titulo = document.getElementById('titulo').value;
        const descripcion = document.getElementById('descripcion').value;
        if (titulo || descripcion || selectedFiles.length) guardarBorradorLocal();
    }, 30000);

    window.addEventListener('beforeunload', () => {
        const titulo = document.getElementById('titulo').value;
        const descripcion = document.getElementById('descripcion').value;
        if (titulo || descripcion || selectedFiles.length) guardarBorradorLocal();
    });
});