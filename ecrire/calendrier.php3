<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


  // ce script peut etre recopie a la racine pour obtenir le calendrier
  // a partir de l'espace public. 
  // Evidemment les messages internes a la redaction seront absents.

include((@is_dir("ecrire") ? 'ecrire/' : '') . "inc_version.php3");

if (!_DIR_RESTREINT)
	include ("inc.php3");
 else {
	include_ecrire("inc_presentation.php3");
	include_ecrire("inc_calendrier.php");
	include_ecrire("inc_texte.php3");
	include_ecrire("inc_layer.php3");
 }

$today=getdate(time());
$jour_today = $today["mday"];
$mois_today = $today["mon"];
$annee_today = $today["year"];

// sans arguments => mois courant
if (!$mois){
  $jour=$jour_today;
  $mois=$mois_today;
  $annee=$annee_today;
 } else {if (!isset($jour)) $jour = 1;}

$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
$jour = journum($date);
$mois = mois($date);
$annee = annee($date);

if (!isset($type)) $type = 'mois';

$afficher_bandeau_calendrier = true;

if ($type == 'semaine') {
	$afficher_bandeau_calendrier_semaine = true;

	$titre = _T('titre_page_calendrier',
		    array('nom_mois' => nom_mois($date), 'annee' => $annee));
	  }
elseif ($type == 'jour') {
	$titre = nom_jour("$annee-$mois-$jour")." ".
	  affdate_jourcourt("$annee-$mois-$jour");
	$jour = $jour_today;
	$mois = $mois_today;
	$annee = $annee_today;
 }
 else {
	$type = 'mois';
	$jour = '01';
	$titre = _T('titre_page_calendrier',
		    array('nom_mois' => nom_mois($annee . '-' . sprintf("%02d", $mois) . '-01'), 
			  'annee' => $annee));
	  }

if (!_DIR_RESTREINT) 
  debut_page($titre,  "redacteurs", "calendrier");
 else debut_html($titre);

$f = 'http_calendrier_init_' . $type;
echo $f($jour,$mois,$annee, $date, $GLOBALS['PHP_SELF']);

if (!_DIR_RESTREINT) fin_page(); else 	echo "</body></html>\n";

?>
