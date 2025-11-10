<?php
require_once __DIR__ . '/../app/helpers.php'; require_once __DIR__ . '/../app/db.php'; session_start();
function ensure_upload(){ $d=__DIR__.'/../uploads'; if(!is_dir($d)) @mkdir($d,0775,true); return $d;}
if(isset($_GET['delete'])){ $id=(int)$_GET['delete']; $q=$conn->prepare("SELECT foto FROM alumnos WHERE id=?"); $q->bind_param('i',$id); $q->execute(); $f=$q->get_result()->fetch_column(); if($f){ delete_file_safe(__DIR__.'/../'.$f); } $conn->query("DELETE FROM alumnos WHERE id=".$id); swal('success','Eliminado','Alumno y foto eliminados.'); redirect('alumnos.php'); }
if($_SERVER['REQUEST_METHOD']==='POST'){ $id=(int)($_POST['id']??0); $nombre=trim($_POST['nombre']??''); $apellido=trim($_POST['apellido']??''); $dni=trim($_POST['dni']??''); $curso=trim($_POST['curso']??''); $division=trim($_POST['division']??''); $foto=null;
  if(isset($_FILES['foto']) && $_FILES['foto']['error']!==UPLOAD_ERR_NO_FILE){ $err=$_FILES['foto']['error']; if($err===UPLOAD_ERR_OK){ $ext=strtolower(pathinfo($_FILES['foto']['name'],PATHINFO_EXTENSION)); if(!in_array($ext,['jpg','jpeg','png','gif','webp'])){ swal('warning','Formato inválido','Subí JPG/PNG/GIF/WEBP'); redirect('alumnos.php'); } $name='uploads/'.uniqid('alu_').'.'.$ext; $abs=__DIR__.'/../'.$name; ensure_upload(); if(!@move_uploaded_file($_FILES['foto']['tmp_name'],$abs)){ if(!@copy($_FILES['foto']['tmp_name'],$abs)){ swal('error','Error','No se pudo guardar la imagen.'); redirect('alumnos.php'); } } @chmod($abs,0664); $foto=$name; } else { swal('error','Error','No se pudo subir la imagen (código '.$err.')'); redirect('alumnos.php'); } }
  if($id){ if($foto){ $q=$conn->prepare("SELECT foto FROM alumnos WHERE id=?"); $q->bind_param('i',$id); $q->execute(); $old=$q->get_result()->fetch_column(); if($old){ delete_file_safe(__DIR__.'/../'.$old);} $st=$conn->prepare("UPDATE alumnos SET nombre=?,apellido=?,dni=?,curso=?,division=?,foto=? WHERE id=?"); $st->bind_param('ssssssi',$nombre,$apellido,$dni,$curso,$division,$foto,$id);} else { $st=$conn->prepare("UPDATE alumnos SET nombre=?,apellido=?,dni=?,curso=?,division=? WHERE id=?"); $st->bind_param('sssssi',$nombre,$apellido,$dni,$curso,$division,$id);} $st->execute(); swal('success','Actualizado','Alumno actualizado.'); }
  else { $st=$conn->prepare("INSERT INTO alumnos(nombre,apellido,dni,curso,division,foto) VALUES (?,?,?,?,?,?)"); $st->bind_param('ssssss',$nombre,$apellido,$dni,$curso,$division,$foto); $st->execute(); swal('success','Creado','Alumno creado.'); }
  redirect('alumnos.php'); }
$edit=null; if(isset($_GET['edit'])){ $id=(int)$_GET['edit']; $q=$conn->prepare("SELECT * FROM alumnos WHERE id=?"); $q->bind_param('i',$id); $q->execute(); $edit=$q->get_result()->fetch_assoc(); }
$rows=$conn->query("SELECT * FROM alumnos ORDER BY apellido,nombre")->fetch_all(MYSQLI_ASSOC);
$title='Alumnos'; ob_start(); ?>
<div class="grid grid-2"><div class="card"><h2><?= $edit?'Editar Alumno':'Nuevo Alumno' ?></h2>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= h($edit['id']??'') ?>"><div class="grid grid-2">
<div><label>Nombre</label><input name="nombre" required value="<?= h($edit['nombre']??'') ?>"></div>
<div><label>Apellido</label><input name="apellido" required value="<?= h($edit['apellido']??'') ?>"></div>
<div><label>DNI</label><input name="dni" value="<?= h($edit['dni']??'') ?>"></div>
<div><label>Curso</label><input name="curso" placeholder="1-6" value="<?= h($edit['curso']??'') ?>"></div>
<div><label>División</label><input name="division" placeholder="A, B..." value="<?= h($edit['division']??'') ?>"></div>
<div><label>Foto (opcional)</label><input type="file" name="foto" accept="image/*"></div>
</div><?php if(!empty($edit['foto'])): ?><p><img class="avatar" src="<?= h($edit['foto']) ?>"></p><?php endif; ?>
<button class="btn" type="submit"><?= $edit?'Guardar cambios':'Crear alumno' ?></button></form></div>
<div class="card"><h2>Listado</h2><table class="table"><thead><tr><th>Foto</th><th>Apellido y Nombre</th><th>Curso</th><th>DNI</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr>
<td><?php if($r['foto']): ?><img class="thumb" src="<?= h($r['foto']) ?>"><?php endif; ?></td>
<td><?= h($r['apellido'].' '.$r['nombre']) ?></td><td><?= h(($r['curso']??'').' '.($r['division']??'')) ?></td><td><?= h($r['dni']) ?></td>
<td class="row-actions"><a class="btn sm" href="alumnos.php?edit=<?= $r['id'] ?>">Editar</a><a class="btn sm red" onclick="return confirm('¿Eliminar alumno y su foto?')" href="alumnos.php?delete=<?= $r['id'] ?>">Eliminar</a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php $content=ob_get_clean(); include __DIR__.'/layout.php'; 