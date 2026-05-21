(function() {
    const recetaId = new URLSearchParams(window.location.search).get('id');
    let currentLang = localStorage.getItem('lang') || 'es';
    const usuarioActualId = document.body.dataset.usuarioId ? parseInt(document.body.dataset.usuarioId) : null;
    const usuarioActualRol = document.body.dataset.usuarioRol || 'usuario';

    const TEXTS = {
        es: {
            titulo: '', fecha: '', tiempo: '', dificultad: '', vistas: 'vistas',
            desc_titulo: 'Descripción', ing_titulo: 'Ingredientes', prep_titulo: 'Preparación',
            galeria: 'Galería', etiquetas: 'Etiquetas', comentarios: 'Comentarios',
            enviar: 'Enviar comentario', cancelar: 'Cancelar',
            login_comentar: 'Inicia sesión para comentar.',
            denunciar_titulo: 'Denunciar receta', motivo: 'Motivo:',
            m_acoso: 'Acoso', m_spam: 'Spam', m_inapropiado: 'Contenido inapropiado',
            m_derechos: 'Infracción de derechos de autor', m_violencia: 'Violencia o lenguaje ofensivo',
            m_suplantacion: 'Suplantación de identidad', m_otro: 'Otro',
            detalle: 'Detalle (opcional):', enviar_denuncia: 'Enviar denuncia',
            confirmar_eliminar: 'Confirmar eliminación', confirmar_texto: '¿Estás seguro de que deseas eliminar esta receta?',
            mas_acciones: 'Más acciones', eliminar: 'Eliminar receta', denunciar: 'Denunciar receta',
            comentario_placeholder: 'Escribe un comentario...',
            responder: 'Responder', denunciar_comentario: 'Denunciar comentario', eliminar_comentario: 'Eliminar comentario',
            motivo_denuncia_comentario: 'Motivo de denuncia:',
            acoso: 'Acoso', spam: 'Spam', inapropiado: 'Contenido inapropiado',
            odio: 'Discurso de odio', falso: 'Información falsa', otro: 'Otro',
            enviar_denuncia_comentario: 'Enviar denuncia', denunciar_comentario_titulo: 'Denunciar comentario',
            ver_respuestas: 'Ver %s respuestas', ocultar_respuestas: 'Ocultar respuestas'
        },
        en: {
            titulo: '', fecha: '', tiempo: '', dificultad: '', vistas: 'views',
            desc_titulo: 'Description', ing_titulo: 'Ingredients', prep_titulo: 'Preparation',
            galeria: 'Gallery', etiquetas: 'Tags', comentarios: 'Comments',
            enviar: 'Post comment', cancelar: 'Cancel',
            login_comentar: 'Log in to comment.',
            denunciar_titulo: 'Report recipe', motivo: 'Reason:',
            m_acoso: 'Harassment', m_spam: 'Spam', m_inapropiado: 'Inappropriate',
            m_derechos: 'Copyright infringement', m_violencia: 'Violence or offensive language',
            m_suplantacion: 'Impersonation', m_otro: 'Other',
            detalle: 'Details (optional):', enviar_denuncia: 'Send report',
            confirmar_eliminar: 'Confirm deletion', confirmar_texto: 'Are you sure you want to delete this recipe?',
            mas_acciones: 'More actions', eliminar: 'Delete recipe', denunciar: 'Report recipe',
            comentario_placeholder: 'Write a comment...',
            responder: 'Reply', denunciar_comentario: 'Report comment', eliminar_comentario: 'Delete comment',
            motivo_denuncia_comentario: 'Report reason:',
            acoso: 'Harassment', spam: 'Spam', inapropiado: 'Inappropriate',
            odio: 'Hate speech', falso: 'Misinformation', otro: 'Other',
            enviar_denuncia_comentario: 'Send report', denunciar_comentario_titulo: 'Report comment',
            ver_respuestas: 'View %s replies', ocultar_respuestas: 'Hide replies'
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
        document.querySelectorAll('.respuestas-toggle').forEach(btn => {
            const count = parseInt(btn.dataset.count);
            if (btn.classList.contains('show')) {
                btn.textContent = t.ocultar_respuestas;
            } else {
                btn.textContent = t.ver_respuestas.replace('%s', count);
            }
        });
    }

    function showNotification(msg, isError = false) {
        const notif = document.getElementById('notificacion');
        if (!notif) return;
        notif.textContent = msg;
        notif.style.backgroundColor = isError ? '#dc3545' : '#28a745';
        notif.classList.remove('hidden');
        setTimeout(() => notif.classList.add('hidden'), 4000);
    }

    function loadComments() {
        fetch(`api/receta.php?action=comentarios&receta_id=${recetaId}`)
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    renderComments(data.comentarios);
                    const countSpan = document.getElementById('comentarios-count');
                    if (countSpan) countSpan.textContent = data.comentarios.length;
                }
            })
            .catch(err => console.error(err));
    }

    function buildCommentTree(comentarios) {
        const map = {};
        const roots = [];
        comentarios.forEach(c => { map[c.id] = { ...c, children: [] }; });
        comentarios.forEach(c => {
            if (c.parent_id === 0) roots.push(map[c.id]);
            else if (map[c.parent_id]) map[c.parent_id].children.push(map[c.id]);
        });
        return roots;
    }

    function renderComments(comentarios) {
        const container = document.getElementById('lista-comentarios');
        if (!container) return;
        const tree = buildCommentTree(comentarios);
        container.innerHTML = '';
        tree.forEach(root => {
            renderCommentNode(root, container, 0);
        });
        attachComentarioEventos();
    }

    function renderCommentNode(comment, parentEl, level) {
        const t = TEXTS[currentLang];
        const isAuthor = (usuarioActualId === comment.usuario_id);
        const canModerate = (usuarioActualRol === 'admin' || usuarioActualRol === 'moderador');
        const div = document.createElement('div');
        div.className = 'comentario';
        div.dataset.id = comment.id;
        div.innerHTML = `
            <div class="comentario-header">
                <span class="comentario-nombre">${escapeHtml(comment.usuario_nombre)}</span>
                <span class="comentario-fecha">${new Date(comment.fecha).toLocaleString()}</span>
                <div class="comentario-acciones">
                    <button class="btn-comentario-acciones" data-id="${comment.id}" data-nombre="${escapeHtml(comment.usuario_nombre)}">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <div class="comentario-dropdown hidden" data-id="${comment.id}">
                        <button class="accion-responder" data-id="${comment.id}" data-nombre="${escapeHtml(comment.usuario_nombre)}">${t.responder}</button>
                        <button class="accion-denunciar" data-id="${comment.id}">${t.denunciar_comentario}</button>
                        ${(isAuthor || canModerate) ? `<button class="accion-eliminar" data-id="${comment.id}">${t.eliminar_comentario}</button>` : ''}
                    </div>
                </div>
            </div>
            <div class="comentario-texto">${escapeHtml(comment.contenido)}</div>
        `;
        if (comment.children && comment.children.length > 0) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'respuestas-toggle';
            toggleBtn.dataset.count = comment.children.length;
            toggleBtn.textContent = t.ver_respuestas.replace('%s', comment.children.length);
            const respuestasDiv = document.createElement('div');
            respuestasDiv.className = 'respuestas-container';
            comment.children.forEach(child => {
                renderCommentNode(child, respuestasDiv, level + 1);
            });
            toggleBtn.addEventListener('click', () => {
                respuestasDiv.classList.toggle('show');
                if (respuestasDiv.classList.contains('show')) {
                    toggleBtn.textContent = t.ocultar_respuestas;
                } else {
                    toggleBtn.textContent = t.ver_respuestas.replace('%s', comment.children.length);
                }
            });
            div.appendChild(toggleBtn);
            div.appendChild(respuestasDiv);
        }
        parentEl.appendChild(div);
    }

    function attachComentarioEventos() {
        document.querySelectorAll('.btn-comentario-acciones').forEach(btn => {
            btn.removeEventListener('click', toggleDropdown);
            btn.addEventListener('click', toggleDropdown);
        });
        document.querySelectorAll('.accion-responder').forEach(btn => {
            btn.removeEventListener('click', responderHandler);
            btn.addEventListener('click', responderHandler);
        });
        document.querySelectorAll('.accion-denunciar').forEach(btn => {
            btn.removeEventListener('click', denunciarComentarioHandler);
            btn.addEventListener('click', denunciarComentarioHandler);
        });
        document.querySelectorAll('.accion-eliminar').forEach(btn => {
            btn.removeEventListener('click', eliminarComentarioHandler);
            btn.addEventListener('click', eliminarComentarioHandler);
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.comentario-acciones')) {
                document.querySelectorAll('.comentario-dropdown').forEach(dd => dd.classList.add('hidden'));
            }
        });
    }

    function toggleDropdown(e) {
        e.stopPropagation();
        const dropdown = this.parentElement.querySelector('.comentario-dropdown');
        document.querySelectorAll('.comentario-dropdown').forEach(dd => {
            if (dd !== dropdown) dd.classList.add('hidden');
        });
        dropdown.classList.toggle('hidden');
    }

    function responderHandler(e) {
        const id = this.dataset.id;
        const nombre = this.dataset.nombre;
        document.getElementById('comentario-parent-id').value = id;
        const respuestaSpan = document.getElementById('respuesta-nombre');
        if (respuestaSpan) respuestaSpan.textContent = nombre;
        const respuestaDiv = document.getElementById('respuesta-a');
        if (respuestaDiv) respuestaDiv.classList.remove('hidden');
        document.getElementById('comentario-texto').focus();
        this.closest('.comentario-dropdown').classList.add('hidden');
    }

    function denunciarComentarioHandler(e) {
        const comentarioId = this.dataset.id;
        const modal = document.getElementById('modal-denuncia-comentario');
        if (modal) {
            modal.dataset.comentarioId = comentarioId;
            modal.classList.remove('hidden');
        }
        this.closest('.comentario-dropdown').classList.add('hidden');
    }

    function eliminarComentarioHandler(e) {
        const comentarioId = this.dataset.id;
        if (confirm('¿Eliminar este comentario permanentemente?')) {
            fetch('api/receta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=eliminar_comentario&comentario_id=${comentarioId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    showNotification('Comentario eliminado');
                    loadComments();
                } else showNotification(data.error, true);
            })
            .catch(() => showNotification('Error', true));
        }
        this.closest('.comentario-dropdown').classList.add('hidden');
    }

    function initReportCommentForm() {
        const form = document.getElementById('form-denuncia-comentario');
        if (!form) return;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const comentarioId = document.getElementById('modal-denuncia-comentario').dataset.comentarioId;
            const motivo = document.getElementById('motivo-denuncia-comentario').value;
            const detalle = document.getElementById('detalle-denuncia-comentario').value;
            fetch('api/receta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=denunciar_comentario&comentario_id=${comentarioId}&motivo=${encodeURIComponent(motivo)}&detalle=${encodeURIComponent(detalle)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    showNotification(data.mensaje || 'Denuncia enviada');
                    document.getElementById('modal-denuncia-comentario').classList.add('hidden');
                    form.reset();
                } else showNotification(data.error, true);
            })
            .catch(() => showNotification('Error', true));
        });
        const cancelBtn = document.querySelector('#modal-denuncia-comentario .btn-cancelar');
        if (cancelBtn) cancelBtn.addEventListener('click', () => {
            document.getElementById('modal-denuncia-comentario').classList.add('hidden');
        });
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function initLightbox() {
        const items = document.querySelectorAll('.galeria-item');
        const modal = document.getElementById('lightbox-modal');
        const imgEl = document.getElementById('lightbox-img');
        const videoEl = document.getElementById('lightbox-video');
        let currentIndex = 0;
        function showItem(index) {
            const item = items[index];
            if (!item) return;
            const isVideo = item.tagName === 'VIDEO';
            if (isVideo) {
                imgEl.style.display = 'none';
                videoEl.style.display = 'block';
                videoEl.src = item.src;
                videoEl.load();
                videoEl.play().catch(e => console.log);
            } else {
                videoEl.style.display = 'none';
                imgEl.style.display = 'block';
                imgEl.src = item.src;
            }
        }
        items.forEach((item, idx) => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                currentIndex = idx;
                showItem(currentIndex);
                modal.classList.remove('hidden');
            });
        });
        const close = modal.querySelector('.lightbox-close');
        const prev = modal.querySelector('.lightbox-prev');
        const next = modal.querySelector('.lightbox-next');
        if (close) close.addEventListener('click', () => modal.classList.add('hidden'));
        if (prev) prev.addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + items.length) % items.length;
            showItem(currentIndex);
        });
        if (next) next.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % items.length;
            showItem(currentIndex);
        });
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });
        let touchStartX = 0;
        modal.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].screenX; });
        modal.addEventListener('touchend', (e) => {
            const diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) > 50) {
                if (diff > 0 && prev) prev.click();
                else if (next) next.click();
            }
        });
    }

    function initInteractions() {
        document.querySelectorAll('.btn-me-gusta, .btn-favorito').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.classList.contains('btn-me-gusta') ? 'like' : 'favorite';
                const icon = this.querySelector('i');
                const countSpan = this.querySelector('.count');
                fetch('api/receta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=${action}&receta_id=${recetaId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        if (data.activo) {
                            this.classList.add('activo');
                            icon.className = action === 'like' ? 'fa-solid fa-heart' : 'fa-solid fa-bookmark';
                        } else {
                            this.classList.remove('activo');
                            icon.className = action === 'like' ? 'fa-regular fa-heart' : 'fa-regular fa-bookmark';
                        }
                        countSpan.textContent = data.total;
                    } else showNotification(data.error || 'Error', true);
                })
                .catch(() => showNotification('Error de conexión', true));
            });
        });
    }

    function initCommentForm() {
        const form = document.getElementById('form-comentario');
        if (!form) return;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const contenido = document.getElementById('comentario-texto').value.trim();
            const parent_id = document.getElementById('comentario-parent-id').value;
            if (!contenido) return;
            fetch('api/receta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=comentar&receta_id=${recetaId}&contenido=${encodeURIComponent(contenido)}&parent_id=${parent_id}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    document.getElementById('comentario-texto').value = '';
                    document.getElementById('comentario-parent-id').value = '0';
                    const respuestaDiv = document.getElementById('respuesta-a');
                    if (respuestaDiv) respuestaDiv.classList.add('hidden');
                    loadComments();
                    showNotification('Comentario enviado');
                } else showNotification(data.error || 'Error', true);
            })
            .catch(() => showNotification('Error de conexión', true));
        });
        const cancelBtn = document.getElementById('cancelar-respuesta');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                document.getElementById('comentario-parent-id').value = '0';
                const respuestaDiv = document.getElementById('respuesta-a');
                if (respuestaDiv) respuestaDiv.classList.add('hidden');
            });
        }
    }

    function initDelete() {
        const btnEliminar = document.getElementById('btn-eliminar-receta');
        const modalEliminar = document.getElementById('modal-eliminar');
        if (!btnEliminar) return;
        btnEliminar.addEventListener('click', () => modalEliminar.classList.remove('hidden'));
        const cancelar = document.getElementById('cancelar-eliminar');
        const confirmar = document.getElementById('confirmar-eliminar');
        if (cancelar) cancelar.addEventListener('click', () => modalEliminar.classList.add('hidden'));
        if (confirmar) {
            confirmar.addEventListener('click', () => {
                fetch('api/receta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=eliminar&receta_id=${recetaId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        showNotification('Receta eliminada correctamente');
                        setTimeout(() => window.location.href = 'index.php', 1500);
                    } else showNotification(data.error, true);
                })
                .catch(() => showNotification('Error al eliminar', true));
                modalEliminar.classList.add('hidden');
            });
        }
    }

    function initReport() {
        const denunciaBtn = document.getElementById('btn-demandar');
        const modalDenuncia = document.getElementById('modal-denuncia');
        if (!denunciaBtn) return;
        denunciaBtn.addEventListener('click', () => modalDenuncia.classList.remove('hidden'));
        const cancelar = document.querySelector('#modal-denuncia .btn-cancelar');
        if (cancelar) cancelar.addEventListener('click', () => modalDenuncia.classList.add('hidden'));
        const formDenuncia = document.getElementById('form-denuncia');
        if (formDenuncia) {
            formDenuncia.addEventListener('submit', (e) => {
                e.preventDefault();
                const motivo = document.getElementById('motivo-denuncia').value;
                const detalle = document.getElementById('detalle-denuncia').value;
                fetch('api/receta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=denunciar&receta_id=${recetaId}&motivo=${encodeURIComponent(motivo)}&detalle=${encodeURIComponent(detalle)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        showNotification(data.mensaje || 'Denuncia enviada. Gracias.');
                        modalDenuncia.classList.add('hidden');
                        document.getElementById('detalle-denuncia').value = '';
                    } else showNotification(data.error, true);
                })
                .catch(() => showNotification('Error al enviar denuncia', true));
            });
        }
    }

    function initActionsDropdown() {
        const btn = document.getElementById('btn-acciones');
        const dropdown = document.getElementById('acciones-dropdown');
        if (!btn || !dropdown) return;
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });
        document.addEventListener('click', (e) => {
            if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    function initTheme() {
        const applyTheme = () => {
            const isDark = localStorage.getItem('theme') === 'dark';
            if (isDark) document.body.classList.add('dark-mode');
            else document.body.classList.remove('dark-mode');
        };
        applyTheme();
        const themeSwitch = document.getElementById('theme-switch');
        if (themeSwitch) {
            themeSwitch.addEventListener('click', () => {
                const isDark = document.body.classList.toggle('dark-mode');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
        }
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

    document.addEventListener('DOMContentLoaded', () => {
        initTheme();
        initLanguage();
        loadComments();
        initLightbox();
        initInteractions();
        initCommentForm();
        initDelete();
        initReport();
        initReportCommentForm();
        initActionsDropdown();
    });
})();