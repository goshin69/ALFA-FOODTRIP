(function() {
    const BASE_URL = '/ALFA/public/';
    let currentTab = 'reportes';
    let pendingAction = null;

    function loadTab(tab) {
        const container = document.getElementById('moderacion-contenido');
        container.innerHTML = '<div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';
        
        fetch(`${BASE_URL}api/moderacion.php?tab=${tab}&_=${Date.now()}`)
            .then(res => res.json())
            .then(data => {
                if (!data.ok) throw new Error(data.error || 'Error al cargar');
                renderTab(tab, data);
                updateCounters(data.counts);
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = `<div class="empty-state"><i class="fa-solid fa-circle-exclamation"></i> Error: ${err.message}</div>`;
            });
    }

    function renderTab(tab, data) {
        const container = document.getElementById('moderacion-contenido');
        const items = data.items || [];
        
        if (items.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fa-solid fa-inbox"></i> No hay elementos en esta sección.</div>`;
            return;
        }

        let html = '<div class="lista-items">';
        items.forEach(item => {
            html += `
                <div class="item-card" data-id="${item.id}" data-tipo="${tab}">
                    <div class="item-header">
                        <div class="item-title">
                            ${tab === 'sanciones' ? `<i class="fa-solid fa-user-slash"></i> ${escapeHtml(item.nombre_usuario)}` : `<a href="#" class="ver-receta-link" data-id="${item.id}">${escapeHtml(item.titulo)}</a>`}
                        </div>
                        <div class="item-meta">
                            ${item.fecha ? `<span><i class="fa-regular fa-calendar"></i> ${item.fecha}</span>` : ''}
                            ${item.estado ? `<span><i class="fa-solid fa-tag"></i> ${escapeHtml(item.estado)}</span>` : ''}
                        </div>
                    </div>
                    <div class="item-details">
                        ${item.detalle || (item.descripcion_corta ? escapeHtml(item.descripcion_corta) : '')}
                    </div>
                    <div class="item-actions">
                        ${renderActions(tab, item)}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;

        // Eventos para links de ver receta
        document.querySelectorAll('.ver-receta-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const recetaId = link.dataset.id;
                verReceta(recetaId);
            });
        });
    }

    function renderActions(tab, item) {
        switch(tab) {
            case 'reportes':
                return `
                    <button class="btn-mod aprobar" data-action="aprobar" data-id="${item.id}"><i class="fa-solid fa-check"></i> Aprobar (ignorar reporte)</button>
                    <button class="btn-mod rechazar" data-action="rechazar" data-id="${item.id}"><i class="fa-solid fa-trash"></i> Eliminar receta</button>
                    <button class="btn-mod ver" data-action="ver" data-id="${item.id}"><i class="fa-solid fa-eye"></i> Ver receta</button>
                `;
            case 'revision':
                return `
                    <button class="btn-mod aprobar" data-action="publicar" data-id="${item.id}"><i class="fa-solid fa-check"></i> Publicar</button>
                    <button class="btn-mod eliminar" data-action="eliminar" data-id="${item.id}"><i class="fa-solid fa-trash"></i> Eliminar</button>
                    <button class="btn-mod ver" data-action="ver" data-id="${item.id}"><i class="fa-solid fa-eye"></i> Ver</button>
                `;
            case 'apelaciones':
                return `
                    <button class="btn-mod aprobar" data-action="aceptar-apelacion" data-id="${item.id}"><i class="fa-solid fa-check"></i> Aceptar apelación (restaurar)</button>
                    <button class="btn-mod rechazar" data-action="rechazar-apelacion" data-id="${item.id}"><i class="fa-solid fa-times"></i> Rechazar apelación</button>
                    <button class="btn-mod ver" data-action="ver" data-id="${item.id}"><i class="fa-solid fa-eye"></i> Ver receta</button>
                `;
            case 'sanciones':
                return `
                    <button class="btn-mod restaurar" data-action="levantar-sancion" data-id="${item.id}"><i class="fa-solid fa-user-check"></i> Levantar suspensión</button>
                    <button class="btn-mod ver" data-action="ver-perfil" data-id="${item.usuario_id}"><i class="fa-solid fa-user"></i> Ver perfil</button>
                `;
            default: return '';
        }
    }

    function updateCounters(counts) {
        if (counts.reportes !== undefined) document.getElementById('reportes-count').textContent = counts.reportes;
        if (counts.revision !== undefined) document.getElementById('revision-count').textContent = counts.revision;
        if (counts.apelaciones !== undefined) document.getElementById('apelaciones-count').textContent = counts.apelaciones;
    }

    function verReceta(recetaId) {
        fetch(`${BASE_URL}api/receta_detalle.php?id=${recetaId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.ok) throw new Error(data.error);
                mostrarModalReceta(data.receta);
            })
            .catch(err => alert('Error al cargar detalles: ' + err.message));
    }

    function mostrarModalReceta(receta) {
        const modal = document.getElementById('modal-receta');
        const body = modal.querySelector('.modal-body');
        body.innerHTML = `
            <h4>${escapeHtml(receta.titulo)}</h4>
            <p><strong>Autor:</strong> ${escapeHtml(receta.autor)}</p>
            <p><strong>Descripción:</strong> ${escapeHtml(receta.descripcion)}</p>
            <p><strong>Ingredientes:</strong></p>
            <pre>${escapeHtml(receta.ingredientes)}</pre>
            <p><strong>Preparación:</strong></p>
            <pre>${escapeHtml(receta.preparacion)}</pre>
            ${receta.imagenes ? `<div class="galeria-mini">${receta.imagenes.map(img => `<img src="${BASE_URL}${img}" style="max-width:150px; margin:5px;">`).join('')}</div>` : ''}
        `;
        modal.classList.remove('hidden');
    }

    function confirmarAccion(mensaje, accionCallback) {
        const modal = document.getElementById('modal-confirmar');
        modal.querySelector('.modal-body').innerHTML = `<p>${escapeHtml(mensaje)}</p>`;
        modal.classList.remove('hidden');
        pendingAction = accionCallback;
    }

    function ejecutarAccion(endpoint, params, successMsg) {
        const formData = new URLSearchParams(params);
        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData,
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                alert(successMsg || 'Acción completada');
                loadTab(currentTab);
            } else {
                alert('Error: ' + (data.error || 'No se pudo completar'));
            }
        })
        .catch(err => alert('Error de conexión: ' + err.message));
    }

    // Delegación de eventos en el contenedor de contenido
    document.getElementById('moderacion-contenido').addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-mod');
        if (!btn) return;
        e.preventDefault();
        const action = btn.dataset.action;
        const id = btn.dataset.id;
        const tipoItem = btn.closest('.item-card')?.dataset.tipo;

        if (action === 'ver') {
            const recetaId = id;
            verReceta(recetaId);
            return;
        }
        if (action === 'ver-perfil') {
            window.location.href = `${BASE_URL}perfil.php?id=${id}`;
            return;
        }

        // Acciones que requieren confirmación
        const confirmMessages = {
            aprobar: '¿Aprobar este reporte? La receta se mantendrá activa y el reporte se cerrará.',
            rechazar: '¿Eliminar esta receta? Esta acción puede ser apelada por el autor.',
            publicar: '¿Publicar esta receta (quitar estado de revisión)?',
            eliminar: '¿Eliminar esta receta permanentemente? No se podrá recuperar.',
            'aceptar-apelacion': '¿Aceptar la apelación? La receta será restaurada a estado activo.',
            'rechazar-apelacion': '¿Rechazar la apelación? La receta permanecerá eliminada.',
            'levantar-sancion': '¿Levantar la suspensión al usuario? Podrá volver a publicar.'
        };

        const msg = confirmMessages[action] || '¿Confirmas esta acción?';
        confirmarAccion(msg, () => {
            let endpoint = `${BASE_URL}api/moderacion.php`;
            let params = { action, id, tab: currentTab };
            if (tipoItem === 'sanciones') params.usuario_id = id;
            ejecutarAccion(endpoint, params, 'Acción realizada con éxito');
        });
    });

    // Manejadores de modales
    document.querySelectorAll('.close-modal, #cerrar-modal-receta, .close-confirm').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('modal-receta').classList.add('hidden');
            document.getElementById('modal-confirmar').classList.add('hidden');
        });
    });
    document.getElementById('cancelar-accion').addEventListener('click', () => {
        document.getElementById('modal-confirmar').classList.add('hidden');
        pendingAction = null;
    });
    document.getElementById('confirmar-accion').addEventListener('click', () => {
        if (pendingAction) pendingAction();
        document.getElementById('modal-confirmar').classList.add('hidden');
        pendingAction = null;
    });

    // Inicialización tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTab = btn.dataset.tab;
            loadTab(currentTab);
        });
    });

    // Cargar pestaña inicial
    loadTab('reportes');

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
})();n