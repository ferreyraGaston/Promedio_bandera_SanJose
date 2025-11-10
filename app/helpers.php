<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
function redirect($p){ header("Location: ".$p); exit; }
function swal($t,$ti,$te){ $_SESSION['swal']=['type'=>$t,'title'=>$ti,'text'=>$te]; }
function delete_file_safe($a){ if($a && file_exists($a) && is_file($a)) @unlink($a); }
function year_options($sel=null,$from=null,$to=null){ if(!$to){$to=(int)date('Y');} if(!$from){$from=$to-12;} $h=""; for($y=$to;$y>=$from;$y--){ $h.='<option value="'.$y.'"'.($sel==$y?' selected':'').'>'.$y.'</option>'; } return $h; }
