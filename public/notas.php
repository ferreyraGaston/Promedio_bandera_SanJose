<?php
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/db.php';
session_start();

/* ====== Utilidades ====== */

$alumnos = $conn->query("SELECT id, nombre, apellido FROM alumnos ORDER BY apellido, nombre")
               ->fetch_all(MYSQLI_ASSOC);

/** Notas ya cargadas por materia para (alumno, año) */
function get_notas(mysqli $conn, int $alumno_id, int $anio): array {
  $q = $conn->prepare("SELECT * FROM notas WHERE alumno_id=? AND anio=? AND nota IS NOT NULL ORDER BY id");
  $q->bind_param('ii', $alumno_id, $anio);
  $q->execute();
  return $q->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Insertar/actualizar una nota por materia (upsert) */
function upsert_nota(mysqli $conn, int $alumno_id, int $anio, string $materia, float $nota): void {
  $q = $conn->prepare("SELECT id FROM notas WHERE alumno_id=? AND anio=? AND materia=? LIMIT 1");
  $q->bind_param('iis', $alumno_id, $anio, $materia);
  $q->execute();
  $id = $q->get_result()->fetch_column();

  if ($id) {
    $st = $conn->prepare("UPDATE notas SET nota=? WHERE id=?");
    $st->bind_param('di', $nota, $id);
    $st->execute();
  } else {
    $st = $conn->prepare("INSERT INTO notas(alumno_id, anio, materia, nota) VALUES (?,?,?,?)");
    $st->bind_param('iisd', $alumno_id, $anio, $materia, $nota);
    $st->execute();
  }
}

/** Promedio solo desde materias (sin considerar promedio_manual) */
function promedio_notas_only(mysqli $conn, int $alumno_id, int $anio): float {
  $q = $conn->prepare("SELECT AVG(nota) prom FROM notas WHERE alumno_id=? AND anio=? AND nota IS NOT NULL");
  $q->bind_param('ii', $alumno_id, $anio);
  $q->execute();
  return (float)($q->get_result()->fetch_assoc()['prom'] ?? 0);
}

/** Obtener registro de promedio_manual (si existe) */
function get_manual_prom(mysqli $conn, int $alumno_id, int $anio): ?array {
  $q = $conn->prepare("SELECT id, promedio_manual FROM notas WHERE alumno_id=? AND anio=? AND promedio_manual IS NOT NULL LIMIT 1");
  $q->bind_param('ii', $alumno_id, $anio);
  $q->execute();
  $row = $q->get_result()->fetch_assoc();
  return $row ?: null;
}

/** Guardar/actualizar promedio_manual solo si cambió (tolerancia 0.005) */
function upsert_manual_if_changed(mysqli $conn, int $alumno_id, int $anio, float $nuevoPromedio): void {
  if ($nuevoPromedio <= 0) return;

  $ex = get_manual_prom($conn, $alumno_id, $anio);
  if (!$ex) {
    $ins = $conn->prepare("INSERT INTO notas(alumno_id, anio, promedio_manual) VALUES (?,?,?)");
    $ins->bind_param('iid', $alumno_id, $anio, $nuevoPromedio);
    $ins->execute();
  } else {
    if (abs(((float)$ex['promedio_manual']) - $nuevoPromedio) > 0.005) {
      $st = $conn->prepare("UPDATE notas SET promedio_manual=? WHERE id=?");
      $st->bind_param('di', $nuevoPromedio, $ex['id']);
      $st->execute();
    }
  }
}

/** Promedio anual “efectivo”: usa promedio_manual si existe; si no, el promedio de materias */
function promedio_anual(mysqli $conn, int $alumno_id, int $anio): float {
  $q2 = $conn->prepare("SELECT promedio_manual FROM notas WHERE alumno_id=? AND anio=? AND promedio_manual IS NOT NULL LIMIT 1");
  $q2->bind_param('ii', $alumno_id, $anio);
  $q2->execute();
  $manual = $q2->get_result()->fetch_column();
  if ($manual !== null) return (float)$manual;

  return promedio_notas_only($conn, $alumno_id, $anio);
}

/* ====== POST: guardar fila de materia ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_row') {
  $alumno_id = (int)($_POST['alumno_id'] ?? 0);
  $anio      = (int)($_POST['anio'] ?? 0);
  $materia   = trim($_POST['materia'] ?? '');
  $nota      = (float)($_POST['nota'] ?? 0);

  if (!$alumno_id || !$anio || $materia === '' || $nota <= 0) {
    swal('warning', 'Faltan datos', 'Completá alumno, año, materia y nota.');
    redirect('notas.php');
  }

  upsert_nota($conn, $alumno_id, $anio, $materia, $nota);

  // Recalcular promedio desde materias y actualizar promedio_manual sólo si cambió
  $calc = promedio_notas_only($conn, $alumno_id, $anio);
  upsert_manual_if_changed($conn, $alumno_id, $anio, $calc);

  swal('success', 'Guardado', 'La nota fue guardada y el promedio actualizado.');
  redirect('notas.php?alumno_id=' . $alumno_id . '&anio=' . $anio);
}

/* ====== POST: guardar promedio anual directo ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_prom') {
  $alumno_id = (int)($_POST['alumno_id'] ?? 0);
  $anio      = (int)($_POST['anio'] ?? 0);
  $prom      = (float)($_POST['promedio_directo'] ?? 0);

  if (!$alumno_id || !$anio || $prom <= 0) {
    swal('warning', 'Datos inválidos', 'Seleccioná alumno/año y un promedio válido.');
    redirect('notas.php');
  }

  // eliminamos cualquier registro manual anterior y guardamos el nuevo
  $del = $conn->prepare("DELETE FROM notas WHERE alumno_id=? AND anio=? AND promedio_manual IS NOT NULL");
  $del->bind_param('ii', $alumno_id, $anio);
  $del->execute();

  $ins = $conn->prepare("INSERT INTO notas(alumno_id, anio, promedio_manual) VALUES (?,?,?)");
  $ins->bind_param('iid', $alumno_id, $anio, $prom);
  $ins->execute();

  swal('success', 'Promedio guardado', 'Se guardó el promedio anual.');
  redirect('notas.php?alumno_id=' . $alumno_id . '&anio=' . $anio);
}

/* ====== GET y datos para la vista ====== */
$alumnoSel = (int)($_GET['alumno_id'] ?? 0);
$anioSel   = (int)($_GET['anio'] ?? date('Y'));
$existentes = $alumnoSel ? get_notas($conn, $alumnoSel, $anioSel) : [];

$title = "Notas";
ob_start();
?>
<div class="card">
  <h2>Notas por materia (guardar por fila)</h2>
  <form method="get" class="grid grid-3">
    <div>
      <label>Alumno</label>
      <select name="alumno_id" required>
        <option value="">Seleccionar...</option>
        <?php foreach ($alumnos as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $alumnoSel == $a['id'] ? 'selected' : '' ?>>
            <?= h($a['apellido'] . ', ' . $a['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Año</label>
      <select name="anio" required>
        <?php
          $to   = (int)date('Y');
          $from = $to - 12;
          for ($yy = $to; $yy >= $from; $yy--) {
            $p = $alumnoSel ? promedio_anual($conn, $alumnoSel, $yy) : 0;
            $style = $p > 0 ? "style='background:#e6ffe6;font-weight:600'" : '';
            $sel   = $anioSel == $yy ? 'selected' : '';
            echo "<option value='{$yy}' {$sel} {$style}>" .
                 $yy . ($p > 0 ? " • (" . number_format($p, 2) . ")" : "") .
                 "</option>";
          }
        ?>
      </select>
    </div>
    <div style="display:flex;align-items:flex-end">
      <button class="btn" type="submit">Ir</button>
    </div>
  </form>
</div>

<?php if ($alumnoSel): ?>
  <div class="card">
    <h3>Materias</h3>

    <div id="rows">
      <?php
        $saved = [];
        foreach ($existentes as $e) {
          $saved[strtoupper($e['materia'])] = (float)$e['nota'];
        }
        $preset = ['Matemática','Lengua','Inglés','Ciencias','Historia','Geografía'];
        foreach ($preset as $mat):
          $key = strtoupper($mat);
          $ya  = array_key_exists($key, $saved);
      ?>
      <form method="post" class="note-row">
        <input type="hidden" name="action" value="save_row">
        <input type="hidden" name="alumno_id" value="<?= $alumnoSel ?>">
        <input type="hidden" name="anio" value="<?= $anioSel ?>">

        <input name="materia" value="<?= h($mat) ?>">
        <input type="number" step="0.01" min="1" max="10" name="nota"
               value="<?= $ya ? $saved[$key] : '' ?>" <?= $ya ? 'disabled' : '' ?>>

        <?php if ($ya): ?>
          <span class="check">✔ Guardado</span>
          <button class="btn sm ghost" type="button"
                  onclick="this.closest('form').querySelector('[name=nota]').disabled=false; this.closest('form').querySelector('[name=nota]').focus(); this.remove();">
            Editar
          </button>
        <?php else: ?>
          <button class="btn sm" type="submit">Guardar</button>
        <?php endif; ?>
      </form>
      <?php endforeach; ?>
    </div>

    <h3 style="margin-top:10px">Agregar materia libre</h3>
    <form method="post" class="note-row">
      <input type="hidden" name="action" value="save_row">
      <input type="hidden" name="alumno_id" value="<?= $alumnoSel ?>">
      <input type="hidden" name="anio" value="<?= $anioSel ?>">
      <input name="materia" placeholder="Materia">
      <input type="number" step="0.01" min="1" max="10" name="nota" placeholder="Nota">
      <button class="btn sm" type="submit">Guardar</button>
    </form>
  </div>

  <div class="card">
    <h3>Promedio anual directo</h3>
    <form method="post" class="grid grid-3">
      <input type="hidden" name="action" value="save_prom">
      <input type="hidden" name="alumno_id" value="<?= $alumnoSel ?>">
      <input type="hidden" name="anio" value="<?= $anioSel ?>">
      <div>
        <label>Promedio</label>
        <input type="number" step="0.01" min="1" max="10" name="promedio_directo">
      </div>
      <div style="display:flex;align-items:flex-end">
        <button class="btn" type="submit">Guardar promedio</button>
      </div>
    </form>
    <p class="badge">Promedio calculado:
      <strong><?= number_format(promedio_anual($conn, $alumnoSel, $anioSel), 2) ?></strong>
    </p>
  </div>
<?php else: ?>
  <div class="card"><p>Seleccioná un alumno y año.</p></div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
