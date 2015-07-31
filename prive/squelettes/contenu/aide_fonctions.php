<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

function aide_changer_langue($var_lang_r, $lang_r) {
	if ($var_lang_r)
		changer_langue($lang = $var_lang_r);
	if ($lang_r)
	  # pour le cas ou on a fait appel au menu de changement de langue
	  # (aide absente dans la langue x)
		changer_langue($lang = $lang_r);
	else $lang = $GLOBALS['spip_lang'];

	return $lang;
}

function aide_contenu(){
	static $contenu = null;
	if ($contenu) return $contenu;

	global $help_server;
	if (!is_array($help_server)) $help_server = array($help_server);
	$path = $GLOBALS['spip_lang'] . "-aide.html";

	include_spip('inc/aider');
	list($contenu, $lastm) = aide_fichier($path, $help_server);

	if (strpos($contenu,"aide_index")!==false){
		$contenu = preg_replace(",target=['\"][^'\"]*['\"],Uims","class='ajax'",$contenu);
		$contenu = str_replace("aide_index","aide",$contenu);
	}
	return $contenu;
}

function aide_extrait_section($aide){
	return aide_section($aide,aide_contenu());
}
/*
function aide_cache_image($help_server, $cache, $rep, $lang, $file, $ext) {
	if ($rep=="IMG" AND $lang=="cache"
	  AND @file_exists($img = _DIR_VAR.'cache-TeX/'.preg_replace(',^TeX-,', '', $file))) {
		return $img;
	}
	else if (@file_exists($img = _DIR_AIDE . $cache)) {
		return $img;
	}
	else if (@file_exists($img = _DIR_RACINE . 'AIDE/aide-'.$cache)) {
		return $img;
	}
	else {
		include_spip('inc/distant');
		sous_repertoire(_DIR_AIDE,'','',true);
		$img = "$help_server/$rep/$lang/$file";
		recuperer_page($img,$f=_DIR_AIDE . $cache);
		return $f;
	}
}
*/

// Affichage du menu de gauche avec analyse de la section demandee
// afin d'ouvrir le sous-menu correspondant a l'affichage a droite
// http://doc.spip.org/@help_menu_rubrique
function aide_menu($aide) {
	$contenu = aide_contenu();
	preg_match_all(_SECTIONS_AIDE, $contenu, $sections, PREG_SET_ORDER);

	return $sections;

	global $spip_lang;

	$afficher = false;
	$ligne = $numrub = 0;
	$texte = $res = '';
	foreach ($sections as $section) {
		list(,$prof, $sujet, $bloc) = $section;
		if ($prof == '1') {
			if ($afficher && $texte)
				$res .= block_parfois_visible("block$numrub", "<div class='rubrique'>$titre</div>", "\n$texte",'', $ouvrir);
			$afficher = $bloc ? ($bloc == 'redac') : true;
			$texte = '';
			if ($afficher) {
				$numrub++;
				$ouvrir = 0;
				$titre = $sujet;
			}
		} else {
			++$ligne;
			$id = "ligne$ligne";

			if ($aide == $sujet) {
				$ouvrir = 1;
				$class = "article-actif";
				$texte .= http_script("curr_article = '$id';");
			} else $class = "article-inactif";

			$h = generer_url_aide("aide=$sujet&frame=body&var_lang=$spip_lang");
			$texte .= "<a class='$class' target='droite' id='$id' href='$h' onclick=\"activer_article('$id');return true;\">"
			  . $bloc
			  . "</a><br style='clear:both;' />\n";
		}
	}
	if ($afficher && $texte)
		$res .= block_parfois_visible("block$numrub", "<div class='rubrique'>$titre</div>", "\n$texte",'', $ouvrir);
	return $res;
}