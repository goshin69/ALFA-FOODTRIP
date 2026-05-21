// crear_receta.js with translation support
let currentLang = localStorage.getItem('lang') || 'es';

const TEXTS = {
    es: {
        nueva_receta: 'Nueva Receta',
        titulo_label: 'Título de la receta:',
        titulo_placeholder: 'Ej. Tacos al Pastor',
        descripcion_label: 'Descripción (presentación):',
        descripcion_placeholder: 'Un pequeño mensaje para tus seguidores...',
        preparacion_label: 'Preparación (pasos detallados):',
        preparacion_placeholder: 'Explica paso a paso cómo se prepara...',
        ingredientes_label: 'Ingredientes:',
        ingredientes_placeholder: 'Lista de ingredientes (1 taza de harina, 2 huevos, etc.)',
        dificultad_label: 'Dificultad',
        dificultad_facil: 'Fácil',
        dificultad_media: 'Media',
        dificultad_dificil: 'Difícil',
        tiempo_label: 'Tiempo de preparación',
        tiempo_5: 'Menos de 10 minutos',
        tiempo_25: '20 a 30 minutos',
        tiempo_45: 'Menos de 1 hora',
        tiempo_90: 'Más de 1 hora',
        etiquetas_label: 'Etiquetas',
        etiquetas_placeholder: 'Escribe para buscar o crear etiquetas...',
        guardar_borrador: 'Guardar Borrador',
        publicar_receta: 'Publicar Receta',
        publicando: 'Publicando...',
        upload_title: 'Arrastra y suelta tus archivos aquí',
        upload_desc: 'Imágenes (JPG, PNG, GIF, WebP) y videos (MP4, MKV, AVI, MOV, WebM)',
        upload_limit: 'Máximo: 1 video (40min, 1080p) y 5 imágenes',
        seleccionar_archivos: 'Seleccionar archivos',
        confirmar_titulo: 'Confirmar publicación',
        confirmar_pregunta: '¿Estás seguro de que deseas publicar esta receta?',
        confirmar_advertencia: 'Una vez publicada, estará visible para todos los usuarios.',
        cancelar: 'Cancelar',
        publicar: 'Publicar',
        procesando: 'Procesando...',
        error_video_formato: 'Solo se permiten videos en formato MP4',
        error_video_duracion: 'El video no puede durar más de 40 minutos',
        error_video_resolucion: 'La resolución máxima es 1920x1080',
        error_video_corrupto: 'No se pudo leer el video (formato no soportado o corrupto)',
        error_archivos_tipo: 'Solo se permiten imágenes (JPG, PNG, GIF, WebP) y videos MP4. Algunos archivos fueron omitidos.',
        error_archivo_duplicado: 'El archivo "%s" ya está agregado.',
        error_max_video: 'Solo se permite 1 video. El video "%s" no se agregó.',
        error_max_imagenes: 'Solo se permiten 5 imágenes. La imagen "%s" no se agregó.',
        error_campos_obligatorios: 'Título, descripción, preparación e ingredientes son obligatorios',
        error_archivos_minimo: 'Debes seleccionar al menos una imagen o video',
        error_limites: 'No se pueden enviar más de 1 video y 5 imágenes.',
        error_datos_vacios: 'No hay datos para guardar',
        exito_publicada: '¡Receta publicada exitosamente!',
        exito_borrador: 'Borrador guardado exitosamente',
        error_publicar: 'Error al publicar la receta',
        error_guardar: 'Error al guardar la receta',
        error_conexion: 'Error de conexión al servidor',
        borrador_cargado: 'Borrador cargado automáticamente',
        borrador_guardado: 'Borrador guardado localmente'
    },
    en: {
        nueva_receta: 'New Recipe',
        titulo_label: 'Recipe title:',
        titulo_placeholder: 'E.g. Tacos al Pastor',
        descripcion_label: 'Description (presentation):',
        descripcion_placeholder: 'A short message for your followers...',
        preparacion_label: 'Preparation (detailed steps):',
        preparacion_placeholder: 'Explain step by step how to prepare...',
        ingredientes_label: 'Ingredients:',
        ingredientes_placeholder: 'List of ingredients (1 cup flour, 2 eggs, etc.)',
        dificultad_label: 'Difficulty',
        dificultad_facil: 'Easy',
        dificultad_media: 'Medium',
        dificultad_dificil: 'Hard',
        tiempo_label: 'Preparation time',
        tiempo_5: 'Less than 10 minutes',
        tiempo_25: '20 to 30 minutes',
        tiempo_45: 'Less than 1 hour',
        tiempo_90: 'More than 1 hour',
        etiquetas_label: 'Tags',
        etiquetas_placeholder: 'Type to search or create tags...',
        guardar_borrador: 'Save Draft',
        publicar_receta: 'Publish Recipe',
        publicando: 'Publishing...',
        upload_title: 'Drag and drop your files here',
        upload_desc: 'Images (JPG, PNG, GIF, WebP) and videos (MP4, MKV, AVI, MOV, WebM)',
        upload_limit: 'Max: 1 video (40min, 1080p) and 5 images',
        seleccionar_archivos: 'Select files',
        confirmar_titulo: 'Confirm publication',
        confirmar_pregunta: 'Are you sure you want to publish this recipe?',
        confirmar_advertencia: 'Once published, it will be visible to all users.',
        cancelar: 'Cancel',
        publicar: 'Publish',
        procesando: 'Processing...',
        error_video_formato: 'Only MP4 videos are allowed',
        error_video_duracion: 'Video cannot exceed 40 minutes',
        error_video_resolucion: 'Maximum resolution is 1920x1080',
        error_video_corrupto: 'Could not read video (unsupported or corrupted format)',
        error_archivos_tipo: 'Only images (JPG, PNG, GIF, WebP) and MP4 videos are allowed. Some files were omitted.',
        error_archivo_duplicado: 'File "%s" is already added.',
        error_max_video: 'Only 1 video allowed. Video "%s" was not added.',
        error_max_imagenes: 'Only 5 images allowed. Image "%s" was not added.',
        error_campos_obligatorios: 'Title, description, preparation and ingredients are required',
        error_archivos_minimo: 'You must select at least one image or video',
        error_limites: 'Cannot upload more than 1 video and 5 images.',
        error_datos_vacios: 'No data to save',
        exito_publicada: 'Recipe published successfully!',
        exito_borrador: 'Draft saved successfully',
        error_publicar: 'Error publishing recipe',
        error_guardar: 'Error saving recipe',
        error_conexion: 'Connection error',
        borrador_cargado: 'Draft loaded automatically',
        borrador_guardado: 'Draft saved locally'
    }
};

function applyLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('lang', lang);
    const t = TEXTS[lang] || TEXTS.es;
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (t[key] !== undefined) el.textContent = t[key];
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (t[key] !== undefined) el.placeholder = t[key];
    });
    document.querySelectorAll('.select-selected').forEach(el => {
        const val = el.getAttribute('data-value');
        if (val === 'facil') el.textContent = t.dificultad_facil;
        else if (val === 'media') el.textContent = t.dificultad_media;
        else if (val === 'dificil') el.textContent = t.dificultad_dificil;
        else if (val === '5') el.textContent = t.tiempo_5;
        else if (val === '25') el.textContent = t.tiempo_25;
        else if (val === '45') el.textContent = t.tiempo_45;
        else if (val === '90') el.textContent = t.tiempo_90;
    });
    document.querySelectorAll('.select-items div').forEach(el => {
        const val = el.getAttribute('data-value');
        if (val === 'facil') el.textContent = t.dificultad_facil;
        else if (val === 'media') el.textContent = t.dificultad_media;
        else if (val === 'dificil') el.textContent = t.dificultad_dificil;
        else if (val === '5') el.textContent = t.tiempo_5;
        else if (val === '25') el.textContent = t.tiempo_25;
        else if (val === '45') el.textContent = t.tiempo_45;
        else if (val === '90') el.textContent = t.tiempo_90;
    });
}

function initLanguage() {
    const savedLang = localStorage.getItem('lang') || 'es';
    applyLanguage(savedLang);
    const langSwitch = document.getElementById('language-switch');
    if (langSwitch) {
        langSwitch.addEventListener('click', () => {
            const newLang = currentLang === 'es' ? 'en' : 'es';
            applyLanguage(newLang);
        });
    }
}

function mostrarMensaje(texto, tipo, params = []) {
    const t = TEXTS[currentLang];
    let mensaje = texto;
    if (t[texto]) {
        mensaje = t[texto];
        params.forEach((param, idx) => {
            mensaje = mensaje.replace(`%s`, param);
        });
    }
    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.innerHTML = mensaje;
    mensajeDiv.className = `mensaje ${tipo}`;
    mensajeDiv.style.display = 'block';
    setTimeout(() => mensajeDiv.style.display = 'none', 5000);
}

function guardarBorradorLocal() {
    const t = TEXTS[currentLang];
    const borrador = {
        titulo: document.getElementById('titulo').value,
        descripcion: document.getElementById('descripcion').value,
        preparacion: document.getElementById('preparacion').value,
        ingredientes: document.getElementById('ingredientes').value,
        dificultad: document.getElementById('dificultad').value,
        tiempo_preparacion: document.getElementById('tiempo_preparacion').value,
        etiquetas: document.getElementById('etiquetas-hidden').value,
        receta_id: window.recetaIdTemp || document.getElementById('receta_id_temp').value,
        timestamp: new Date().getTime()
    };
    localStorage.setItem('borrador_receta', JSON.stringify(borrador));
    mostrarMensaje('borrador_guardado', 'success');
}

function cargarBorrador() {
    const borradorGuardado = localStorage.getItem('borrador_receta');
    if (borradorGuardado) {
        const borrador = JSON.parse(borradorGuardado);
        if (borrador.titulo) document.getElementById('titulo').value = borrador.titulo;
        if (borrador.descripcion) document.getElementById('descripcion').value = borrador.descripcion;
        if (borrador.preparacion) document.getElementById('preparacion').value = borrador.preparacion;
        if (borrador.ingredientes) document.getElementById('ingredientes').value = borrador.ingredientes;
        if (borrador.dificultad) document.getElementById('dificultad').value = borrador.dificultad;
        if (borrador.tiempo_preparacion) document.getElementById('tiempo_preparacion').value = borrador.tiempo_preparacion;
        if (borrador.etiquetas && typeof cargarEtiquetasDesdeIds === 'function') {
            cargarEtiquetasDesdeIds(borrador.etiquetas.split(','));
        }
        if (borrador.receta_id) {
            document.getElementById('receta_id_temp').value = borrador.receta_id;
            window.recetaIdTemp = borrador.receta_id;
        }
        mostrarMensaje('borrador_cargado', 'info');
    }
}

function validarVideo(file) {
    const t = TEXTS[currentLang];
    return new Promise((resolve, reject) => {
        if (file.type !== 'video/mp4') {
            reject('error_video_formato');
            return;
        }
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.onloadedmetadata = () => {
            URL.revokeObjectURL(video.src);
            const duration = video.duration;
            const width = video.videoWidth;
            const height = video.videoHeight;
            if (duration > 2400) {
                reject('error_video_duracion');
            } else if (width > 1920 || height > 1080) {
                reject('error_video_resolucion');
            } else {
                resolve();
            }
        };
        video.onerror = () => reject('error_video_corrupto');
        video.src = URL.createObjectURL(file);
    });
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
    initLanguage();
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

    function actualizarContadores() {
        videoCount = selectedFiles.filter(f => f.type === 'video/mp4').length;
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
            const isVideo = file.type === 'video/mp4';
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
                        mostrarMensaje('error_video_duracion', 'error');
                        setTimeout(() => removeBtn.click(), 100);
                    }
                };
                previewItem.appendChild(video);
            } else {
                const img = document.createElement('img');
                img.src = blobUrl;
                previewItem.appendChild(img);
            }
            previewContainer.appendChild(previewItem);
        });
    }

    function esArchivoDuplicado(nuevoFile) {
        return selectedFiles.some(existente =>
            existente.name === nuevoFile.name &&
         