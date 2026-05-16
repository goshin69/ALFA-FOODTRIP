<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "koalicius";

$conn = mysqli_connect($host, $user, $pass, $db);

if(!$conn){
    die("Error de conexión");
}


/* =========================================
   TOP SEMANAL
========================================= */

$sql_semanal = "

SELECT *

FROM recetas

WHERE YEARWEEK(fecha_publicacion, 1)
= YEARWEEK(CURDATE(), 1)

ORDER BY likes DESC

LIMIT 5

";

$resultado_semanal = mysqli_query($conn, $sql_semanal);



/* =========================================
   TOP MENSUAL
========================================= */

$sql_mensual = "

SELECT *

FROM recetas

WHERE MONTH(fecha_publicacion)
= MONTH(CURDATE())

AND YEAR(fecha_publicacion)
= YEAR(CURDATE())

ORDER BY likes DESC

LIMIT 10

";

$resultado_mensual = mysqli_query($conn, $sql_mensual);

?>

<?php include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Tendencias | Koalicius</title>

<link rel="stylesheet" href="assets/css/global.css">
<link rel="stylesheet" href="assets/css/tendencias.css">

</head>

<body>

<div class="tendencias-container">


    <!-- =========================================
         PATROCINADORES
    ========================================= -->

    <section class="bloque">

        <div class="titulo-box">
            <h2 class="titulo-seccion">🍔 Patrocinadores</h2>
            <span class="subtitulo">
                Restaurantes destacados
            </span>
        </div>

        <div class="cards-grid patrocinadores-grid">

            <div class="card-t patrocinador">

                <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?q=80&w=1200&auto=format&fit=crop">

                <div class="card-info">

                    <h3>Burger House</h3>

                    <p>
                        Patrocinador oficial de Koalicius 🔥
                    </p>

                    <button>
                        Ver restaurante
                    </button>

                </div>

            </div>


            <!-- AQUI PUEDES AGREGAR MAS PATROCINADORES -->


        </div>

    </section>





    <!-- =========================================
         TOP SEMANAL
    ========================================= -->

    <section class="bloque">

        <div class="titulo-box">

            <h2 class="titulo-seccion">
                🔥 Top 5 semanal
            </h2>

            <span class="subtitulo">
                Las recetas más populares de la semana
            </span>

        </div>

        <div class="cards-grid">

            <?php while($receta = mysqli_fetch_assoc($resultado_semanal)) { ?>

                <div class="card-t">

                    <span class="badge">
                        TOP
                    </span>

                    <img src="<?= $receta['imagen'] ?>">

                    <div class="card-info">

                        <h3>
                            <?= $receta['titulo'] ?>
                        </h3>

                        <p>
                            ❤️ <?= number_format($receta['likes']) ?> Likes
                        </p>

                        <button>
                            Ver receta
                        </button>

                    </div>

                </div>

            <?php } ?>

        </div>

    </section>





    <!-- =========================================
         TOP MENSUAL
    ========================================= -->

    <section class="bloque">

        <div class="titulo-box">

            <h2 class="titulo-seccion">
                🏆 Top 10 mensual
            </h2>

            <span class="subtitulo">
                Las recetas legendarias del mes 😎
            </span>

        </div>

        <div class="cards-grid">

            <?php while($receta = mysqli_fetch_assoc($resultado_mensual)) { ?>

                <div class="card-t">

                    <span class="badge mensual">
                        MES
                    </span>

                    <img src="<?= $receta['imagen'] ?>">

                    <div class="card-info">

                        <h3>
                            <?= $receta['titulo'] ?>
                        </h3>

                        <p>
                            ❤️ <?= number_format($receta['likes']) ?> Likes
                        </p>

                        <button>
                            Ver receta
                        </button>

                    </div>

                </div>

            <?php } ?>

        </div>

    </section>


</div>

</body>
</html>