<?php
require_once __DIR__ . '/../app/helpers.php';
$title = "Inicio";
ob_start();
?>
<div class="grid grid-2">
  <div class="card">
    <h2>Bienvenido/a</h2>
    <p>Este sistema permite:</p>
    <ul>
      <li>Gestionar <strong>alumnos</strong> con foto.</li>
      <li>Cargar <strong>notas por materia</strong> o un <strong>promedio anual directo</strong>.</li>
      <li>Calcular promedios por año y ver el <strong>podio</strong> y los <strong>candidatos</strong> que siguen.</li>
    </ul>
    <p><a class="btn" href="alumnos.php">Comenzar con Alumnos</a></p>
  </div>
  <div class="card">
    <h3>Atajos</h3>
    <p><a class="btn" href="notas.php">Cargar Notas</a>
       <a class="btn secondary" href="estadisticas.php">Ver Estadísticas</a></p>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
