<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Koalicus - Recetas Deliciosas</title>
  <link rel="stylesheet" href="assets/css/global.css?v=5.3">
  <link rel="stylesheet" href="assets/css/crear_receta.css?v=5.3">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <base href="/ALFA/public/">
   <?php include '../includes/header.php'; ?>

  <main id="app">
    <div class="hero-section"> 
      <div style = "text-align: center;"> <h2>  Recetas Recientes</h2></div>
      <p class="subtitle">Descubre Las Mejores Recetas Compartidas Por Nuestra Comunidad</p>
    </div>
    
    <div class="container">
      <div id="recetas-container" class="recetas-grid">
        <div class="loading-spinner">
          <i class="fas fa-utensils"></i> Cargando recetas...
        </div>
      </div>
    </div>
  </main>

  <footer class="footer">
    <p>&copy; 2026 A Koalicus - Comparte tus mejores recetas</p>
  </footer>

  <script src="assets/js/global.js?v=5.3"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>