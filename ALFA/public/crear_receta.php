<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Crear Receta - Koalicius</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/global.css?v=5.5">
    <link rel="stylesheet" href="assets/css/crear_receta.css?v=5.5">
</head>
<body>
    <div id="loader-wrapper">
        <div class="loader"></div>
    </div>

    <main class="form-container">
        <h2 class="form-title">Nueva Receta</h2>
        <div id="mensaje" class="mensaje"></div>

        <form id="recetaForm" enctype="multipart/form-data">
            <input type="hidden" id="receta_id_temp" name="receta_id_temp" value="">

            <div class="two-column-layout">
                <div class="left-column">
                    <div class="input-group">
                        <label for="titulo">Título de la receta:</label>
                        <input type="text" id="titulo" name="titulo" placeholder="Ej. Tacos al Pastor">
                    </div>

                    <div class="input-group">
                        <label for="descripcion">Descripción (presentación):</label>
                        <textarea id="descripcion" name="descripcion" rows="2" placeholder="Un pequeño mensaje para tus seguidores..."></textarea>
                    </div>

                    <div class="input-group">
                        <label for="preparacion">Preparación (pasos detallados):</label>
                        <textarea id="preparacion" name="preparacion" rows="6" placeholder="Explica paso a paso cómo se prepara..."></textarea>
                    </div>

                    <div class="input-group">
                        <label for="ingredientes">Ingredientes:</label>
                        <textarea id="ingredientes" name="ingredientes" rows="4" placeholder="Lista de ingredientes (1 taza de harina, 2 huevos, etc.)"></textarea>
                    </div>

                    <div class="row-fields">
                        <div class="half-width">
                            <label for="dificultad"><i class="fa-regular fa-chart-line"></i> Dificultad</label>
                            <div class="custom-select" id="custom-dificultad">
                                <div class="select-selected" data-value="media">Media</div>
                                <div class="select-items">
                                    <div data-value="facil">Fácil</div>
                                    <div data-value="media">Media</div>
                                    <div data-value="dificil">Difícil</div>
                                </div>
                                <input type="hidden" name="dificultad" id="dificultad" value="media">
                            </div>
                        </div>

                        <div class="half-width">
                            <label for="tiempo_preparacion"><i class="fa-regular fa-clock"></i> Tiempo de preparación</label>
                            <div class="custom-select" id="custom-tiempo">
                                <div class="select-selected" data-value="25">20 a 30 minutos</div>
                                <div class="select-items">
                                    <div data-value="5">Menos de 10 minutos</div>
                                    <div data-value="25">20 a 30 minutos</div>
                                    <div data-value="45">Menos de 1 hora</div>
                                    <div data-value="90">Más de 1 hora</div>
                                </div>
                                <input type="hidden" name="tiempo_preparacion" id="tiempo_preparacion" value="25">
                            </div>
                        </div>
                    </div>

                    <div class="input-group" id="etiquetas-group">
                        <label>Etiquetas</label>
                        <div class="etiquetas-container">
                            <input type="text" id="etiqueta-input" placeholder="Escribe para buscar o crear etiquetas...">
                            <div id="etiquetas-sugerencias" class="sugerencias"></div>
                        </div>
                        <div id="etiquetas-seleccionadas" class="etiquetas-seleccionadas"></div>
                        <input type="hidden" name="etiquetas" id="etiquetas-hidden" value="">
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn-guardar" id="btnGuardarBorrador">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar Borrador
                        </button>
                        <button type="button" class="btn-submit" id="btnPublicar">
                            <span class="btn-text"><i class="fa-solid fa-upload"></i> Publicar Receta</span>
                            <span class="btn-loader" style="display: none;">Publicando...</span>
                        </button>
                    </div>
                </div>

                <div class="right-column">
                    <div class="upload-box" id="uploadBox">
                        <div class="upload-icon">
                            <i class="fa-solid fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            <h3>Arrastra y suelta tus archivos aquí</h3>
                            <p>Imágenes (JPG, PNG, GIF, WebP) y videos (MP4, MKV, AVI, MOV, WebM)</p>
                            <small>Máximo: 1 video (40min, 1080p) y 5 imágenes</small>
                        </div>
                        <div class="btn-subir">
                            <i class="fa-solid fa-upload"></i> Seleccionar archivos
                        </div>
                        <input type="file" id="archivos" name="archivos[]" accept="image/*,video/*" multiple>
                        <div id="preview-container" class="preview-container"></div>
                        <div id="file-info" class="file-info"></div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar publicación</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas publicar esta receta?</p>
                <p class="modal-warning">Una vez publicada, estará visible para todos los usuarios.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancelar" id="cancelarPublicacion">Cancelar</button>
                <button class="btn-confirmar" id="confirmarPublicacion">Publicar</button>
            </div>
        </div>
    </div>

    <div id="progressOverlay" class="progress-overlay" style="display: none;">
        <div class="progress-container">
            <div class="progress-spinner"></div>
            <div class="progress-text">Procesando...</div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>
    </div>

    <script src="assets/js/global.js?v=5.5"></script>
    <script src="assets/js/etiquetas.js?v=5.5"></script>
    <script src="assets/js/crear_receta.js?v=5.5"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function initCustomSelect(selectId) {
                const customSelect = document.getElementById(selectId);
                if (!customSelect) return;
                const selectedDiv = customSelect.querySelector('.select-selected');
                const itemsContainer = customSelect.querySelector('.select-items');
                const hiddenInput = customSelect.querySelector('input[type="hidden"]');

                selectedDiv.addEventListener('click', function(e) {
                    e.stopPropagation();
                    itemsContainer.classList.toggle('show');
                });

                const items = itemsContainer.querySelectorAll('div');
                items.forEach(item => {
                    item.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        const text = this.textContent;
                        selectedDiv.textContent = text;
                        selectedDiv.setAttribute('data-value', value);
                        hiddenInput.value = value;
                        itemsContainer.classList.remove('show');
                    });
                });

                document.addEventListener('click', function(e) {
                    if (!customSelect.contains(e.target)) {
                        itemsContainer.classList.remove('show');
                    }
                });
            }

            initCustomSelect('custom-dificultad');
            initCustomSelect('custom-tiempo');
        });
    </script>
</body>
</html>