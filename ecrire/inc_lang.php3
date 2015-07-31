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


// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LANG")) return;
define("_ECRIRE_INC_LANG", "1");


//
// Charger un fichier langue
//

function charger_langue($lang, $module = 'spip') {

	$fichier_lang = $module.'_'.$lang.'.php3';
	$fichier_lang_exists = @is_readable(_DIR_LANG . $fichier_lang);

	if ($fichier_lang_exists) {
		$GLOBALS['idx_lang']='i18n_'.$module.'_'.$lang;
		include_lang($fichier_lang);
	} else {
		// si le fichier de langue du module n'existe pas, on se rabat sur
		// la langue par defaut du site -- et au pire sur le francais, qui
		// *par definition* doit exister, et on copie le tableau dans la
		// var liee a la langue
		$l = lire_meta('langue_site');
		if (!is_readable(_DIR_LANG . $module.'_'.$l.'.php3'))
			$l = 'fr';
		$fichier_lang = $module.'_' .$l. '.php3';
		if (is_readable(_DIR_LANG . $fichier_lang)) {
			$GLOBALS['idx_lang']='i18n_'.$module.'_' .$l;
			include_lang($fichier_lang);
			$GLOBALS['i18n_'.$module.'_'.$lang]
				= &$GLOBALS['i18n_'.$module.'_'.$l];
			#spip_log("module de langue : ${module}_$l.php3");
		}
	}

	// surcharge perso -- on cherche le fichier local(_xx).php3 dans le chemin
	if ($f = (find_in_path('local.php3')))
		surcharger_langue($f);
	if ($f = (find_in_path('local_'.$lang.'.php3')))
		surcharger_langue($f);
	// compatibilite ascendante : chercher aussi local_xx.php3 dans ecrire/lang/
	else if (@is_readable($f = _DIR_LANG . 'local_'.$lang.'.php3'))
		surcharger_langue($f);
}

//
// Surcharger le fichier de langue courant avec un autre (tordu, hein...)
//
function surcharger_langue($f) {
	$idx_lang_normal = $GLOBALS['idx_lang'];
	$GLOBALS['idx_lang'] .= '_temporaire';
	include($f);

	if (is_array($GLOBALS[$GLOBALS['idx_lang']]))
		foreach ($GLOBALS[$GLOBALS['idx_lang']] as $var => $val)
			$GLOBALS[$idx_lang_normal][$var] = $val;

	unset ($GLOBALS[$GLOBALS['idx_lang']]);
	$GLOBALS['idx_lang'] = $idx_lang_normal;
}



//
// Changer la langue courante
//
function changer_langue($lang) {
	global $all_langs, $spip_lang_rtl, $spip_lang_right, $spip_lang_left, $spip_lang_dir, $spip_dir_lang;

	$liste_langues = $all_langs.','.lire_meta('langues_multilingue');

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
	global $_SERVER, $_COOKIE;

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
function traduire_chaine($code, $args) {
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
	$text = '';
	while (!$text AND (list(,$module) = each ($modules))) {
		$var = "i18n_".$module."_".$spip_lang;
		if (empty($GLOBALS[$var]))
			charger_langue($spip_lang, $module);
		$text = $GLOBALS[$var][$code];
	}

	// fallback langues pas finies ou en retard (eh oui, c'est moche...)
	if ($spip_lang<>'fr') {
		$text = ereg_replace("^<(NEW|MODIF)>","",$text);
		if (!$text) {
			$spip_lang_temp = $spip_lang;
			$spip_lang = 'fr';
			$text = traduire_chaine($code_ori, $args);
			$spip_lang = $spip_lang_temp;
		}
	}

	// inserer les variables
	if (!$args) return $text;
	while (list($name, $value) = each($args))
		$text = str_replace ("@$name@", $value, $text);
	return $text;
}


function traduire_nom_langue($lang) {
	init_codes_langues();
	$r = $GLOBALS['codes_langues'][$lang];
	if (!$r) $r = $lang;

		include_ecrire("inc_charsets.php3");
		$r = html2unicode($r);

	return $r;
}

function init_codes_langues() {
	$GLOBALS['codes_langues'] = array(
	'aa' => "Afar",
	'ab' => "Abkhazian",
	'af' => "Afrikaans",
	'am' => "Amharic",
	'ar' => "&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;",
	'as' => "Assamese",
	'ast' => "asturiano",
	'ay' => "Aymara",
	'az' => "&#1040;&#1079;&#1241;&#1088;&#1073;&#1072;&#1112;&#1209;&#1072;&#1085;",
	'ba' => "Bashkir",
	'be' => "&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1110;",
	'bg' => "&#1073;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;",
	'bh' => "Bihari",
	'bi' => "Bislama",
	'bm' => "Bambara",
	'bn' => "Bengali; Bangla",
	'bo' => "Tibetan",
	'br' => "brezhoneg",
	'ca' => "catal&#224;",
	'co' => "corsu",
	'cpf' => "Kr&eacute;ol r&eacute;yon&eacute;",
	'cpf_dom' => "Krey&ograve;l",
	'cpf_hat' => "Kr&eacute;y&ograve;l (P&eacute;yi Dayiti)",
	'cs' => "&#269;e&#353;tina",
	'cy' => "Cymraeg",	# welsh, gallois
	'da' => "dansk",
	'de' => "Deutsch",
	'dz' => "Bhutani",
	'el' => "&#949;&#955;&#955;&#951;&#957;&#953;&#954;&#940;",
	'en' => "English",
	'en_hx' => "H4ck3R",
	'eo' => "Esperanto",
	'es' => "Espa&#241;ol",
	'es_co' => "Colombiano",
	'et' => "eesti",
	'eu' => "euskara",
	'fa' => "&#1601;&#1575;&#1585;&#1587;&#1609;",
	'ff' => "Fulah", // peul
	'fi' => "suomi",
	'fj' => "Fiji",
	'fo' => "f&#248;royskt",
	'fon' => "fongb&egrave;",
	'fr' => "fran&#231;ais",
	'fr_tu' => "fran&#231;ais copain",
	'fy' => "Frisian",
	'ga' => "Irish",
	'gd' => "Scots Gaelic",
	'gl' => "galego",
	'gn' => "Guarani",
	'gu' => "Gujarati",
	'ha' => "Hausa",
	'he' => "&#1506;&#1489;&#1512;&#1497;&#1514;",
	'hi' => "&#2361;&#2367;&#2306;&#2342;&#2368;",
	'hr' => "hrvatski",
	'hu' => "magyar",
	'hy' => "Armenian",
	'ia' => "Interlingua",
	'id' => "Indonesia",
	'ie' => "Interlingue",
	'ik' => "Inupiak",
	'is' => "&#237;slenska",
	'it' => "italiano",
	'iu' => "Inuktitut",
	'ja' => "&#26085;&#26412;&#35486;",
	'jw' => "Javanese",
	'ka' => "&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;",
	'kk' => "&#1178;&#1072;&#1079;&#1072;&#1097;b",
	'kl' => "Greenlandic",
	'km' => "Cambodian",
	'kn' => "Kannada",
	'ko' => "&#54620;&#44397;&#50612;",
	'ks' => "Kashmiri",
	'ku' => "Kurdish",
	'ky' => "Kirghiz",
	'la' => "Latin",
	'lb' => "L&euml;tzebuergesch",
	'ln' => "Lingala",
	'lo' => "Laothian",
	'lt' => "lietuvi&#371;",
	'lu' => "luba-katanga",
	'lv' => "latvie&#353;u",
	'mg' => "Malagasy",
	'mi' => "Maori",
	'mk' => "&#1084;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080; &#1112;&#1072;&#1079;&#1080;&#1082;",
	'ml' => "Malayalam",
	'mn' => "Mongolian",
	'mo' => "Moldavian",
	'mos' => "Mor&eacute;",
	'mr' => "&#2350;&#2352;&#2366;&#2336;&#2368;",
	'ms' => "Bahasa Malaysia",
	'mt' => "Maltese",
	'my' => "Burmese",
	'na' => "Nauru",
	'ne' => "Nepali",
	'nl' => "Nederlands",
	'no' => "norsk",
	'nb' => "norsk bokm&aring;l",
	'nn' => "norsk nynorsk",
	'oc' => "&ograve;c",
	'oc_lnc' => "&ograve;c lengadocian",
	'oc_ni' => "&ograve;c ni&ccedil;ard",
	'oc_ni_la' => "&ograve;c ni&ccedil;ard (larg)",
	'oc_prv' => "&ograve;c proven&ccedil;au",
	'oc_gsc' => "&ograve;c gascon",
	'oc_lms' => "&ograve;c lemosin",
	'oc_auv' => "&ograve;c auvernhat",
	'oc_va' => "&ograve;c vivaroaupenc",
	'om' => "(Afan) Oromo",
	'or' => "Oriya",
	'pa' => "Punjabi",
	'pl' => "polski",
	'ps' => "Pashto, Pushto",
	'pt' => "Portugu&#234;s",
	'pt_br' => "Portugu&#234;s do Brasil",
	'qu' => "Quechua",
	'rm' => "Rhaeto-Romance",
	'rn' => "Kirundi",
	'ro' => "rom&#226;n&#259;",
	'ru' => "&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;",
	'rw' => "Kinyarwanda",
	'sa' => "&#2360;&#2306;&#2360;&#2381;&#2325;&#2371;&#2340;",
	'sc' => "sarde",
	'sd' => "Sindhi",
	'sg' => "Sangho",
	'sh' => "srpskohrvastski",
	'sh_lat' => 'srpskohrvastski',
	'sh_cyr' => '&#1057;&#1088;&#1087;&#1089;&#1082;&#1086;&#1093;&#1088;&#1074;&#1072;&#1090;&#1089;&#1082;&#1080;',
	'si' => "Sinhalese",
	'sk' => "sloven&#269;ina",	// (Slovakia)
	'sl' => "sloven&#353;&#269;ina",	// (Slovenia)
	'sm' => "Samoan",
	'sn' => "Shona",
	'so' => "Somali",
	'sq' => "shqipe",
	'sr' => "&#1089;&#1088;&#1087;&#1089;&#1082;&#1080;",
	'ss' => "Siswati",
	'st' => "Sesotho",
	'su' => "Sundanese",
	'sv' => "svenska",
	'sw' => "Kiswahili",
	'ta' => "&#2980;&#2990;&#3007;&#2996;&#3021;", // Tamil
	'te' => "Telugu",
	'tg' => "Tajik",
	'th' => "&#3652;&#3607;&#3618;",
	'ti' => "Tigrinya",
	'tk' => "Turkmen",
	'tl' => "Tagalog",
	'tn' => "Setswana",
	'to' => "Tonga",
	'tr' => "T&#252;rk&#231;e",
	'ts' => "Tsonga",
	'tt' => "&#1058;&#1072;&#1090;&#1072;&#1088;",
	'tw' => "Twi",
	'ug' => "Uighur",
	'uk' => "&#1091;&#1082;&#1088;&#1072;&#1111;&#1085;&#1100;&#1089;&#1082;&#1072;",
	'ur' => "&#1649;&#1585;&#1583;&#1608;",
	'uz' => "U'zbek",
	'vi' => "Ti&#7871;ng Vi&#7879;t",
	'vo' => "Volapuk",
	'wo' => "Wolof",
	'xh' => "Xhosa",
	'yi' => "Yiddish",
	'yo' => "Yoruba",
	'za' => "Zhuang",
	'zh' => "&#20013;&#25991;",
	'zu' => "Zulu");
}

//
// Filtres de langue
//

// afficher 'gaucher' si la langue est arabe, hebreu, kurde, persan,
// 'droitier' sinon ; utilise par #LANG_DIR, #LANG_LEFT, #LANG_RIGHT
function lang_dir($lang, $droitier='ltr', $gaucher='rtl') {
	if ($lang=='fa' OR $lang=='ar' OR $lang == 'he' OR $lang == 'ku')
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
		$lang = lire_meta('langue_site');

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
function menu_langues($nom_select = 'var_lang', $default = '', $texte = '', $herit = '') {
	global $couleur_foncee, $couleur_claire, $connect_id_auteur;

	$ret = liste_options_langues($nom_select, $default, $herit);

	if (!$ret) return '';

	if (!$couleur_foncee) $couleur_foncee = '#044476';

	$lien = $GLOBALS['clean_link'];

	if ($nom_select == 'changer_lang') {
		$lien->delvar('changer_lang');
		$lien->delvar('url');
		$post = $lien->getUrl();
		$cible = '';
	} else {
		// eviter un bug a l'installation ; mais, dans le cas general,
		// pourquoi aurait-on besoin ici d'une URL absolue ?
		if (!defined('_ECRIRE_INSTALL')
		AND !defined('_TEST_DIRS'))
			$site = lire_meta("adresse_site");
		if (!$site)
			if (_DIR_RESTREINT)
				$site = '.';
			else
				$site = '..';

		if (!_DIR_RESTREINT) {
			include_ecrire('inc_admin.php3');
			$cible = _DIR_RESTREINT_ABS . $lien->getUrl();
			$post = "$site/spip_cookie.php3?id_auteur=$connect_id_auteur&amp;valeur=".calculer_action_auteur('var_lang_ecrire', $connect_id_auteur);
		} else {
			$cible = $lien->getUrl();
			$post = "$site/spip_cookie.php3";
		}
	}

	$postcomplet = new Link($post);
	if ($cible) $postcomplet->addvar('url', $cible);

	return "<form action='"
	  . $post
	  . "' method='post' style='margin:0px; padding:0px;'>"
	  . (!$cible ? '' : "<input type='hidden' name='url' value='".quote_amp($cible)."' />")
	  . $texte
	  . "<select name='$nom_select' "
	  . (_DIR_RESTREINT ?
	     ("class='forml' style='vertical-align: top; max-height: 24px; margin-bottom: 5px; width: 120px;'") :
	     (($nom_select == 'var_lang_ecrire')  ?
	      ("class='verdana1' style='background-color: " . $couleur_foncee
	       . "; max-height: 24px; border: 1px solid white; color: white; width: 100px;'") :
	      "class='fondl'"))
	  . " onchange=\"document.location.href='"
	  . $postcomplet->geturl()
	  ."&amp;$nom_select='+this.options[this.selectedIndex].value\">\n"
	  . $ret
	  . "</select>\n"
	  . "<noscript><input type='submit' name='Valider' value='&gt;&gt;' class='spip_bouton' /></noscript>"
	  . "</form>";
}

function liste_options_langues($nom_select, $default='', $herit='') {

	if ($default == '') $default = $GLOBALS['spip_lang'];
	if ($nom_select == 'var_lang_ecrire')
		$langues = explode(',', $GLOBALS['all_langs']);
	else
		$langues = explode(',', lire_meta('langues_multilingue'));

	if (count($langues) <= 1) return '';
	$ret = '';
	sort($langues);
	while (list(, $l) = each ($langues)) {
		$selected = ($l == $default) ? ' selected=\'selected\'' : '';
		if ($l == $herit) {
			$ret .= "<option class='maj-debut' style='font-weight: bold;' value='herit'$selected>"
				.traduire_nom_langue($herit)." ("._T('info_multi_herit').")</option>\n";
		}
		else $ret .= "<option class='maj-debut' value='$l'$selected>".traduire_nom_langue($l)."</option>\n";
	}
	return $ret;
}

//
// Cette fonction est appelee depuis inc-public-global si on a installe
// la variable de personnalisation $forcer_lang ; elle renvoie le brouteur
// si necessaire vers l'URL xxxx?lang=ll
//
function verifier_lang_url() {
	global $_GET, $_COOKIE, $spip_lang, $clean_link;

	// quelle langue est demandee ?
	$lang_demandee = lire_meta('langue_site');
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
	AND !($_GET['lang']=='' AND $lang_demandee == lire_meta('langue_site')))
	{
		$destination = new Link;
		$destination->addvar('lang', $lang_demandee);
		if ($d = $GLOBALS['var_mode'])
			$destination->addvar('var_mode', $d);
		redirige_par_entete($destination->getUrl());
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

//
// Initialisation
//
function init_langues() {
	global $all_langs, $langue_site;
	global $pile_langues, $lang_typo, $lang_dir;

	$all_langs = lire_meta('langues_proposees')
		.lire_meta('langues_proposees2');
	$langue_site = lire_meta('langue_site');
	$pile_langues = array();
	$lang_typo = '';
	$lang_dir = '';

	$toutes_langs = Array();
	if (!$all_langs || !$langue_site || !_DIR_RESTREINT) {
		if (!$d = @opendir(_DIR_LANG)) return;
		while ($f = readdir($d)) {
			if (ereg('^spip_([a-z_]+)\.php3?$', $f, $regs))
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
				if (defined("_ECRIRE_INC_META"))
					ecrire_meta('langue_site', $langue_site);
			}
			if (defined("_ECRIRE_INC_META")) {
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
