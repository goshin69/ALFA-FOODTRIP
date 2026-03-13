
const btn = document.getElementById("btnSeguir");

btn.addEventListener("click", function() {
    if (!btn.classList.contains("siguiendo")) {
        btn.classList.add("siguiendo");
        btn.innerText = "Siguiendo";
    } else {
        btn.classList.remove("siguiendo");
        btn.innerText = "Seguir";
    }
});


        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del perfil
            const profileImage = document.getElementById('profile-image');
            const userName = document.querySelector('.user-name');
            const userBio = document.querySelector('.bio-text');
            const userBirthday = document.querySelector('.birthday-date');
            
            // Botón editar
            const editButton = document.querySelector('.btn-edit');
            
            // Panel y overlay
            const editPanel = document.querySelector('.edit-panel');
            const overlay = document.querySelector('.overlay');
            
            // Campos del formulario
            const editName = document.getElementById('edit-name');
            const editBio = document.getElementById('edit-bio');
            const editBirthday = document.getElementById('edit-birthday');
            
            // Elementos de foto
            const photoInput = document.getElementById('edit-photo-input');
            const photoPreview = document.getElementById('photo-preview');
            
            // Botones guardar y cancelar
            const saveButton = document.querySelector('.btn-save');
            const cancelButton = document.querySelector('.btn-cancel');

            // Variable para almacenar la nueva foto seleccionada (objeto File)
            let selectedPhotoFile = null;

            // Función para cargar datos del perfil desde localStorage
            function loadProfileData() {
                const savedName = localStorage.getItem('profileName');
                const savedBio = localStorage.getItem('profileBio');
                const savedBirthday = localStorage.getItem('profileBirthday');
                const savedPhoto = localStorage.getItem('profilePhoto');
                if (savedName) userName.textContent = savedName;
                if (savedBio) userBio.textContent = savedBio;
                if (savedBirthday) userBirthday.textContent = savedBirthday;
                if (savedPhoto) profileImage.src = savedPhoto;
            }

            // Variable para determinar si es el perfil propio (cambiar a false para perfil visitante)
            const isOwnProfile = true; // true para propietario, false para visitante

            // Mostrar/ocultar botones según el modo
            if (isOwnProfile) {
                editButton.style.display = 'inline-block';
                btnSeguir.style.display = 'none';
            } else {
                editButton.style.display = 'none';
                btnSeguir.style.display = 'inline-block';
                // Lógica para el botón seguir en perfil visitante
                btnSeguir.addEventListener('click', function() {
                    if (btnSeguir.classList.contains('siguiendo')) {
                        btnSeguir.classList.remove('siguiendo');
                        btnSeguir.textContent = 'Seguir';
                    } else {
                        btnSeguir.classList.add('siguiendo');
                        btnSeguir.textContent = 'Siguiendo';
                    }
                });
            }

            // Función para formatear fecha de DD/MM/AAAA a YYYY-MM-DD para input date
            function formatDateToInput(dateStr) {
                if (!dateStr) return '';
                const parts = dateStr.split('/');
                if (parts.length === 3) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
                return '';
            }

            // Función para formatear fecha de YYYY-MM-DD a DD/MM/AAAA
            function formatDateToDisplay(dateStr) {
                if (!dateStr) return '';
                const parts = dateStr.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                return '';
            }

            // Abrir panel con los datos actuales
            function openEditPanel() {
                editName.value = userName.textContent;
                editBio.value = userBio.textContent;
                // Convertir fecha actual al formato del input date
                const currentDate = userBirthday.textContent.trim();
                editBirthday.value = formatDateToInput(currentDate);
                
                // Restablecer la vista previa a la foto actual
                photoPreview.src = profileImage.src;
                selectedPhotoFile = null; // Limpiar selección previa
                photoInput.value = ''; // Limpiar input file
                
                editPanel.classList.add('active');
                overlay.classList.add('active');
            }

            // Cerrar panel sin guardar
            function closeEditPanel() {
                editPanel.classList.remove('active');
                overlay.classList.remove('active');
            }

            // Manejar selección de nueva foto
            photoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Verificar que sea una imagen
                    if (file.type.startsWith('image/')) {
                        selectedPhotoFile = file;
                        // Mostrar vista previa
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            photoPreview.src = event.target.result;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        alert('Por favor selecciona un archivo de imagen válido.');
                        photoInput.value = '';
                    }
                }
            });

            // Guardar cambios
            function saveChanges() {
                const newName = editName.value.trim();
                const newBio = editBio.value.trim();
                const newBirthday = editBirthday.value;

                if (newName) {
                    userName.textContent = newName;
                }
                if (newBio) {
                    userBio.textContent = newBio;
                }
                if (newBirthday) {
                    userBirthday.textContent = formatDateToDisplay(newBirthday);
                }

                // Guardar datos de texto en localStorage
                localStorage.setItem('profileName', userName.textContent);
                localStorage.setItem('profileBio', userBio.textContent);
                localStorage.setItem('profileBirthday', userBirthday.textContent);

                // Si hay una nueva foto seleccionada, actualizar la imagen de perfil y guardar
                if (selectedPhotoFile) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        profileImage.src = event.target.result;
                        localStorage.setItem('profilePhoto', event.target.result);
                    };
                    reader.readAsDataURL(selectedPhotoFile);
                } else {
                    // Si no hay nueva foto, guardar la actual
                    localStorage.setItem('profilePhoto', profileImage.src);
                }

                closeEditPanel();
            }

            // Eventos
            editButton.addEventListener('click', openEditPanel);
            saveButton.addEventListener('click', saveChanges);
            cancelButton.addEventListener('click', closeEditPanel);
            overlay.addEventListener('click', closeEditPanel);
        });
    

