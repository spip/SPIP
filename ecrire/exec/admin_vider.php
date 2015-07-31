<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/actions');


// http://doc.spip.org/@calculer_taille_dossier
function calculer_taille_dossier ($dir) {
	$handle = @opendir($dir);
	if (!$handle) return;

	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if (is_file($d = "$dir/$fichier")) {
			$taille += filesize($d);
		}
		else if (is_dir($d))
			$taille += calculer_taille_dossier($d);
	}
	closedir($handle);
	return $taille;
}



// http://doc.spip.org/@afficher_taille_cache_vignettes
function afficher_taille_cache_vignettes() {
	$taille = calculer_taille_dossier(_DIR_VAR);
	return _T('ecrire:taille_cache_image',
		array(
			'dir' => joli_repertoire(_DIR_VAR),
			'taille' => "<b>".taille_en_octets($taille)."</b>"
			)
		);
}

// http://doc.spip.org/@exec_admin_vider_dist
function exec_admin_vider_dist()
{
  global $quota_cache, $spip_lang;

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

// autorisation a affiner
if (!autoriser('configurer', 'admin_vider')) {
	echo _T('avis_non_acces_page');
	echo fin_gauche(), fin_page();
	exit;
}

debut_cadre_trait_couleur("cache-24.gif", false, "", _T('texte_vider_cache'));

echo "\n<p style='text-align: justify;'>"._T('texte_suppression_fichiers')."</p>",
	"<p style='text-align: justify;'>"._T('texte_recalcul_page')."</p>";

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

echo "<p style='text-align: justify;'><b>$info</b></p>\n";

echo "\n<p style='text-align: justify;'>";
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
	"\n<div style='text-align: right'><input class='fondo' type='submit' value=\"" .
			 str_replace('"', '&quot;', _T('bouton_vider_cache')) .
			 "\" /></div>",
	" method='post'");
 fin_cadre_relief();


 debut_cadre_relief("image-24.gif", false, "", _T('info_images_auto'));

 echo afficher_taille_cache_vignettes();

 echo generer_action_auteur('purger',
	'vignettes',
	generer_url_ecrire("admin_vider"),
	"\n<div style='text-align: right'><input class='fondo' type='submit' value=\"" .
			 str_replace('"', '&quot;', _T('bouton_vider_cache')) .
			 "\" /></div>",
	" method='post'");

fin_cadre_relief();

fin_cadre_trait_couleur();

//
// Purger la base d'indexation
//
debut_cadre_trait_couleur("racine-site-24.gif", false, "", _T('texte_effacer_donnees_indexation'));

	echo "\n<p style='text-align: justify;'>";
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
		"\n<div style='text-align: right'><input class='fondo' type='submit' value=\"" .
			 str_replace('"', '&quot;', _T('bouton_effacer_index')) .
			 "\" /></div>",
		" method='post'");

fin_cadre_trait_couleur();

echo "<br />";

echo fin_gauche(), fin_page();
}

?>
