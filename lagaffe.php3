<?php

# generateur automatique d'un squelette de test
# le repertoire des squelettes doit etre accessibles en ecriture

# pour ne pas etre logé automatiquement lors du test de balises FORMULAIRE
# demander au client de tuer son cookie et de rappeler ce script aussitot
$url = $_SERVER['REQUEST_URI'];
if (!strpos($url,'cookie_killed='))
  {
    setcookie("", "", time()-3600);
    header("Location: $url" . 
	   (strpos($url,'?') ? '&' : '?') .
	   'cookie_killed=oui');
    exit;
    } 
include("ecrire/inc_serialbase.php3");
include("inc-compilo-index.php3");
include("inc-balises.php3");
include("inc-boucles.php3");

# décommenter au besoin, mais faire attention au double chargement.
#if (file_exists("mes_fonctions.php3")) include("mes_fonctions.php3");

function dispose_boucle($nom, $corps, $criteres, $avant, $apres, $sinon)
{
  return
    ($avant ? "<B_$nom>$avant" : '') .
    "<BOUCLE_$nom$criteres>$corps</BOUCLE_$nom>" .
    ($apres ? "$apres</B_$nom>" : '') .
    ($sinon ? "$sinon<//B_$nom>" : '');
}

function dispose_champs($type)
{
  $corps = '';
  $p = new Champ;
  $p->id_boucle = '';
  $p->boucles = '';
  $p->id_mere = '';
  $p->etoile = false; # le + dur
  $p->documents = true; # le + dur
  $p->statut = 'html';
  $p->type_requete = '';
  $p->code ='';
  global $tables_principales;
  foreach($tables_principales[$type]['field'] as $n => $t) {
    $n = strtoupper($n);
    $corps .= "\n\t<tr><td>" . $n . "</td><td>#$n</td></tr>";
    $p->nom_champ = $n;
    if (champs_traitements($p))
      $corps .= "\n\t<tr><td>" . $n . "*</td><td>#$n*</td></tr>";
  }
  return $corps . "\n";
}

function table_nulle($nom, $type) {
  return
    ("\n<center>table $type " .
     (function_exists('boucle_' . $nom . '_dist') ? 'vide' : 'inconnue') .
     "</center>\n");
}

$res = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="fr">
<head><title>Lagaffe</title></head><body>
';
#$res .= "<b>url: $url</b><br>";
$la_semantique_folle_a_encore_frappe = $table_des_tables;

foreach($table_des_tables as $k => $v)
{
  $nom = strtoupper($k);
  $criteres = "($nom){0,1}";
  $les_champs = dispose_champs($v);
  $corps = $les_champs;

  // faire toute la combinatoire dépasse les 30 secondes sur un G4 à 1.33
  // on ne fait que ça du coup.

  if ($k == 'rubriques')
    {

  foreach($la_semantique_folle_a_encore_frappe as $k2 => $v2)
    {
      if ($k2 != $k)
	{
	  $nom2 = strtoupper($k2);
	  $nomdouble = $nom .'_englobant_' . $nom2;
	  $criteres2 = "($nom2){0,1}";
	  $corps .= "\n" .
	    dispose_boucle($nomdouble,
			   $les_champs,
			   $criteres2,
			   ("\n<table border='1' width='100%'>\n" .
			    "<tr><td colspan=2 align=center> BOUCLE $nomdouble $criteres2</td></tr>\n"),
			   "\n</table>\n",
			   table_nulle($k2, $nom2));
	}
    }
    }
  $res .= "\n" .
    dispose_boucle($nom, 
		   $corps,
		   $criteres,
		   ("\n<table border='1' width='100%'>\n" .
		    "<tr><td colspan=2 align=center> BOUCLE $nom $criteres</td></tr>\n"),
		   "\n</table>\n",
		   table_nulle($k, $nom)) .
    "\n<br><hr><br>\n";
}

$fond = "lagaffe";
$nom = $GLOBALS['dossier_squelettes'] . $fond. ".html";
if (!($f = fopen($nom, 'w')))
  {if (function_exists("php_sapi_name")  AND eregi("cgi", @php_sapi_name()))
      Header("Status: 503");
    else Header("HTTP/1.0 Service Unavailable");
    echo ("impossible d'écrire le fichier de tests $nom");
    exit;
  }
fwrite($f, $res);
fwrite($f, '<INCLUDE(casse-noisettes.php3)>');
fclose($f);

$flag_dynamique = true;
$delais = 0;

include ("inc-public.php3");
$res .= "</body></html>";
?>
