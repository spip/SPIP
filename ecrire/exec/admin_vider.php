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

include_spip('inc/presentation');
include_spip('inc/actions');

// http://doc.spip.org/@exec_admin_vider_dist
function exec_admin_vider_dist()
{
  global $connect_toutes_rubriques,  $quota_cache, $spip_lang;

$commencer_page = charger_fonction('commencer_page', 'inc');
echo $commencer_page(_T('onglet_vider_cache'), "configuration", "cache");


echo "<br /><br /><br />";
gros_titre(_T('titre_admin_vider'));
// barre_onglets("administration", "vider");


debut_gauche();

debut_boite_info();

echo _T('info_gauche_admin_vider');

fin_boite_info();

debut_droite();

if (!$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	echo fin_page();
	exit;
}

debut_cadre_trait_couleur("cache-24.gif", false, "", _T('texte_vider_cache'));

echo "\n<p align='justify'>"._T('texte_suppression_fichiers')."</p>",
	"<p align='justify'>"._T('texte_recalcul_page')."</p>";

echo "\n<div>&nbsp;</div>";


//
// Quota et taille du cache
//
debut_cadre_relief("", false, "", _T('taille_repertoire_cache'));

$cpt = spip_fetch_array(spip_query("SELECT SUM(taille) AS n FROM spip_caches WHERE type='t'"));

if ($cpt = $cpt['n']) {
	$info = _T('taille_cache_octets', array('octets' => taille_en_octets($cpt)));
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

 echo ' (', _T('cache_modifiable_webmestre'),')</p>', 
   generer_action_auteur('purger',
	'cache',
	generer_url_ecrire("admin_vider"),
	"\n<div align='right'><input class='fondo' type='submit' value=\"" .
			 str_replace('"', '&quot;', _T('bouton_vider_cache')) .
			 "\" /></div>",
	" method='post'");
 fin_cadre_relief();


 debut_cadre_relief("image-24.gif", false, "", _T('info_images_auto'));

 echo "<div style='text-align: center;'>
<iframe width='530px' height='65px'\nsrc='",
   generer_action_auteur('purger','taille_vignettes') . "&amp;lang=$spip_lang",
   "'></iframe>
</div>";

 echo generer_action_auteur('purger',
	'vignettes',
	generer_url_ecrire("admin_vider"),
	"\n<div align='right'><input class='fondo' type='submit' value=\"" .
			 str_replace('"', '&quot;', _T('bouton_vider_cache')) .
			 "\" /></div>",
	" method='post'");

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
		$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_index"));
		if ($cpt['n'])
			echo _T('texte_commande_vider_tables_indexation');
		else
			echo _T('texte_tables_indexation_vides');
	
	}
	echo '</p>';
	echo generer_action_auteur('purger',
		'index',
		generer_url_ecrire("admin_vider"),
		"\n<div align='right'><input class='fondo' type='submit' value=\"" .
			 str_replace('"', '&quot;', _T('bouton_effacer_index')) .
			 "\" /></div>",
		" method='post'");

fin_cadre_trait_couleur();

echo "<br />";

echo fin_page();
}

?>
