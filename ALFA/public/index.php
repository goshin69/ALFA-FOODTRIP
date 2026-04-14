<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Koalicus - Recetas Deliciosas</title>
    <link rel="stylesheet" href="assets/css/global.css?v=5.8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main id="app">

        <div class="container">
            <div id="recetas-container" class="secciones-container">
                <div class="loading-spinner">
                    <i class="fas fa-utensils fa-spin"></i> Cargando recetas...
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Koalicius - Comparte tus mejores recetas</p>
    </footer>

    <script src="assets/js/global.js?v=5.8"></script>
    <script src="assets/js/main.js?v=5.8"></script>
</body>
</html>