<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_PUBLIC_GLOBAL")) return;
define("_INC_PUBLIC_GLOBAL", "1");

function inclure_subpage($fond, $delais_inclus, $contexte_inclus, $cache_incluant) {
	// ce perdant de PHP ne comprend pas f(x)[y]
	$page = inclure_page($fond, $delais_inclus, $contexte_inclus, $cache_incluant);
	return $page['texte']; 
}

function inclure_page($fond, $delais_inclus, $contexte_inclus, $cache_incluant='') {
	global $delais;
	static $pile_delais = '', $ptr_delais = 0;
	$ptr_delais++;
	$pile_delais[$ptr_delais] = $delais_inclus;

	spip_log("Inclusion dans $cache_incluant");
	$cle = $fond;
	if ($contexte_inclus)
		foreach($contexte_inclus as $k=>$v)
			$cle .= "&$k=$v";

	// Si on a inclus sans fixer le critere de lang, de deux choses l'une :
	// - on est dans la langue du site, et pas besoin d'inclure inc_lang
	// - on n'y est pas, et alors il faut revenir dans la langue par defaut
	if ($lang = $contexte_inclus['lang']
	|| ($GLOBALS['spip_lang'] != ($lang = lire_meta('langue_site')))) {
		include_ecrire('inc_lang.php3');
		lang_select($lang);
		$lang_select = true; // pour lang_dselect ci-dessous
	}

	$page = ramener_cache($cle,
			  'cherche_page_incluse',
			  array('fond' => $fond, 
				'cache_incluant' => $cache_incluant,
				'contexte' => $contexte_inclus),
			  $pile_delais[$ptr_delais]);
	
	if ($lang_select)
		lang_dselect();

	// si son de'lai est + court que l'incluant, il pre'domine
	if ($ptr_delais == 1) {
		if ($delais > $pile_delais[$ptr_delais])
			$delais = $pile_delais[$ptr_delais];
	}
	else { 
		if ($pile_delais[$ptr_delais-1] > $pile_delais[$ptr_delais])
			$pile_delais[$ptr_delais-1] = $pile_delais[$ptr_delais];
	}
	$ptr_delais--;
	return $page;
}

//
// Le bouton des administrateurs
//
function admin_page($cached, $texte) {
	if (!$GLOBALS['flag_preserver']
	&& ($admin = $GLOBALS['HTTP_COOKIE_VARS']['spip_admin'])) {
		include_local('inc-admin.php3');
		$a = afficher_boutons_admin($cached ? ' *' : '');

		// La constante doit etre definie a l'identique dans inc-form-squel
		// balise #FORMULAIRE_ADMIN ? sinon ajouter en fin de page
		if (!(strpos($texte, '<!-- @@formulaire_admin@@45609871@@ -->') === false))
			$texte = str_replace('<!-- @@formulaire_admin@@45609871@@ -->', $a, $texte);
		else
			$texte .= $a;
	}
	return $texte;
}

function cherche_image_nommee($nom, $dossier) {
	$formats = array ('gif', 'jpg', 'png');
	while (list(, $format) = each($formats)) {
		$d = "$dossier$nom.$format";
		if (file_exists($d))
			return ($d);
	}
}

function taches_de_fond() {
	// Gestion des taches de fond ?  toutes les 5 secondes
	// (on mettra 30 s quand on aura prevu la preemption par une image-cron)
	if (!@file_exists('ecrire/data/cron.lock')
	OR (time() - @filemtime('ecrire/data/cron.lock') > 5)) {

		// Si MySQL est out, laisser souffler
		if (!@file_exists('ecrire/data/mysql_out')
		OR (time() - @filemtime('ecrire/data/mysql_out') > 300)) {
			include_ecrire('inc_cron.php3');
			spip_cron();
		}
	}

	// Gestion des statistiques du site public
	// (a la fin pour ne pas forcer le $db_ok)
	if (lire_meta("activer_statistiques") != "non") {
		include_local ("inc-stats.php3");
		ecrire_stats();
	}
}

?>
