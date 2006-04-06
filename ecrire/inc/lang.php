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

//
// Charger un fichier langue
//
function chercher_module_lang($module, $lang = '') {
	if ($lang)
		$lang = '_'.$lang;

	// 1) dans un repertoire nomme lang/ se trouvant sur le chemin
	if ($f = include_spip('lang/'.$module.$lang, false))
		return $f;

	// 2) directement dans le chemin (old style)
	return include_spip($module.$lang, false);
}

function charger_langue($lang, $module = 'spip') {
	if ($fichier_lang = chercher_module_lang($module, $lang)) {
		$GLOBALS['idx_lang']='i18n_'.$module.'_'.$lang;
		include_once($fichier_lang);
	} else {
		// si le fichier de langue du module n'existe pas, on se rabat sur
		// la langue par defaut du site -- et au pire sur le francais, qui
		// *par definition* doit exister, et on copie le tableau dans la
		// var liee a la langue
		$l = $GLOBALS['meta']['langue_site'];
		if (!$fichier_lang = chercher_module_lang($module, $l))
			$fichier_lang = chercher_module_lang($module, 'fr');

		if ($fichier_lang) {
			$GLOBALS['idx_lang']='i18n_'.$module.'_' .$l;
			include($fichier_lang);
			$GLOBALS['i18n_'.$module.'_'.$lang]
				= &$GLOBALS['i18n_'.$module.'_'.$l];
			#spip_log("module de langue : ${module}_$l.php");
		}
	}
}

//
// Surcharger le fichier de langue courant avec un autre (tordu, hein...)
//
function surcharger_langue($fichier) {

	$idx_lang_normal = $GLOBALS['idx_lang'];
	$idx_lang_surcharge = $GLOBALS['idx_lang'].'_temporaire';
	$GLOBALS['idx_lang'] = $idx_lang_surcharge;
	include($fichier);
	if (is_array($GLOBALS[$idx_lang_surcharge])) {
		$GLOBALS[$idx_lang_normal] = array_merge(
			$GLOBALS[$idx_lang_normal],
			$GLOBALS[$idx_lang_surcharge]
		);
	}
	unset ($GLOBALS[$idx_lang_surcharge]);
	$GLOBALS['idx_lang'] = $idx_lang_normal;
}



//
// Changer la langue courante
//
function changer_langue($lang) {
	global $all_langs, $spip_lang_rtl, $spip_lang_right, $spip_lang_left, $spip_lang_dir, $spip_dir_lang;

	$liste_langues = $all_langs.','.$GLOBALS['meta']['langues_multilingue'];

	if ($lang && ereg(",$lang,", ",$liste_langues,")) {
		$GLOBALS['spip_lang'] = $lang;

		$spip_lang_rtl =   lang_dir($lang, '', '_rtl');
		$spip_lang_left =  lang_dir($lang, 'left', 'right');
		$spip_lang_right = lang_dir($lang, 'right', 'left');
		$spip_lang_dir =   lang_dir($lang);
		$spip_dir_lang = " dir='$spip_lang_dir'";

		return true;
	}
	else
		return false;
}

//
// Regler la langue courante selon les infos envoyees par le brouteur
//
function regler_langue_navigateur() {
	$accept_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	if (is_array($accept_langs)) {
		while(list(, $s) = each($accept_langs)) {
			if (eregi('^([a-z]{2,3})(-[a-z]{2,3})?(;q=[0-9.]+)?$', trim($s), $r)) {
				$lang = strtolower($r[1]);
				if (changer_langue($lang)) return $lang;
			}
		}
	}
	return false;
}


//
// Traduire une chaine internationalisee
//
function traduire_chaine($code) {
	global $spip_lang;

	// modules par defaut
	if (_DIR_RESTREINT)
		$modules = array('spip');
	else
		$modules = array('spip', 'ecrire');

	// modules demandes explicitement
	$code_ori = $code; # le garder pour le fallback plus tard
	if (strpos($code, ':')) {
		if (ereg("^([a-z/]+):(.*)$", $code, $regs)) {
			$modules = explode("/",$regs[1]);
			$code = $regs[2];
		}
	}

	// parcourir tous les modules jusqu'a ce qu'on trouve
	foreach ($modules as $module) {
		$var = "i18n_".$module."_".$spip_lang;
		if (empty($GLOBALS[$var])) {
			charger_langue($spip_lang, $module);

			// surcharge perso -- on cherche (lang/)local_xx.php ...
			if ($f = chercher_module_lang('local', $spip_lang))
				surcharger_langue($f);
			// ... puis (lang/)local.php
			if ($f = chercher_module_lang('local'))
				surcharger_langue($f);
		}
		if (isset($GLOBALS[$var][$code])) break;
	}

	$text = $GLOBALS[$var][$code];

	// fallback langues pas finies ou en retard (eh oui, c'est moche...)
	if ($spip_lang<>'fr') {
		$text = ereg_replace("^<(NEW|MODIF)>","",$text);
		if (!$text) {
			$spip_lang_temp = $spip_lang;
			$spip_lang = 'fr';
			$text = traduire_chaine($code_ori);
			$spip_lang = $spip_lang_temp;
		}
	}
	return $text;
}


function traduire_nom_langue($lang) {
	include_spip('inc/lang_liste');
	$r = $GLOBALS['codes_langues'][$lang];
	if (!$r) $r = $lang;

		include_spip('inc/charsets');
		$r = html2unicode($r);

	return $r;
}

//
// Filtres de langue
//

// afficher 'gaucher' si la langue est arabe, hebreu, persan, 'droitier' sinon
// utilise par #LANG_DIR, #LANG_LEFT, #LANG_RIGHT
function lang_dir($lang, $droitier='ltr', $gaucher='rtl') {
	if ($lang=='fa' OR $lang=='ar' OR $lang == 'he')
		return $gaucher;
	else
		return $droitier;
}

function lang_typo($lang) {
	if ($lang == 'eo' OR $lang == 'fr' OR substr($lang, 0, 3) == 'fr_' OR $lang == 'cpf')
		return 'fr';
	else if ($lang)
		return 'en';
	else
		return false;
}

// service pour que l'espace prive reflete la typo et la direction des objets affiches
function changer_typo($lang = '', $source = '') {
	global $lang_typo, $lang_dir, $dir_lang;

	if (ereg("^(article|rubrique|breve|auteur)([0-9]+)", $source, $regs)) {
		$r = spip_fetch_array(spip_query("SELECT lang FROM spip_".$regs[1]."s WHERE id_".$regs[1]."=".$regs[2]));
		$lang = $r['lang'];
	}

	if (!$lang)
		$lang = $GLOBALS['meta']['langue_site'];

	$lang_typo = lang_typo($lang);
	$lang_dir = lang_dir($lang);
	$dir_lang = " dir='$lang_dir'";
}

// selectionner une langue
function lang_select ($lang='') {
	global $pile_langues, $spip_lang;
	array_push($pile_langues, $spip_lang);
	changer_langue($lang);
}

// revenir a la langue precedente
function lang_dselect ($rien='') {
	global $pile_langues;
	changer_langue(array_pop($pile_langues));
}


//
// Afficher un menu de selection de langue
// - 'var_lang_ecrire' = langue interface privee,
// - 'var_lang' = langue de l'article, espace public
// - 'changer_lang' = langue de l'article, espace prive
// 
function menu_langues($nom_select = 'var_lang', $default = '', $texte = '', $herit = '', $lien='') {
	global $couleur_foncee, $connect_id_auteur;

	$ret = liste_options_langues($nom_select, $default, $herit);

	if (!$ret) return '';

	if (!$couleur_foncee) $couleur_foncee = '#044476';

	if (!$lien)
		$lien = self();

	if ($nom_select == 'changer_lang') {
		$lien = parametre_url($lien, 'changer_lang', '');
		$lien = parametre_url($lien, 'url', '');
		$cible = '';
	} else {
		if (_DIR_RESTREINT) {
			$cible = $lien;
		} else {
			$cible = _DIR_RESTREINT_ABS . $lien;
			if (_FILE_CONNECT) {
				include_spip('inc/session');
				$args = "id_auteur=$connect_id_auteur&valeur=".calculer_action_auteur('var_lang_ecrire', $connect_id_auteur);
			}
		}
		$lien = generer_url_action('cookie', $args);
	}

	return "<form action='$lien' method='post' style='margin:0px; padding:0px;'>"
	  . (!$cible ? '' : "<input type='hidden' name='url' value='$cible' />")
	  . $texte
	  . "<select name='$nom_select' "
	  . (_DIR_RESTREINT ?
	     ("class='forml' style='vertical-align: top; max-height: 24px; margin-bottom: 5px; width: 120px;'") :
	     (($nom_select == 'var_lang_ecrire')  ?
	      ("class='verdana1' style='background-color: " . $couleur_foncee
	       . "; max-height: 24px; border: 1px solid white; color: white; width: 100px;'") :
	      "class='fondl'"))
	  . "\nonchange=\"document.location.href='"
	  . parametre_url($lien, 'url', str_replace('&amp;', '&', $cible))
	  ."&amp;$nom_select='+this.options[this.selectedIndex].value\">\n"
	  . $ret
	  . "</select>\n"
	  . "<noscript><div style='display:inline;'><input type='submit' name='Valider' value='&gt;&gt;' class='spip_bouton' /></div></noscript>\n"
	  . "</form>\n";
}

function liste_options_langues($nom_select, $default='', $herit='') {

	if ($default == '') $default = $GLOBALS['spip_lang'];
	if ($nom_select == 'var_lang_ecrire')
		$langues = explode(',', $GLOBALS['all_langs']);
	else
		$langues = explode(',', $GLOBALS['meta']['langues_multilingue']);

	if (count($langues) <= 1) return '';
	$ret = '';
	sort($langues);
	foreach ($langues as $l) {
		$selected = ($l == $default) ? ' selected=\'selected\'' : '';
		if ($l == $herit) {
			$ret .= "<option class='maj-debut' style='font-weight: bold;' value='herit'$selected>"
				.traduire_nom_langue($herit)." ("._T('info_multi_herit').")</option>\n";
		}
		## ici ce serait bien de pouvoir choisir entre "langue par defaut"
		## et "langue heritee"
		else
			$ret .= "<option class='maj-debut' value='$l'$selected>".traduire_nom_langue($l)."</option>\n";
	}
	return $ret;
}

//
// Cette fonction est appelee depuis public/global si on a installe
// la variable de personnalisation $forcer_lang ; elle renvoie le brouteur
// si necessaire vers l'URL xxxx?lang=ll
//
function verifier_lang_url() {
	global $_GET, $_COOKIE, $spip_lang;

	// quelle langue est demandee ?
	$lang_demandee = $GLOBALS['meta']['langue_site'];
	if ($_COOKIE['spip_lang_ecrire'])
		$lang_demandee = $_COOKIE['spip_lang_ecrire'];
	if ($_COOKIE['spip_lang'])
		$lang_demandee = $_COOKIE['spip_lang'];
	if ($_GET['lang'])
		$lang_demandee = $_GET['lang'];

	// Verifier que la langue demandee existe
	lang_select($lang_demandee);
	$lang_demandee = $spip_lang;

	// Renvoyer si besoin
	if (!($_GET['lang']<>'' AND $lang_demandee == $_GET['lang'])
	AND !($_GET['lang']=='' AND $lang_demandee == $GLOBALS['meta']['langue_site']))
	{
		$destination = parametre_url(self(),'lang', $lang_demandee, '&');
		if ($d = $GLOBALS['var_mode'])
			$destination = parametre_url($destination, 'var_mode', $d, '&');
		redirige_par_entete($destination);
	}

	// Subtilite : si la langue demandee par cookie est la bonne
	// alors on fait comme si $lang etait passee dans l'URL
	// (pour criteres {lang}).
	$GLOBALS['lang'] = $_GET['lang'] = $spip_lang;
}


//
// Selection de langue haut niveau
//
function utiliser_langue_site() {
	changer_langue($GLOBALS['langue_site']);
}

function utiliser_langue_visiteur() {
	global $_COOKIE;

	if (!regler_langue_navigateur())
		utiliser_langue_site();

	if (!empty($GLOBALS['auteur_session']['lang']))
		changer_langue($GLOBALS['auteur_session']['lang']);

	$cookie_lang = (_DIR_RESTREINT  ? 'spip_lang' : 'spip_lang_ecrire');
	if (!empty($_COOKIE[$cookie_lang]))

		changer_langue($_COOKIE[$cookie_lang]);
}

// Une fonction qui donne le repertoire ou trouver des fichiers de langue
// note : pourrait en donner une liste... complique
function repertoire_lang($module='spip', $lang='fr') {
	# valeur forcee (par ex.sur spip.net), old style, a faire disparaitre
	if (defined('_DIR_LANG'))
		return _DIR_LANG;

	# regarder s'il existe une v.f. qq part
	if ($f = include_spip('lang/'.$module.'_'.$lang, false));
		return dirname($f).'/';

	# sinon, je ne sais trop pas quoi dire...
	return _DIR_INCLUDE.'lang/';
}

//
// Initialisation
//
function init_langues() {
	global $all_langs, $langue_site;
	global $pile_langues, $lang_typo, $lang_dir;

	$all_langs = $GLOBALS['meta']['langues_proposees']
		.$GLOBALS['meta']['langues_proposees2'];
	$pile_langues = array();
	$lang_typo = '';
	$lang_dir = '';

	$toutes_langs = Array();
	if (!$all_langs || !$langue_site || !_DIR_RESTREINT) {
		if (!$d = @opendir(repertoire_lang())) return;
		while (($f = readdir($d)) !== false) {
			if (ereg('^spip_([a-z_]+)\.php[3]?$', $f, $regs))
				$toutes_langs[] = $regs[1];
		}
		closedir($d);
		sort($toutes_langs);
		$all_langs2 = join(',', $toutes_langs);

		// Si les langues n'ont pas change, ne rien faire
		if ($all_langs2 != $all_langs) {
			$all_langs = $all_langs2;
			if (!$langue_site) {
				// Initialisation : le francais par defaut, sinon la premiere langue trouvee
				if (ereg(',fr,', ",$all_langs,")) $langue_site = 'fr';
				else list(, $langue_site) = each($toutes_langs);
				if (function_exists('ecrire_meta'))
					ecrire_meta('langue_site', $langue_site);
			}
				if (function_exists('ecrire_meta')) {
				# sur spip.net le nombre de langues proposees fait exploser
				# ce champ limite a 255 caracteres ; a revoir...
				if (strlen($all_langs) <= 255) {
					ecrire_meta('langues_proposees', $all_langs);
					effacer_meta('langues_proposees2');
				} else {
					ecrire_meta('langues_proposees', substr($all_langs,0,255));
					ecrire_meta('langues_proposees2', substr($all_langs,255));
				}
				ecrire_metas();
			}
		}
	}
}

init_langues();
utiliser_langue_site();


?>
