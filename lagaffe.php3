<?php

# generateur automatique d'un squelette de test
# le repertoire des squelettes doit etre accessibles en ecriture

include("ecrire/inc_serialbase.php3");
@include("mes_fonctions.php3");

$res = '';

foreach($tables_principales as $k => $v)
{
  $f = $v['field'];
  $k = strtoupper($k);
  if ($k=='FORUM') $k .='S'; else if ($k=='SYNDIC') $k .= 'ATION';
  $b = "BOUCLE_$k($k){0,1}";
  $res .= "<B_$k>\n<table border='1' width='100%'>\n";
  $res .= "<tr><td colspan=2 align=center>$b</td></tr>";
  $res .= "<$b>";
  foreach($f as $n => $t) {
    $n = strtoupper($n);
    $res .= "\n\t<tr><td>" . $n . "</td><td>#$n</td></tr>";
  }
  $res .= "\n</BOUCLE_$k>\n";
  $res .= "</table>\n</B_$k>\n";
  $res .= "<center>table $k vide</center>\n<//B_$k>\n<br><hr><br>\n";
}

$fond = "lagaffe";
$f = fopen($GLOBALS['dossier_squelettes'] . $fond. ".html",'w');
fwrite($f, $res);
fwrite($f, '<INCLUDE(casse-noisettes.php3)>');
fclose($f);

$flag_dynamique = true;
$delais = 0;

include ("inc-public.php3");

?>
