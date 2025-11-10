<?php
require_once __DIR__ . '/../app/helpers.php'; require_once __DIR__ . '/../app/db.php'; session_start();
function pa($c,$aid,$y){ $q=$c->prepare("SELECT AVG(nota) prom FROM notas WHERE alumno_id=? AND anio=? AND nota IS NOT NULL"); $q->bind_param('ii',$aid,$y); $q->execute(); $pn=(float)($q->get_result()->fetch_assoc()['prom']??0); $q2=$c->prepare("SELECT promedio_manual FROM notas WHERE alumno_id=? AND anio=? AND promedio_manual IS NOT NULL LIMIT 1"); $q2->bind_param('ii',$aid,$y); $q2->execute(); $man=$q2->get_result()->fetch_column(); return $man!==null?(float)$man:$pn; }
$hasta=(int)($_GET['hasta']??date('Y')); $window=6; $desde=$hasta-($window-1);
$alumnos=$conn->query("SELECT id,nombre,apellido,curso,division,foto FROM alumnos")->fetch_all(MYSQLI_ASSOC);
$ranking=[]; foreach($alumnos as $a){ $sum=0;$w=0;$det=[]; for($y=$desde;$y<=$hasta;$y++){ $p=pa($conn,$a['id'],$y); if($p>0){ $peso=($y-$desde)+1; $sum+=$p*$peso; $w+=$peso; $det[$y]=['prom'=>$p,'peso'=>$peso]; } } if($w>0){ $final=$sum/$w; $ranking[]=['alumno'=>$a,'puntaje'=>$final,'detalle'=>$det]; } }
usort($ranking,function($x,$y){ if(abs($y['puntaje']-$x['puntaje'])>0.0001) return $y['puntaje']<=>$x['puntaje']; $mx=empty($x['detalle'])?0:max(array_keys($x['detalle'])); $my=empty($y['detalle'])?0:max(array_keys($y['detalle'])); $px=$x['detalle'][$mx]['prom']??0; $py=$y['detalle'][$my]['prom']??0; return $py<=>$px; });
$title='Estadísticas'; ob_start(); ?>
<div class="card"><h2>Estadísticas de promedios (últimos 6 años con prioridad a los más recientes)</h2>
<form method="get" class="grid grid-3"><div><label>Hasta año</label><select name="hasta"><?= year_options($hasta) ?></select></div><div style="display:flex;align-items:flex-end"><button class="btn" type="submit">Ver</button></div></form></div>
<div class="card">
<?php if(!$ranking): ?><p>No hay promedios para el criterio seleccionado.</p>
<?php else: ?><h3>Abanderado y escoltas</h3><ol>
<?php for($i=0;$i<min(5,count($ranking));$i++): $r=$ranking[$i]; $a=$r['alumno']; ?><li style="margin:8px 0">
<img class="thumb" src="<?= h($a['foto']) ?>" onerror="this.style.display='none'"> <strong><?= h($a['apellido'].', '.$a['nombre']) ?></strong> — Puntaje: <strong><?= number_format($r['puntaje'],2) ?></strong> <span class="badge">Curso: <?= h($a['curso'].' '.$a['division']) ?></span></li>
<?php endfor; ?></ol><p class="muted">1° Abanderado • 2° y 3° Escoltas • 4° y 5° Suplentes</p>
<h3>Top 10</h3><table class="table"><thead><tr><th>#</th><th>Alumno</th><th>Puntaje</th><th>Detalle</th></tr></thead><tbody>
<?php foreach(array_slice($ranking,0,10) as $i=>$r): $a=$r['alumno']; ?><tr><td><?= $i+1 ?></td><td><img class="thumb" src="<?= h($a['foto']) ?>" onerror="this.style.display='none'"> <?= h($a['apellido'].', '.$a['nombre']) ?></td><td><strong><?= number_format($r['puntaje'],2) ?></strong></td><td>
<?php foreach($r['detalle'] as $y=>$d): ?><span class="badge"><?= $y ?>: <?= number_format($d['prom'],2) ?> (×<?= $d['peso'] ?>)</span> <?php endforeach; ?></td></tr><?php endforeach; ?></tbody></table>
<?php endif; ?></div>
<?php $content=ob_get_clean(); include __DIR__.'/layout.php'; 