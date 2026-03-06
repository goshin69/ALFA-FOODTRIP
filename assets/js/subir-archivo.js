const uploadBox = document.getElementById('uploadBox');
const fileInput = document.getElementById('fileInput');
const uploadBtn = document.getElementById('uploadBtn');
const submitBtn = document.getElementById('submitBtn');
const cancelBtn = document.getElementById('cancelBtn');
const filePreview = document.getElementById('filePreview');
const previewSection = document.getElementById('previewSection');
const submitSection = document.getElementById('submitSection');
const loading = document.getElementById('loading');
const successMessage = document.getElementById('successMessage');
const errorMessage = document.getElementById('errorMessage');

let selectedFiles = [];

// Click en el botón para abrir el selector de archivos
uploadBtn.addEventListener('click', () => {
    fileInput.click();
});

// Cuando se seleccionan archivos
fileInput.addEventListener('change', (e) => {
    selectedFiles = Array.from(e.target.files);
    displayPreviews();
});

// Drag and drop
uploadBox.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadBox.classList.add('drag-over');
});

uploadBox.addEventListener('dragleave', () => {
    uploadBox.classList.remove('drag-over');
});

uploadBox.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadBox.classList.remove('drag-over');
    selectedFiles = Array.from(e.dataTransfer.files);
    displayPreviews();
});

// Mostrar previsualizaciones
function displayPreviews() {
    filePreview.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
        const isVideo = file.type.startsWith('video/');
        const isImage = file.type.startsWith('image/');

        if (isVideo || isImage) {
            const div = document.createElement('div');
            div.className = 'preview-item';

            const reader = new FileReader();
            reader.onload = (e) => {
                if (isVideo) {
                    const video = document.createElement('video');
                    video.src = e.target.result;
                    div.appendChild(video);
                } else {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    div.appendChild(img);
                }
            };
            reader.readAsDataURL(file);

            const nameDiv = document.createElement('div');
            nameDiv.className = 'preview-item-name';
            nameDiv.title = file.name;
            nameDiv.textContent = file.name;
            div.appendChild(nameDiv);

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-btn';
            removeBtn.textContent = '✕';
            removeBtn.type = 'button';
            removeBtn.addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                displayPreviews();
            });
            div.appendChild(removeBtn);

            filePreview.appendChild(div);
        }
    });

    if (selectedFiles.length > 0) {
        previewSection.style.display = 'block';
        submitSection.style.display = 'flex';
    } else {
        previewSection.style.display = 'none';
        submitSection.style.display = 'none';
    }
}

// Enviar archivos
submitBtn.addEventListener('click', async () => {
    if (selectedFiles.length === 0) return;

    loading.style.display = 'block';
    successMessage.style.display = 'none';
    errorMessage.style.display = 'none';

    const formData = new FormData();
    selectedFiles.forEach(file => {
        formData.append('files[]', file);
    });

    try {
        const response = await fetch('api/subir_archivos.php', {
            method: 'POST',
            body: formData
        });

        const text = await response.text();

        if (response.ok && !text.includes('error')) {
            loading.style.display = 'none';
            successMessage.style.display = 'block';
            selectedFiles = [];
            fileInput.value = '';
            previewSection.style.display = 'none';
            submitSection.style.display = 'none';
            filePreview.innerHTML = '';
            
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
        } else {
            throw new Error(text || 'Error en la subida');
        }
    } catch (err) {
        loading.style.display = 'none';
        errorMessage.textContent = '✗ ' + err.message;
        errorMessage.style.display = 'block';
        console.error(err);
    }
});

// Limpiar
cancelBtn.addEventListener('click', () => {
    selectedFiles = [];
    fileInput.value = '';
    previewSection.style.display = 'none';
    submitSection.style.display = 'none';
    filePreview.innerHTML = '';
    successMessage.style.display = 'none';
    errorMessage.style.display = 'none';
});
