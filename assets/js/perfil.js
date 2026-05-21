function initPerfil(userId, baseUrl, esDueno, loggedIn) {
    const grid = document.getElementById('publicationsGrid');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const orderSelect = document.getElementById('orderSelect');
    const btnSeguir = document.getElementById('btnSeguir');
    const modalOverlay = document.getElementById('modalOverlay');
    const modalLista = document.getElementById('modalLista');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const closeModal = document.querySelector('.close-modal');

    let currentTab = 'publicaciones';
    let currentOrder = orderSelect ? orderSelect.value : 'recientes';

    function requiereLogin() {
        if (!loggedIn) {
            window.location.href = 'login.php';
            return true;
        }
        return false;
    }

    function cargarGrid() {
        if (!grid) return;
        grid.innerHTML = '<div class="loading-spinner"><i class="fas fa-utensils fa-spin"></i> Cargando...</div>';
        let url = `api/perfil.php?action=get_tab&user_id=${userId}&tab=${currentTab}`;
        if (currentTab === 'publicaciones') {
            url += `&order=${currentOrder}`;
        }
        fetch(url)
            .then(res => res.text())
            .then(html => { grid.innerHTML = html; })
            .catch(() => { grid.innerHTML = '<p>Error al cargar contenido.</p>'; });
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentTab = this.dataset.tab;
            const orderForm = document.getElementById('orderForm');
            if (orderForm) {
                orderForm.style.display = currentTab === 'publicaciones' ? 'inline-block' : 'none';
            }
            cargarGrid();
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    const urlTab = urlParams.get('tab');
    if (urlTab && esDueno && ['favoritos','me_gusta','historial'].includes(urlTab)) {
        const btn = document.querySelector(`.tab-btn[data-tab="${urlTab}"]`);
        if (btn) btn.click();
    } else {
        cargarGrid();
    }

    if (orderSelect) {
        orderSelect.addEventListener('change', function() {
            currentOrder = this.value;
            if (currentTab === 'publicaciones') cargarGrid();
        });
    }

    if (btnSeguir) {
        btnSeguir.addEventListener('click', function() {
            if (requiereLogin()) return;
            const seguidoId = this.dataset.userId;
            const isSiguiendo = this.classList.contains('siguiendo');
            const action = isSiguiendo ? 'unfollow' : 'follow';

            fetch('api/perfil.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${action}&seguido_id=${seguidoId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    if (data.siguiendo) {
                        btnSeguir.classList.add('siguiendo');
                        btnSeguir.textContent = 'Siguiendo';
                    } else {
                        btnSeguir.classList.remove('siguiendo');
                        btnSeguir.textContent = 'Seguir';
                    }
                    const seguidoresSpan = document.querySelector('.stat.clickable[data-modal="seguidores"] .stat-number');
                    if (seguidoresSpan) seguidoresSpan.textContent = data.seguidores;
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión');
            });
        });
    }

    function mostrarModal(tipo) {
        if (requiereLogin()) return;
        const url = `api/perfil.php?action=get_${tipo}&user_id=${userId}`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    modalTitle.textContent = tipo === 'followers' ? 'Seguidores' : 'Siguiendo';
                    let html = '<ul>';
                    data.usuarios.forEach(u => {
                        const img = u.imagen_perfil ? baseUrl + u.imagen_perfil : baseUrl + 'assets/img/Logo Sesion.png';
                        html += `<li><a href="perfil.php?id=${u.id}"><img src="${img}" width="30" style="border-radius:50%; vertical-align:middle;"> ${escapeHtml(u.nombre)}</a></li>`;
                    });
                    html += '</ul>';
                    modalBody.innerHTML = html;
                    modalOverlay.style.display = 'block';
                    modalLista.style.display = 'block';
                } else {
                    alert('Error al cargar la lista');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión');
            });
    }

    document.querySelectorAll('.stat.clickable').forEach(stat => {
        stat.addEventListener('click', function() {
            const tipo = this.dataset.modal;
            mostrarModal(tipo === 'seguidores' ? 'followers' : 'following');
        });
    });

    if (closeModal) {
        closeModal.addEventListener('click', function() {
            modalOverlay.style.display = 'none';
            modalLista.style.display = 'none';
        });
    }
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function() {
            this.style.display = 'none';
            modalLista.style.display = 'none';
        });
    }

    if (esDueno) {
        const btnEdit = document.querySelector('.btn-edit');
        const editPanel = document.querySelector('.edit-panel');
        const overlay = document.querySelector('.overlay');
        const btnCancel = document.querySelector('.btn-cancel');
        const btnSave = document.querySelector('.btn-save');
        const photoInput = document.getElementById('edit-photo-input');
        const photoPreview = document.getElementById('photo-preview');

        if (btnEdit && editPanel && overlay) {
            btnEdit.addEventListener('click', () => {
                editPanel.style.display = 'block';
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
            function cerrarPanel() {
                editPanel.style.display = 'none';
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }
            if (btnCancel) btnCancel.addEventListener('click', cerrarPanel);
            overlay.addEventListener('click', cerrarPanel);

            if (photoInput && photoPreview) {
                photoInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = ev => { photoPreview.src = ev.target.result; };
                        reader.readAsDataURL(file);
                    }
                });
            }

            if (btnSave) {
                btnSave.addEventListener('click', function() {
                    const formData = new FormData();
                    formData.append('nombre', document.getElementById('edit-name').value);
                    formData.append('biografia', document.getElementById('edit-bio').value);
                    formData.append('fecha_nacimiento', document.getElementById('edit-birthday').value);
                    if (photoInput && photoInput.files[0]) {
                        formData.append('imagen_perfil', photoInput.files[0]);
                    }

                    btnSave.disabled = true;
                    btnSave.textContent = 'Guardando...';

                    fetch('api/actualizar_perfil.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.ok) {
                            if (data.nombre) document.querySelector('.user-name').textContent = data.nombre;
                            if (data.imagen_perfil) document.getElementById('profile-image').src = data.imagen_perfil;
                            if (data.biografia != null) document.querySelector('.bio-text').innerHTML = data.biografia.replace(/\n/g, '<br>');
                            if (data.fecha_nacimiento) {
                                const parts = data.fecha_nacimiento.split('-');
                                document.querySelector('.birthday-date').textContent = parts[2] + '/' + parts[1] + '/' + parts[0];
                            }
                            cerrarPanel();
                        } else {
                            alert('Error: ' + (data.error || 'No se pudo actualizar'));
                        }
                    })
                    .catch(() => alert('Error de conexión'))
                    .finally(() => {
                        btnSave.disabled = false;
                        btnSave.textContent = 'Guardar';
                        if (photoInput) photoInput.value = '';
                    });
                });
            }
        }
    }

    const header = document.querySelector('header');
    const main = document.querySelector('main');
    if (header && main) {
        function setPadding() {
            main.style.paddingTop = header.offsetHeight + 'px';
        }
        setPadding();
        window.addEventListener('resize', setPadding);
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.perfilData !== 'undefined') {
        initPerfil(
            window.perfilData.userId,
            window.perfilData.baseUrl,
            window.perfilData.esDueno,
            window.perfilData.loggedIn
        );
    }
});