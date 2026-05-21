<?php
$baseUrl = '/ALFA/public/';
session_start();
require_once '../includes/database.php';
require_once '../includes/funciones.php';

// Verificar que el usuario sea admin o moderador
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'] ?? '', ['admin', 'moderador'])) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

ob_start();
include '../includes/header.php';
$header_html = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de moderación - Koalicius</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/moderacion.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="<?= $baseUrl ?>assets/js/moderacion.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?= $header_html ?>
    <main class="moderacion-main">
        <div class="moderacion-header">
            <h1><i class="fa-solid fa-gavel"></i> Panel de moderación</h1>
            <p>Gestiona reportes, recetas en revisión, apelaciones y usuarios sancionados.</p>
        </div>

        <div class="moderacion-tabs">
            <button class="tab-btn active" data-tab="reportes"><i class="fa-solid fa-flag"></i> Reportes <span id="reportes-count" class="badge"></span></button>
            <button class="tab-btn" data-tab="revision"><i class="fa-solid fa-clock"></i> En revisión <span id="revision-count" class="badge"></span></button>
            <button class="tab-btn" data-tab="apelaciones"><i class="fa-solid fa-scale-balanced"></i> Apelaciones <span id="apelaciones-count" class="badge"></span></button>
            <button class="tab-btn" data-tab="sanciones"><i class="fa-solid fa-ban"></i> Usuarios sancionados</button>
        </div>

        <div id="moderacion-contenido" class="moderacion-contenido">
            <div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
        </div>

        <!-- Modal para ver detalles de receta -->
        <div id="modal-receta" class="modal-overlay hidden">
            <div class="modal-content modal-receta">
                <div class="modal-header">
                    <h3>Detalles de la receta</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button class="btn-secondary" id="cerrar-modal-receta">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Modal para confirmar acciones -->
        <div id="modal-confirmar" class="modal-overlay hidden">
            <div class="modal-content modal-confirm">
                <div class="modal-header">
                    <h3>Confirmar acción</h3>
                    <button class="close-confirm">&times;</button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button class="btn-cancelar" id="cancelar-accion">Cancelar</button>
                    <button class="btn-confirmar" id="confirmar-accion">Aceptar</button>
                </div>
            </div>
        </div>
    </main>
    <div id="notif-overlay" class="notif-overlay hidden"></div>
    <script src="<?= $baseUrl ?>assets/js/global.js"></script>
</body>
</html>