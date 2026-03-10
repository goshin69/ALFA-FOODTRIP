document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('etiqueta-input');
    const sugerenciasDiv = document.getElementById('etiquetas-sugerencias');
    const contenedorSeleccionadas = document.getElementById('etiquetas-seleccionadas');
    const hiddenInput = document.getElementById('etiquetas-hidden');
    
    let etiquetasSeleccionadas = [];
    let timeoutId = null;

    function actualizarHidden() {
        const ids = etiquetasSeleccionadas.map(e => e.id).join(',');
        hiddenInput.value = ids;
        contenedorSeleccionadas.innerHTML = etiquetasSeleccionadas.map(et => `
            <span class="etiqueta-chip" data-id="${et.id}">
                ${et.nombre}
                <i class="fa-solid fa-times" onclick="eliminarEtiqueta(${et.id})"></i>
            </span>
        `).join('');
    }

    window.eliminarEtiqueta = function(id) {
        etiquetasSeleccionadas = etiquetasSeleccionadas.filter(e => e.id != id);
        actualizarHidden();
        input.focus();
    };

    function agregarEtiqueta(id, nombre) {
        if (!etiquetasSeleccionadas.some(e => e.id == id)) {
            etiquetasSeleccionadas.push({ id, nombre });
            actualizarHidden();
        }
        input.value = '';
        sugerenciasDiv.style.display = 'none';
    }

    function buscarEtiquetas(termino) {
        if (termino.length < 1) {
            sugerenciasDiv.style.display = 'none';
            return;
        }

        fetch(`api/buscar_etiquetas.php?q=${encodeURIComponent(termino)}`)
            .then(response => response.json())
            .then(data => {
                mostrarSugerencias(data, termino);
            })
            .catch(error => console.error('Error al buscar etiquetas:', error));
    }

    function mostrarSugerencias(etiquetas, termino) {
        sugerenciasDiv.innerHTML = '';
        if (etiquetas.length === 0) {
            const div = document.createElement('div');
            div.className = 'crear-nueva';
            div.textContent = `+ Crear nueva etiqueta "${termino}"`;
            div.addEventListener('click', () => crearNuevaEtiqueta(termino));
            sugerenciasDiv.appendChild(div);
        } else {
            etiquetas.forEach(et => {
                if (!etiquetasSeleccionadas.some(e => e.id == et.id)) {
                    const div = document.createElement('div');
                    div.textContent = et.nombre;
                    div.addEventListener('click', () => agregarEtiqueta(et.id, et.nombre));
                    sugerenciasDiv.appendChild(div);
                }
            });
        }
        sugerenciasDiv.style.display = 'block';
    }

    function crearNuevaEtiqueta(nombre) {
    const formData = new FormData();
    formData.append('nombre', nombre);

    fetch('api/crear_etiqueta.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        console.log('Respuesta del servidor:', text);
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Respuesta no JSON: ' + text);
        }
    })
    .then(data => {
        if (data.ok) {
            agregarEtiqueta(data.id, data.nombre);
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error detallado:', error);
        alert('Error de conexión al crear etiqueta. Revisa la consola (F12) para más detalles.');
    });
}

    input.addEventListener('input', function() {
        const termino = this.value.trim();
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => buscarEtiquetas(termino), 300);
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && sugerenciasDiv.style.display === 'block') {
            e.preventDefault();
            const primerSugerencia = sugerenciasDiv.querySelector('div');
            if (primerSugerencia) {
                primerSugerencia.click();
            }
        }
    });

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !sugerenciasDiv.contains(e.target)) {
            sugerenciasDiv.style.display = 'none';
        }
    });
});