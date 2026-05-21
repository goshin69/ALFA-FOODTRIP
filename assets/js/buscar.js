(function() {
    'use strict';

    const TEXTS = {
        es: {
            titulo_pagina: 'Buscar recetas - Koalicius',
            tiempo_label: '<i class="fa-regular fa-clock"></i> Tiempo máx.',
            tiempo_cualquiera: 'Cualquiera',
            tiempo_15: 'Hasta 15 min',
            tiempo_30: 'Hasta 30 min',
            tiempo_60: 'Hasta 60 min',
            tiempo_mas60: 'Más de 60 min',
            orden_label: '<i class="fa-solid fa-arrow-down-wide-short"></i> Ordenar por',
            orden_popular: 'Más popular',
            orden_reciente: 'Más reciente',
            orden_nombre: 'Nombre A-Z',
            etiquetas_label: '<i class="fa-solid fa-tags"></i> Etiquetas (debe contener todas)',
            excluir_label: '<i class="fa-solid fa-ban"></i> Excluir etiquetas',
            placeholder_etiquetas: 'Escribe para agregar etiquetas...',
            placeholder_excluir: 'Escribe para excluir etiquetas...',
            populares: 'Populares:',
            aplicar_filtros: 'Aplicar filtros',
            limpiar: 'Limpiar',
            cargando_mas: 'Cargando más recetas...',
            no_recetas: 'No encontramos recetas.',
            intenta_otra: 'Intenta con otras palabras o filtros.',
            error_conexion: 'Error al conectar con el servidor',
            error_resultados: 'Error al cargar resultados'
        },
        en: {
            titulo_pagina: 'Search recipes - Koalicius',
            tiempo_label: '<i class="fa-regular fa-clock"></i> Max time',
            tiempo_cualquiera: 'Any',
            tiempo_15: 'Up to 15 min',
            tiempo_30: 'Up to 30 min',
            tiempo_60: 'Up to 60 min',
            tiempo_mas60: 'More than 60 min',
            orden_label: '<i class="fa-solid fa-arrow-down-wide-short"></i> Sort by',
            orden_popular: 'Most popular',
            orden_reciente: 'Most recent',
            orden_nombre: 'Name A-Z',
            etiquetas_label: '<i class="fa-solid fa-tags"></i> Tags (must contain all)',
            excluir_label: '<i class="fa-solid fa-ban"></i> Exclude tags',
            placeholder_etiquetas: 'Type to add tags...',
            placeholder_excluir: 'Type to exclude tags...',
            populares: 'Popular:',
            aplicar_filtros: 'Apply filters',
            limpiar: 'Clear',
            cargando_mas: 'Loading more recipes...',
            no_recetas: 'No recipes found.',
            intenta_otra: 'Try other words or filters.',
            error_conexion: 'Connection error',
            error_resultados: 'Error loading results'
        }
    };

    let currentLang = localStorage.getItem('lang') || 'es';
    let todasEtiquetas = [];
    let etiquetasSeleccionadas = [];
    let etiquetasExcluidas = [];
    let paginaActual = 1;
    let cargandoMas = false;
    let totalResultados = 0;
    let recetasAcumuladas = [];

    function aplicarIdioma(lang) {
        currentLang = lang;
        localStorage.setItem('lang', lang);
        const texts = TEXTS[lang] || TEXTS['es'];
        document.title = texts.titulo_pagina;
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.dataset.i18n;
            if (texts[key]) {
                if (key.startsWith('orden_') || key.startsWith('tiempo_')) {
                    if (el.tagName === 'OPTION') el.textContent = texts[key];
                } else if (key.includes('label') || key.includes('placeholder')) {
                    el.innerHTML = texts[key];
                } else {
                    el.textContent = texts[key];
                }
            }
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.dataset.i18nPlaceholder;
            if (texts[key]) el.placeholder = texts[key];
        });
        actualizarContador();
        cargarPopulares();
    }

    function actualizarContador() {
        const countEl = document.getElementById('result-count');
        if (countEl) {
            countEl.textContent = totalResultados + ' receta' + (totalResultados !== 1 ? 's' : '') + ' encontradas';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            if (m === '"') return '&quot;';
            if (m === "'") return '&#39;';
            return m;
        });
    }

    function cargarPopulares() {
        fetch('api/buscar.php?action=populares&_=' + Date.now())
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    const container = document.getElementById('populares-container');
                    if (!container) return;
                    const label = container.querySelector('.populares-label');
                    container.innerHTML = '';
                    container.appendChild(label);
                    data.populares.forEach(et => {
                        const chip = document.createElement('span');
                        chip.className = 'chip-popular';
                        chip.dataset.id = et.id;
                        chip.dataset.nombre = et.nombre;
                        chip.textContent = et.nombre;
                        chip.addEventListener('click', () => {
                            if (!etiquetasSeleccionadas.some(e => e.id === et.id) && !etiquetasExcluidas.some(e => e.id === et.id)) {
                                agregarEtiqueta({ id: et.id, nombre: et.nombre }, 'incluir');
                            }
                        });
                        container.appendChild(chip);
                    });
                }
            });
    }

    function cargarEtiquetas(callback) {
        fetch('api/buscar.php?action=etiquetas&_=' + Date.now())
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    todasEtiquetas = data.etiquetas;
                    const urlParams = new URLSearchParams(window.location.search);
                    const incluirIds = urlParams.getAll('etiquetas');
                    const excluirIds = urlParams.getAll('excluir_etiquetas');
                    if (incluirIds.length) {
                        incluirIds.forEach(id => {
                            const et = todasEtiquetas.find(e => e.id == id);
                            if (et) agregarEtiqueta(et, 'incluir');
                        });
                    }
                    if (excluirIds.length) {
                        excluirIds.forEach(id => {
                            const et = todasEtiquetas.find(e => e.id == id);
                            if (et) agregarEtiqueta(et, 'excluir');
                        });
                    }
                    actualizarHidden();
                    if (typeof callback === 'function') callback();
                }
            })
            .catch(err => {
                console.error('Error al cargar etiquetas:', err);
                if (typeof callback === 'function') callback();
            });
    }

    function mostrarSugerencias(termino, tipo) {
        const sugerenciasDiv = tipo === 'incluir' ? document.getElementById('etiquetas-sugerencias') : document.getElementById('excluir-sugerencias');
        if (!termino.trim()) {
            sugerenciasDiv.style.display = 'none';
            return;
        }
        const filtradas = todasEtiquetas.filter(et => 
            et.nombre.toLowerCase().includes(termino.toLowerCase()) &&
            !etiquetasSeleccionadas.some(s => s.id === et.id) &&
            !etiquetasExcluidas.some(s => s.id === et.id)
        );
        if (filtradas.length === 0) {
            sugerenciasDiv.style.display = 'none';
            return;
        }
        sugerenciasDiv.innerHTML = filtradas.map(et => `<div data-id="${et.id}" data-nombre="${et.nombre}">${et.nombre}</div>`).join('');
        sugerenciasDiv.style.display = 'block';
    }

    function agregarEtiqueta(etiqueta, tipo) {
        if (tipo === 'incluir') {
            if (etiquetasSeleccionadas.some(e => e.id === etiqueta.id)) return;
            etiquetasExcluidas = etiquetasExcluidas.filter(e => e.id != etiqueta.id);
            etiquetasSeleccionadas.push(etiqueta);
        } else {
            if (etiquetasExcluidas.some(e => e.id === etiqueta.id)) return;
            etiquetasSeleccionadas = etiquetasSeleccionadas.filter(e => e.id != etiqueta.id);
            etiquetasExcluidas.push(etiqueta);
        }
        renderizarChips('incluir');
        renderizarChips('excluir');
        actualizarHidden();
    }

    function eliminarEtiqueta(id, tipo) {
        const chip = document.querySelector(`.chip-remove[data-id="${id}"][data-tipo="${tipo}"]`)?.parentElement;
        if (chip) {
            chip.classList.add('removing');
            chip.addEventListener('animationend', () => {
                if (tipo === 'incluir') {
                    etiquetasSeleccionadas = etiquetasSeleccionadas.filter(e => e.id != id);
                } else {
                    etiquetasExcluidas = etiquetasExcluidas.filter(e => e.id != id);
                }
                renderizarChips(tipo);
                actualizarHidden();
            }, { once: true });
        }
    }

    function renderizarChips(tipo) {
        const container = tipo === 'incluir' ? document.getElementById('etiquetas-seleccionadas') : document.getElementById('excluidas-seleccionadas');
        const lista = tipo === 'incluir' ? etiquetasSeleccionadas : etiquetasExcluidas;
        container.innerHTML = lista.map(et => `
            <div class="chip ${tipo === 'excluir' ? 'excluida' : ''}">
                ${escapeHtml(et.nombre)}
                <span class="chip-remove" data-id="${et.id}" data-tipo="${tipo}">&times;</span>
            </div>
        `).join('');
        document.querySelectorAll('.chip-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = parseInt(btn.dataset.id);
                const tipoBtn = btn.dataset.tipo;
                eliminarEtiqueta(id, tipoBtn);
            });
        });
    }

    function actualizarHidden() {
        document.getElementById('etiquetas-hidden').value = etiquetasSeleccionadas.map(e => e.id).join(',');
        document.getElementById('excluir-hidden').value = etiquetasExcluidas.map(e => e.id).join(',');
    }

    function obtenerParametros(pagina = 1) {
        const urlParams = new URLSearchParams(window.location.search);
        const q = urlParams.get('q') || '';
        const tiempo_max = document.getElementById('tiempo_max')?.value || '';
        const order = document.getElementById('order')?.value || '';
        const etiquetasIds = etiquetasSeleccionadas.map(e => e.id);
        const excluirIds = etiquetasExcluidas.map(e => e.id);
        const params = {};
        if (q) params.q = q;
        if (tiempo_max) params.tiempo_max = tiempo_max;
        if (order) params.order = order;
        if (etiquetasIds.length) params.etiquetas = etiquetasIds;
        if (excluirIds.length) params.excluir_etiquetas = excluirIds;
        params.pagina = pagina;
        return params;
    }

    function cargarResultados(params, append = false) {
        const container = document.getElementById('resultados-container');
        if (!append) {
            container.innerHTML = '<div class="cargando"><i class="fa-solid fa-spinner fa-spin"></i></div>';
            recetasAcumuladas = [];
        }
        
        const url = 'api/buscar.php?' + new URLSearchParams(params).toString() + '&_=' + Date.now();
        
        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error('Error de red');
                return res.json();
            })
            .then(data => {
                if (!data.ok) {
                    container.innerHTML = `<div class="cargando">${TEXTS[currentLang].error_resultados}</div>`;
                    return;
                }
                totalResultados = data.total;
                actualizarContador();
                
                if (data.total === 0 && !append) {
                    container.innerHTML = `
                        <div class="no-results">
                            <img src="/ALFA/public/assets/img/triste.png" alt="Triste" class="no-results-img">
                            <h2>${TEXTS[currentLang].no_recetas}</h2>
                            <p>${TEXTS[currentLang].intenta_otra}</p>
                        </div>`;
                    return;
                }
                
                if (append) {
                    recetasAcumuladas = recetasAcumuladas.concat(data.recetas);
                } else {
                    recetasAcumuladas = data.recetas;
                }
                
                renderizarRecetas(recetasAcumuladas);
                document.getElementById('cargando-mas').style.display = 'none';
                cargandoMas = false;
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = `<div class="cargando">${TEXTS[currentLang].error_conexion}</div>`;
                document.getElementById('cargando-mas').style.display = 'none';
                cargandoMas = false;
            });
    }

    function renderizarRecetas(recetas) {
        const container = document.getElementById('resultados-container');
        let html = '<div class="recetas-grid">';
        
        recetas.forEach(r => {
            const avatarUrl = r.autor_avatar 
                ? `/ALFA/public/uploads/perfiles/perfil_${r.autor_id}.webp`
                : '/ALFA/public/assets/img/koali.ico';
            html += `
                <article class="receta-card">
                    <a href="/ALFA/public/receta.php?id=${r.id}" class="card-link">
                        <div class="card-image">
                            <img src="${escapeHtml(r.imagen)}" alt="${escapeHtml(r.titulo)}">
                            <span class="tiempo-badge"><i class="fa-regular fa-clock"></i> ${r.tiempo_preparacion} min</span>
                        </div>
                        <div class="card-body">
                            <h3>${escapeHtml(r.titulo)}</h3>
                            <p class="descripcion">${escapeHtml(r.descripcion_corta)}</p>
                            <div class="card-footer">
                                <div class="autor">
                                    <img src="${avatarUrl}" class="avatar-mini" alt="avatar" onerror="this.onerror=null;this.src='/ALFA/public/assets/img/koali.ico';">
                                    <span>${escapeHtml(r.autor_nombre)}</span>
                                </div>
                                <div class="stats">
                                    <span><i class="fa-regular fa-eye"></i> ${r.vistas}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </article>`;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    function configurarScrollInfinito() {
        window.addEventListener('scroll', () => {
            if (cargandoMas) return;
            const cargandoMasDiv = document.getElementById('cargando-mas');
            if (!cargandoMasDiv) return;
            const rect = cargandoMasDiv.getBoundingClientRect();
            if (rect.top <= window.innerHeight + 100 && totalResultados > recetasAcumuladas.length) {
                cargarMas();
            }
        });
    }

    function cargarMas() {
        if (cargandoMas) return;
        cargandoMas = true;
        document.getElementById('cargando-mas').style.display = 'flex';
        paginaActual++;
        const params = obtenerParametros(paginaActual);
        cargarResultados(params, true);
    }

    window.addEventListener('load', function() {
        aplicarIdioma(currentLang);
        window.cambiarIdioma = (lang) => aplicarIdioma(lang);
        
        function initCustomSelect(wrapperId) {
            const wrapper = document.getElementById(wrapperId);
            if (!wrapper) return;
            const selectedDiv = wrapper.querySelector('.select-selected');
            const itemsContainer = wrapper.querySelector('.select-items');
            const hiddenInput = wrapper.querySelector('input[type="hidden"]');
            const allItems = itemsContainer.querySelectorAll('div');

            function closeSelect() {
                wrapper.classList.remove('open');
            }

            selectedDiv.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.custom-select.open').forEach(cs => {
                    if (cs !== wrapper) cs.classList.remove('open');
                });
                wrapper.classList.toggle('open');
            });

            allItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    allItems.forEach(i => i.classList.remove('selected'));
                    item.classList.add('selected');
                    selectedDiv.textContent = item.textContent;
                    selectedDiv.setAttribute('data-value', item.getAttribute('data-value'));
                    hiddenInput.value = item.getAttribute('data-value');
                    closeSelect();
                });
            });

            document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) {
                    closeSelect();
                }
            });

            document.addEventListener('touchstart', (e) => {
                if (!wrapper.contains(e.target)) {
                    closeSelect();
                }
            }, { passive: true });

            const urlParams = new URLSearchParams(window.location.search);
            let paramName = '';
            if (wrapperId === 'custom-tiempo') paramName = 'tiempo_max';
            else if (wrapperId === 'custom-orden') paramName = 'order';

            if (paramName && urlParams.has(paramName)) {
                const val = urlParams.get(paramName);
                const targetItem = itemsContainer.querySelector(`div[data-value="${val}"]`);
                if (targetItem) {
                    targetItem.click();
                }
            } else {
                const defVal = hiddenInput.value;
                const defaultItem = itemsContainer.querySelector(`div[data-value="${defVal}"]`);
                if (defaultItem) {
                    defaultItem.classList.add('selected');
                    selectedDiv.textContent = defaultItem.textContent;
                    selectedDiv.setAttribute('data-value', defVal);
                }
            }
        }

        initCustomSelect('custom-tiempo');
        initCustomSelect('custom-orden');
        
        const themeSwitch = document.getElementById('theme-switch');
        if (themeSwitch) {
            themeSwitch.addEventListener('click', () => {
                document.body.classList.toggle('dark-mode');
                localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
            });
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
            }
        }

        const languageSwitch = document.getElementById('language-switch');
        if (languageSwitch) {
            languageSwitch.addEventListener('click', () => {
                const newLang = currentLang === 'es' ? 'en' : 'es';
                aplicarIdioma(newLang);
            });
        }

        const searchForm = document.getElementById('search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                paginaActual = 1;
                const params = obtenerParametros(1);
                const url = new URL(window.location.href);
                url.searchParams.delete('q');
                url.searchParams.delete('tiempo_max');
                url.searchParams.delete('order');
                url.searchParams.delete('etiquetas');
                url.searchParams.delete('excluir_etiquetas');
                Object.keys(params).forEach(key => {
                    if (key === 'etiquetas' || key === 'excluir_etiquetas') {
                        params[key].forEach(val => url.searchParams.append(key, val));
                    } else if (key !== 'pagina') {
                        url.searchParams.set(key, params[key]);
                    }
                });
                window.history.pushState({}, '', url);
                cargarResultados(params, false);
            });
        }

        const resetBtn = document.getElementById('reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                document.getElementById('tiempo_max').value = '';
                document.getElementById('order').value = 'popular';
                etiquetasSeleccionadas = [];
                etiquetasExcluidas = [];
                renderizarChips('incluir');
                renderizarChips('excluir');
                actualizarHidden();
                document.getElementById('etiqueta-input').value = '';
                document.getElementById('excluir-input').value = '';
                paginaActual = 1;
                const params = obtenerParametros(1);
                cargarResultados(params, false);
                const url = new URL(window.location.href);
                url.searchParams.delete('q');
                url.searchParams.delete('tiempo_max');
                url.searchParams.delete('order');
                url.searchParams.delete('etiquetas');
                url.searchParams.delete('excluir_etiquetas');
                window.history.pushState({}, '', url);
            });
        }

        const etiquetaInput = document.getElementById('etiqueta-input');
        if (etiquetaInput) {
            etiquetaInput.addEventListener('input', function(e) {
                mostrarSugerencias(this.value, 'incluir');
            });
            etiquetaInput.addEventListener('blur', function() {
                setTimeout(() => {
                    document.getElementById('etiquetas-sugerencias').style.display = 'none';
                }, 200);
            });
        }

        const excluirInput = document.getElementById('excluir-input');
        if (excluirInput) {
            excluirInput.addEventListener('input', function(e) {
                mostrarSugerencias(this.value, 'excluir');
            });
            excluirInput.addEventListener('blur', function() {
                setTimeout(() => {
                    document.getElementById('excluir-sugerencias').style.display = 'none';
                }, 200);
            });
        }

        const etSugerencias = document.getElementById('etiquetas-sugerencias');
        if (etSugerencias) {
            etSugerencias.addEventListener('click', function(e) {
                const div = e.target.closest('div');
                if (div && div.dataset.id) {
                    const id = parseInt(div.dataset.id);
                    const nombre = div.dataset.nombre;
                    agregarEtiqueta({ id, nombre }, 'incluir');
                    etiquetaInput.value = '';
                    etSugerencias.style.display = 'none';
                }
            });
        }

        const exSugerencias = document.getElementById('excluir-sugerencias');
        if (exSugerencias) {
            exSugerencias.addEventListener('click', function(e) {
                const div = e.target.closest('div');
                if (div && div.dataset.id) {
                    const id = parseInt(div.dataset.id);
                    const nombre = div.dataset.nombre;
                    agregarEtiqueta({ id, nombre }, 'excluir');
                    excluirInput.value = '';
                    exSugerencias.style.display = 'none';
                }
            });
        }

        cargarEtiquetas(function() {
            cargarPopulares();
            const paramsInicial = obtenerParametros(1);
            cargarResultados(paramsInicial, false);
        });

        configurarScrollInfinito();
    });
})();