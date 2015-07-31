<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire("inc_presentation");

function admin_vider_dist()
{
  global
    $connect_id_auteur,
    $connect_statut,
    $connect_toutes_rubriques,
    $hash,
    $purger_index,
    $quota_cache,
    $spip_lang;

debut_page(_T('onglet_vider_cache'), "administration", "cache");


echo "<br><br><br>";
gros_titre(_T('titre_admin_vider'));
// barre_onglets("administration", "vider");


debut_gauche();

debut_boite_info();

echo _T('info_gauche_admin_vider');

fin_boite_info();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

// toujours vrai

if ($purger_index == "oui") {
	if (verifier_action_auteur("purger_index", $hash)) {
		include_ecrire('inc_index');
		purger_index();
		creer_liste_indexation();
	}
}




//
// Purger le cache
//


debut_cadre_trait_couleur("cache-24.gif", false, "", _T('texte_vider_cache'));


echo "\n<p align='justify'>"._T('texte_suppression_fichiers')."</p>",
	"<p align='justify'>"._T('texte_recalcul_page')."</p>";


echo "\n<div>&nbsp;</div>";



//
// Quota et taille du cache
//
debut_cadre_relief("", false, "", _T('taille_repertoire_cache'));

list ($taille) = spip_fetch_array(spip_query("SELECT SUM(taille) FROM spip_caches WHERE type='t'"));

if ($taille>0) {
	$info = _T('taille_cache_octets', array('octets' => taille_en_octets($taille)));
} else
	$info = _T('taille_cache_vide');

echo "<p align='justify'><b>$info</b></p>\n";

echo "\n<p align='justify'>";
if ($quota_cache) {
	echo _T('taille_cache_maxi',
		array('octets' => taille_en_octets($quota_cache*1024*1024)));
} else {
	echo _T('taille_cache_infinie');
}

 $action = 'purger';
 $arg = 'cache';
 $hash = calculer_action_auteur("$action $arg");

 echo ' (', _T('cache_modifiable_webmestre'),')</p>', 
  "\n<form action='", generer_url_public("spip_action.php"), "' method='POST'>", 
  "\n<input type='hidden' name='action' value='$action' />",
  "\n<input type='hidden' name='arg' value='$arg' />",
  "\n<input type='hidden' name='id_auteur' value='$connect_id_auteur' />", 
  "\n<input type='hidden' name='hash' value='$hash' />", 
  "\n<input type='hidden' name='redirect' value='", generer_url_ecrire("admin_vider"),
  "' />", 
  "\n<p><div align='right'><input class='fondo' type='submit' value=\"",
  str_replace('"', '&quot;', _T('bouton_vider_cache')),
  "\"></form></div>";

 fin_cadre_relief();


 debut_cadre_relief("image-24.gif", false, "", _T('info_images_auto'));

 echo "<div style='text-align: center;'>
<iframe width='530px' height='65px' src='",
   generer_action_auteur('purger','taille_vignettes') . "&lang=$spip_lang",
   "'></iframe>
</div>";

 $action = 'purger';
 $arg = 'vignettes';
 $hash = calculer_action_auteur("$action $arg");

 echo   "\n<form action='", generer_url_public("spip_action.php"), "' method='POST'>",
   "\n<input type='hidden' name='action' value='$action' />",
   "\n<input type='hidden' name='arg' value='$arg' />",
   "\n<input type='hidden' name='id_auteur' value='$connect_id_auteur' />",
   "\n<input type='hidden' name='hash' value='$hash' />",
   "\n<input type='hidden' name='redirect' value='",generer_url_ecrire("admin_vider"),
   "' />", 
   "\n<p><DIV align='right'><input class='fondo' type='submit'  value=\"",
   str_replace('"', '&quot;', _T('bouton_vider_cache')),
   "\" /></form></div>";

fin_cadre_relief();

fin_cadre_trait_couleur();

//
// Purger la base d'indexation
//
debut_cadre_trait_couleur("racine-site-24.gif", false, "", _T('texte_effacer_donnees_indexation'));

	echo "\n<p align='justify'>";
	if ($GLOBALS['meta']['activer_moteur'] == 'oui')
		echo _T('texte_moteur_recherche_active');
	else {
		echo "<b>"._T('texte_moteur_recherche_non_active')."</b> ";
		$row = spip_fetch_array(spip_query("SELECT COUNT(*) AS cnt FROM spip_index"));
		if ($row['cnt'])
			echo _T('texte_commande_vider_tables_indexation');
		else
			echo _T('texte_tables_indexation_vides');
	
	}
	
	$hash = calculer_action_auteur("purger_index");

	echo generer_url_post_ecrire("admin_vider");
	echo "\n<INPUT TYPE='hidden' NAME='hash' VALUE='$hash' />";
	echo "\n<INPUT TYPE='hidden' NAME='purger_index' VALUE='oui' />";
	echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE=\""._T('bouton_effacer_index')."\"></FORM></DIV>";

fin_cadre_trait_couleur();

echo "<br />";

fin_page();
}

?>

