<?php
$cfg=require __DIR__.'/config.php'; $conn=new mysqli($cfg['db_host'],$cfg['db_user'],$cfg['db_pass'],$cfg['db_name']); if($conn->connect_error){die('DB');} $conn->set_charset('utf8mb4');
