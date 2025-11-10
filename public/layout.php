<?php
// Detecta página activa para marcar en el navbar
$active = basename($_SERVER['PHP_SELF']); // index.php | alumnos.php | notas.php | estadisticas.php
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Colegio San José · Gestión de Promedios</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <!-- NAVBAR -->
  <header class="topbar">
    <div class="brand">
      <img src="img/logo.png" class="brand-logo" alt="Logo Colegio San José">
      <div class="brand-text">
        <strong>Colegio San José</strong><br>
        <span>Gestión de Promedios</span>
      </div>
    </div>

    <!-- Botón hamburguesa (visible en móvil) -->
    <button class="menu-toggle" aria-controls="mainnav" aria-expanded="false" aria-label="Abrir menú">
      <span class="bars"></span>
    </button>

    <!-- Menú principal -->
    <nav id="mainnav" class="nav">
      <a href="index.php"        class="<?= $active==='index.php'?'active':'' ?>">Inicio</a>
      <a href="alumnos.php"      class="<?= $active==='alumnos.php'?'active':'' ?>">Alumnos</a>
      <a href="notas.php"        class="<?= $active==='notas.php'?'active':'' ?>">Notas</a>
      <a href="estadisticas.php" class="<?= $active==='estadisticas.php'?'active':'' ?>">Estadísticas</a>
    </nav>
  </header>

  <!-- CONTENIDO -->
  <main class="container">
    <?= $content ?? '' ?>
  </main>

  <footer class="footer">
    © <?= date('Y') ?> Colegio San José • Proyecto de Promedios
  </footer>

  <script src="js/main.js" defer></script>
</body>
</html>
