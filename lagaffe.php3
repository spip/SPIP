<?php

# generateur automatique d'un squelette de test
# le repertoire des squelettes doit etre accessibles en ecriture

include("ecrire/inc_serialbase.php3");
include("inc-compilo-index.php3");
include("inc-balises.php3");
include("inc-boucles.php3");

# décommenter au besoin, mais faire attention au double chargement.
#if (file_exists("mes_fonctions.php3")) include("mes_fonctions.php3");

$res = '';

foreach($table_des_tables as $k => $v)
{
  $k = strtoupper($k);
  $b = "BOUCLE_$k($k){0,1}";
  $res .= "<B_$k>\n<table border='1' width='100%'>\n";
  $res .= "<tr><td colspan=2 align=center>$b</td></tr>";
  $res .= "<$b>";
  $p = new Champ;
  $p->id_boucle = $k;
  $p->boucles = '';
  $p->id_mere = $k;
  $p->etoile = false; # le + dur
  $p->documents = true; # le + dur
  $p->statut = 'html';
  $p->type_requete = $v;
  $p->code ='';

  foreach($tables_principales[$v]['field'] as $n => $t) {
    $n = strtoupper($n);
    $res .= "\n\t<tr><td>" . $n . "</td><td>#$n</td></tr>";
    $p->nom_champ = $n;
    if (champs_traitements($p))
      $res .= "\n\t<tr><td>" . $n . "*</td><td>#$n*</td></tr>";

  }
  $res .= "\n</BOUCLE_$k>\n"
    . "</table>\n</B_$k>\n"
    . "<center>table $k "
    . (function_exists('boucle_' . $k . '_dist') ? 'vide' : 'inconnue')
    . "</center>\n<//B_$k>\n<br><hr><br>\n";
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
