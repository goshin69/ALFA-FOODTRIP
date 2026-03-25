document.addEventListener('DOMContentLoaded', function() {
    const btnSeguir = document.getElementById('btnSeguir');
    if (btnSeguir) {
        btnSeguir.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const isFollowing = this.classList.contains('siguiendo');
            const formData = new FormData();
            formData.append('seguido_id', userId);
            formData.append('action', isFollowing ? 'unfollow' : 'follow');

            fetch('api/seguir.php', { method: 'POST', credentials: 'include', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        if (data.seguidores !== undefined) document.getElementById('followers-count').textContent = data.seguidores;
                        if (data.siguiendo) {
                            btnSeguir.classList.add('siguiendo');
                            btnSeguir.textContent = 'Siguiendo';
                        } else {
                            btnSeguir.classList.remove('siguiendo');
                            btnSeguir.textContent = 'Seguir';
                        }
                    } else {
                        alert(data.error || 'Error al procesar');
                    }
                })
                .catch(err => console.error(err));
        });
    }

    const editButton = document.querySelector('.btn-edit');
    if (editButton) {
        const editPanel = document.querySelector('.edit-panel');
        const overlay = document.querySelector('.overlay');
        const saveButton = document.querySelector('.btn-save');
        const cancelButton = document.querySelector('.btn-cancel');
        const photoInput = document.getElementById('edit-photo-input');
        const photoPreview = document.getElementById('photo-preview');
        const editName = document.getElementById('edit-name');
        const editBio = document.getElementById('edit-bio');
        const editBirthday = document.getElementById('edit-birthday');
        const profileImg = document.getElementById('profile-image');
        const profileName = document.querySelector('.user-name');
        const profileBio = document.querySelector('.bio-text');
        const profileBirthday = document.querySelector('.birthday-date');

        let selectedPhotoFile = null;

        function formatDateToInput(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('/');
            if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
            return '';
        }

        function formatDateToDisplay(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
            return '';
        }

        function openPanel() {
            editName.value = profileName.textContent;
            editBio.value = profileBio.textContent;
            editBirthday.value = formatDateToInput(profileBirthday.textContent);
            photoPreview.src = profileImg.src;
            selectedPhotoFile = null;
            photoInput.value = '';
            editPanel.classList.add('active');
            overlay.classList.add('active');
        }

        function closePanel() {
            editPanel.classList.remove('active');
            overlay.classList.remove('active');
        }

        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                selectedPhotoFile = file;
                const reader = new FileReader();
                reader.onload = ev => { photoPreview.src = ev.target.result; };
                reader.readAsDataURL(file);
            } else if (file) {
                alert('Por favor selecciona un archivo de imagen válido.');
                photoInput.value = '';
            }
        });

        function saveChanges() {
            const formData = new FormData();
            formData.append('nombre', editName.value.trim());
            formData.append('biografia', editBio.value.trim());
            formData.append('fecha_nacimiento', editBirthday.value);
            if (selectedPhotoFile) formData.append('imagen_perfil', selectedPhotoFile);

            fetch('api/actualizar_perfil.php', { method: 'POST', credentials: 'include', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        profileName.textContent = data.nombre;
                        profileBio.textContent = data.biografia;
                        profileBirthday.textContent = data.fecha_nacimiento ? formatDateToDisplay(data.fecha_nacimiento) : 'No especificada';
                        if (data.imagen_perfil) profileImg.src = data.imagen_perfil;
                        closePanel();
                    } else {
                        alert(data.error || 'Error al guardar');
                    }
                })
                .catch(err => console.error(err));
        }

        editButton.addEventListener('click', openPanel);
        saveButton.addEventListener('click', saveChanges);
        cancelButton.addEventListener('click', closePanel);
        overlay.addEventListener('click', closePanel);
    }
});