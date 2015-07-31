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

// Mettre a jour la liste locale des miroirs
// http://doc.spip.org/@maj_miroirs_ortho
function maj_miroirs_ortho() {
	$liste = explode(" ", $GLOBALS['meta']["liste_miroirs_ortho"]);
	$miroirs_old = array();
	foreach ($liste as $index) {
		list($url) = explode(" ", $GLOBALS['meta']["miroir_ortho_$index"]);
		$miroirs_old[$url] = $index;
	}

	$page = recuperer_page($GLOBALS['services']['ortho']?$GLOBALS['services']['ortho']:'http://www.spip.net/services/ortho.txt');
	$urls=array();
	foreach(explode("\n", $page) as $ligne) {
		if(trim($ligne)=="") continue;
		$t= explode(";", $ligne);
		$urls[$t[0]]=$t[1];
	}

	$liste = array();
	$miroirs_new = array();
	$index = 1;
	foreach ($urls as $url => $contact) {
		if ($index_old = $miroirs_old[$url]) {
			$s = $GLOBALS['meta']["miroir_ortho_$index_old"];
		}
		else {
			$s = $url." ".time();
		}
		$miroirs_new[$index] = $s;
		$liste[] = $index++;
	}
	foreach ($miroirs_old as $index) {
		effacer_meta("miroir_ortho_$index");
	}
	foreach ($miroirs_new as $index => $s) {
		ecrire_meta("miroir_ortho_$index", $s);
	}
	ecrire_meta("liste_miroirs_ortho", join(" ", $liste));
}

// Lire la liste des miroirs et les langues associees
// http://doc.spip.org/@lire_miroirs_ortho
function lire_miroirs_ortho() {
	global $miroirs_ortho, $index_miroirs_ortho, $duree_cache_miroirs_ortho;

	$miroirs_ortho = array();
	$index_miroirs_ortho = array();

	$t = time();
	$maj = $GLOBALS['meta']["maj_miroirs_ortho"];
	if ($maj < $t - $duree_cache_miroirs_ortho) {
		maj_miroirs_ortho();
		ecrire_meta("maj_miroirs_ortho", $t);
		lire_metas();
	}

	$liste = explode(" ", $GLOBALS['meta']["liste_miroirs_ortho"]);
	foreach ($liste as $index) {
		$s = explode(" ", $GLOBALS['meta']["miroir_ortho_$index"]);
		$url = $s[0];
		$maj = $s[1];
		$langs = explode(",", $s[2]);
		// Reinitialiser periodiquement la liste des langues non-supportees
		if ($maj < $t - $duree_cache_miroirs_ortho) {
			foreach ($langs as $key => $lang) {
				if (substr($lang, 0, 1) == '!') unset($langs[$key]);
			}
			$s[1] = $t;
			$s[2] = join(",", $langs);
			ecrire_meta("miroir_ortho_$index", join(" ", $s));
		}
		$index_miroirs_ortho[$url] = $index;
		$miroirs_ortho[$url] = array();
		foreach ($langs as $lang) {
			if ($lang) $miroirs_ortho[$url][$lang] = $lang;
		}
	}
	lire_metas();
	mt_srand(time());
}

// Sauvegarder les infos de langues pour le miroir
// http://doc.spip.org/@ecrire_miroir_ortho
function ecrire_miroir_ortho($url, $langs) {
	global $index_miroirs_ortho;

	$index = $index_miroirs_ortho[$url];
	$s = explode(" ", $GLOBALS['meta']["miroir_ortho_$index"]);
	$s[2] = join(",", $langs);
	ecrire_meta("miroir_ortho_$index", join(" ", $s));
}

// http://doc.spip.org/@ajouter_langue_miroir
function ajouter_langue_miroir($url, $lang) {
	global $miroirs_ortho;
	$langs = $miroirs_ortho[$url];
	$langs[$lang] = $lang;
	unset($langs["!$lang"]);
	ecrire_miroir_ortho($url, $langs);
}

// http://doc.spip.org/@enlever_langue_miroir
function enlever_langue_miroir($url, $lang) {
	global $miroirs_ortho;
	$langs = $miroirs_ortho[$url];
	unset($langs[$lang]);
	$langs["!$lang"] = "!$lang";
	ecrire_miroir_ortho($url, $langs);
}

// http://doc.spip.org/@reset_miroir
function reset_miroir($url) {
	global $miroirs_ortho;
	ecrire_miroir_ortho($url, array());
}

//
// Renvoie la liste des miroirs utilisables pour une langue donnee
//
// http://doc.spip.org/@chercher_miroirs_ortho
function chercher_miroirs_ortho($lang) {
	global $miroirs_ortho;
	
	$result = array();
	$chercher = true;
	foreach ($miroirs_ortho as $url => $langs) {
		if ($langs[$lang]) {
			$result[] = $url;
		}
		else if ($chercher && !$langs["!$lang"]) {
			if (verifier_langue_miroir($url, $lang)) $result[] = $url;
			// Ne recuperer la langue d'un miroir qu'une seule fois par requete
			if ($result) $chercher = false;
		}
	}
	return $result;
}

// http://doc.spip.org/@choisir_miroirs_ortho
function choisir_miroirs_ortho($lang) {
	$liste = chercher_miroirs_ortho($lang);
	if (!count($liste)) return false;
	foreach ($liste as $url) {
		$miroirs[md5(mt_rand().$url.rand())] = $url;
	}
	ksort($miroirs);
	return $miroirs;
}

//
// Envoyer une requete a un serveur d'orthographe
//
// http://doc.spip.org/@post_ortho
function post_ortho($url, $texte, $lang) {

	$gz = ($GLOBALS['flag_gz'] && strlen($texte) >= 200);
	$boundary = '';
	$vars = array(
		'op' => 'spell',
		'lang' => $lang,
		'texte' => $texte,
		'gz' => $gz ? 1 : 0
	);
	// Si le texte est petit, l'overhead du multipart est dispendieux
	// Sinon, on passe en multipart pour compresser la chaine a corriger
	if ($gz) {
		// Il faut eliminer les caracteres 0 sinon PHP ne lit pas la suite du parametre
		// passe en multipart/form-data (gros hack bien sale)
		$texte_gz = gzcompress($texte);
		for ($echap = 255; $echap > 0; $echap--) {
			$str_echap = chr($echap ^ 1).chr($echap).chr($echap).chr($echap ^ 2);
			if (!is_int(strpos($texte_gz, $str_echap))) break;
		}
		$texte_gz = str_replace("\x00", $str_echap, $texte_gz);
		$vars['texte'] = $texte_gz;
		$vars['nul_echap'] = $str_echap;
		$boundary = substr(md5(rand().'ortho'), 0, 8);
	}
	
  $r = recuperer_page($url, false, false, 1048576, $vars, $boundary, true);

	// decompression de GZ ortho
	if ($gz) $r = gzuncompress($r);
	return $r;
	
/*
 * Note a propos de la compression : si on ne refuse pas le gz dans recuperer_page(),
 * le serveur d'ortho va retourner des donnees compressees deux fois ; le code
 * saurait les decompresser deux fois, mais on perd alors beaucoup de temps (on
 * passe, dans un test, de 5 s a 25 s de delai !)
 */

}

//
// Verifier si un serveur gere une langue donnee
//
// http://doc.spip.org/@verifier_langue_miroir
function verifier_langue_miroir($url, $lang) {
	// Envoyer une requete bidon
	$result = post_ortho($url, " ", $lang);
	if (!preg_match(',<ortho>.*</ortho>,s', $result)) {
		reset_miroir($url);
		return false;
	}
	if (!preg_match(',<erreur>.*<code>E_LANG_ABSENT</code>.*</erreur>,s', $result)) {
		ajouter_langue_miroir($url, $lang);
		return true;
	}
	enlever_langue_miroir($url, $lang);
	return false;
}


//
// Gestion du dictionnaire local
//
// http://doc.spip.org/@suggerer_dico_ortho
function suggerer_dico_ortho(&$mots, $lang) {
	$result = spip_query("SELECT mot FROM spip_ortho_dico WHERE lang=" . _q($lang) . " AND mot IN (".join(", ", array_map('_q', $mots)).")");

	$mots = array_flip($mots);
	$bons = array();
	if (isset($mots[''])) unset($mots['']);
	while ($row = spip_fetch_array($result)) {
		$mot = $row['mot'];
		if (isset($mots[$mot])) {
			unset($mots[$mot]);
			$bons[] = $mot;
		}
	}

	if (count($mots)) $mots = array_flip($mots);
	else $mots = array();
	return $bons;
}

// http://doc.spip.org/@ajouter_dico_ortho
function ajouter_dico_ortho($mot, $lang) {
	global $connect_id_auteur;

	spip_query("INSERT IGNORE INTO spip_ortho_dico (lang, mot, id_auteur)  VALUES (" . _q($lang) . ", " . _q($mot) . ", $connect_id_auteur)");

}

// http://doc.spip.org/@supprimer_dico_ortho
function supprimer_dico_ortho($mot, $lang) {
	spip_query("DELETE FROM spip_ortho_dico WHERE lang=" . _q($lang) . " AND mot=" . _q($mot));

}

// http://doc.spip.org/@gerer_dico_ortho
function gerer_dico_ortho($lang) {
	global $ajout_ortho, $supp_ortho;
	if ($mot = strval($ajout_ortho)) {
		ajouter_dico_ortho($mot, $lang);
	}
	if ($mot = strval($supp_ortho)) {
		supprimer_dico_ortho($mot, $lang);
	}
}


//
// Gestion du cache de corrections
//
// http://doc.spip.org/@suggerer_cache_ortho
function suggerer_cache_ortho(&$mots, $lang) {
	global $duree_cache_ortho;

	$result = spip_query("SELECT mot, ok, suggest FROM spip_ortho_cache WHERE lang=" . _q($lang) . " AND mot IN (".join(", ", array_map('_q', $mots)).") AND maj > FROM_UNIXTIME(".(time() - $duree_cache_ortho).")");

	
	$mots = array_flip($mots);
	$suggest = array();
	if (isset($mots[''])) unset($mots['']);
	while ($row = spip_fetch_array($result)) {
		$mot = $row['mot'];
		if (isset($mots[$mot])) {
			unset($mots[$mot]);
			if (!$row['ok']) {
				if (strlen($row['suggest']))
					$suggest[$mot] = explode(",", $row['suggest']);
				else
					$suggest[$mot] = array();
			}
		}
	}
	if (count($mots)) $mots = array_flip($mots);
	else $mots = array();
	return $suggest;
}

// http://doc.spip.org/@ajouter_cache_ortho
function ajouter_cache_ortho($tous, $mauvais, $lang) {
	global $duree_cache_ortho;

	$values = array();
	$lang = _q($lang);
	if (count($mauvais)) {
		foreach ($mauvais as $mot => $suggest) {
			$values[] = "($lang, " . _q($mot) . ", 0, "._q(join(",", $suggest)).")";
		}
	}
	if (count($tous)) {
		foreach ($tous as $mot) {
			if (!isset($mauvais[$mot]))
				$values[] = "($lang, " . _q($mot) . ", 1, '')";
		}
	}
	if (count($values)) {
		spip_query("DELETE FROM spip_ortho_cache WHERE maj < FROM_UNIXTIME(".(time() - $duree_cache_ortho).")");

		spip_query("INSERT IGNORE INTO spip_ortho_cache (lang, mot, ok, suggest) VALUES ".join(", ", $values));

	}
}


//
// Cette fonction doit etre appelee pour reecrire le texte en utf-8 "propre"
//
// http://doc.spip.org/@preparer_ortho
function preparer_ortho($texte, $lang) {
	include_spip('inc/charsets');

	$charset = $GLOBALS['meta']['charset'];

	if ($charset == 'utf-8')
		return unicode_to_utf_8(html2unicode($texte));
	else
		return unicode_to_utf_8(html2unicode(charset2unicode($texte, $charset, true)));
}

// http://doc.spip.org/@afficher_ortho
function afficher_ortho($texte) {
	$charset = $GLOBALS['meta']['charset'];
	if ($charset == 'utf-8') return $texte;

	if (!is_array($texte)) return charset2unicode($texte, 'utf-8');
	foreach ($texte as $key => $val) {
		$texte[$key] = afficher_ortho($val);
	}
	return $texte;
}

//
// Cette fonction envoie le texte prepare a un serveur d'orthographe
// et retourne un tableau de mots mal orthographies associes chacun a un tableau de mots suggeres
//
// http://doc.spip.org/@corriger_ortho
function corriger_ortho($texte, $lang, $charset = 'AUTO') {
	include_spip('inc/charsets');
	include_spip("inc/indexation");
	include_spip('inc/filtres');

	$texte = preg_replace(',<code>.*?</code>,is', '', $texte);
	$texte = preg_replace(',<cadre>.*?</cadre>,is', '', $texte);
	$texte = preg_replace(',\[([^][]*)->([^][]*)\],is', '\\1', $texte);
	$texte = supprimer_tags($texte);

	$texte = " ".$texte." ";
	
	// Virer les caracteres non-alphanumeriques
	if (test_pcre_unicode()) {
		$texte = preg_replace(',[^-\''.pcre_lettres_unicode().']+,us', ' ', $texte);
	}
	else {
		// Ici bidouilles si PCRE en mode UTF-8 ne fonctionne pas correctement ...
		// Caracteres non-alphanumeriques de la plage latin-1 + saloperies non-conformes
		$texte = preg_replace(',\xC2[\x80-\xBF],', ' ', $texte);
		// Poncutation etendue (unicode)
		$texte = preg_replace(",".plage_punct_unicode().",", ' ', $texte);
		// Caracteres ASCII non-alphanumeriques
		$texte = preg_replace(",[^-a-zA-Z0-9\x80-\xFF']+,", ' ', $texte);
	}
	$texte = preg_replace(', [-\']+,', ' ', $texte); // tirets de typo
	$texte = preg_replace(',\' ,', ' ', $texte); // apostrophes utilisees comme guillemets

	// Virer les mots contenant au moins un chiffre
	$texte = preg_replace(', ([^ ]*\d[^ ]* )+,', ' ', $texte);

	// Melanger les mots
	$mots = preg_split(', +,', $texte);
	sort($mots);
	$mots = array_unique($mots);

	// 1. Enlever les mots du dico local
	$bons = suggerer_dico_ortho($mots, $lang);

	// 2. Enlever les mots du cache local
	$result_cache = suggerer_cache_ortho($mots, $lang);

	// 3. Envoyer les mots restants a un serveur
	$mauvais = array();
	if (count($mots)) {
		$texte = join(' ', $mots);
		
		// Hack : ligatures en francais pas gerees par aspell
		unset($trans_rev);
		$texte_envoi = $texte;
		if ($lang == 'fr') {
			$trans = array(chr(197).chr(146) => 'OE', chr(197).chr(147) => 'oe', 
					chr(195).chr(134) => 'AE', chr(195).chr(166) => 'ae');
			$texte_envoi = strtr($texte_envoi, $trans);
			$trans_rev = array_flip($trans);
		}
		
		// POST de la requete et recuperation du resultat XML
		$urls = choisir_miroirs_ortho($lang);
		if (!$urls) return false;
		unset($ok);
		$erreur = false;
		foreach ($urls as $url) {
			$xml = post_ortho($url, $texte_envoi, $lang);
			if ($xml && preg_match(',<ortho>(.*)</ortho>,s', $xml, $r)) {
				$xml = $r[1];
				if (preg_match(',<erreur>.*<code>(.*)</code>.*</erreur>,s', $xml, $r)) 
					$erreur = $r[1];
				if (preg_match(',<ok>(.*)</ok>,s', $xml, $r)) {
					$ok = $r[1];
					break;
				}
			}
			reset_miroir($url);
		}
		if (!isset($ok)) return $erreur;

		// Remplir le tableau des resultats (mots mal orthographies)
		if ($trans_rev) {
			$assoc_mots = array_flip($mots);
		}
		while (preg_match(',<mot>(.*?)</mot>(\s*<suggest>(.*?)</suggest>)?,s', $ok, $r)) {
			$p = strpos($ok, $r[0]);
			$ok = substr($ok, $p + strlen($r[0]));
			$mot = $r[1];
			if ($suggest = $r[3]) 
				$s = preg_split('/[ ,]+/', $suggest);
			else 
				$s = array();
			// Hack ligatures
			if ($trans_rev) {
				$mot_rev = strtr($mot, $trans_rev);
				if ($mot != $mot_rev) {
					if ($assoc_mots[$mot]) 
						$mauvais[$mot] = $s;
					if ($assoc_mots[$mot_rev]) 
						$mauvais[$mot_rev] = $s;
				}
				else $mauvais[$mot] = $s;
			}
			else $mauvais[$mot] = $s;
		}
	}
	if (!$erreur) ajouter_cache_ortho($mots, $mauvais, $lang);

	// Retour a l'envoyeur
	$mauvais = array_merge($result_cache, $mauvais);
	$result = array(
		'bons' => $bons,
		'mauvais' => $mauvais
	);
	if ($erreur) $result['erreur'] = $erreur;
	return $result;
}

//
// Fonctions d'affichage HTML
//

// http://doc.spip.org/@panneau_ortho
function panneau_ortho($ortho_result) {
	global $id_suggest;

	$id_suggest = array();
	$i = 1;

	$mauvais = $ortho_result['mauvais'];
	$bons = $ortho_result['bons'];
	if (!count($mauvais) && !count($bons)) return;
	ksort($mauvais);

	$panneau = "<script type='text/javascript'><!--
	var curr_suggest = null;
// http://doc.spip.org/@suggest
	function suggest(id) {
		var menu_box;
		if (curr_suggest)
			document.getElementById('suggest' + curr_suggest).className = 'suggest-inactif';
		if (1 || id!=curr_suggest) {
			document.getElementById('suggest' + id).className = 'suggest-actif';
			curr_suggest = id;
		}
		else curr_suggest = null;
		menu_box = document.getElementById('select_ortho');
		if (menu_box.length > id) menu_box.selectedIndex = id;
	}";
	$panneau .= "//--></script>";

	$panneau .= "<form class='form-ortho verdana2' action='' method='get'>\n";
	$panneau .= "<select name='select_ortho' id='select_ortho' onChange='suggest(this.selectedIndex);'>\n";
	$panneau .= "<option value='0'>... "._T('ortho_mots_a_corriger')." ...</option>\n";
	foreach ($mauvais as $mot => $suggest) {
		$id = $id_suggest[$mot] = "$i";
		$i++;
		$mot_html = afficher_ortho($mot);
		$panneau .= "<option value='$id'>$mot_html</option>\n";
	}
	foreach ($bons as $mot) {
		$id = $id_suggest[$mot] = "$i";
		$i++;
	}
	$panneau .= "</select>\n";
	$panneau .= "</form>\n";
	// Mots mal orthographies :
	// liste des suggestions plus lien pour ajouter au dico
	foreach ($mauvais as $mot => $suggest) {
		$id = $id_suggest[$mot];
		$mot_html = afficher_ortho($mot);
		$panneau .= "<div class='suggest-inactif' id='suggest$id'>";
		$panneau .= "<span class='ortho'>$mot_html</span>\n";
		$panneau .= "<div class='detail'>\n";
		if (is_array($suggest) && count($suggest)) {
			$panneau .= "<ul>\n";
			$i = 0;
			foreach ($suggest as $sug) {
				if (++$i > 12) {
					$panneau .= "<li><i>(...)</i></li>\n";
					break;
				}
				$panneau .= "<li>".typo(afficher_ortho($sug))."</li>\n";
			}
			$panneau .= "</ul>\n";
		}
		else {
			$panneau .= "<i>"._T('ortho_aucune_suggestion')."</i>";
		}
		$panneau .= "<br />";
		$lien = parametre_url(self(), 'supp_ortho', '');
		$lien = parametre_url($lien, 'ajout_ortho', $mot);
		$panneau .= icone_horizontale(_T('ortho_ajouter_ce_mot'), $lien, "ortho-24.gif", "creer.gif", false);
		$panneau .= "</div>\n";
		$panneau .= "</div>\n\n";
	}
	// Mots trouves dans le dico :
	// message plus lien pour retirer du dico
	foreach ($bons as $mot) {
		$id = $id_suggest[$mot];
		$mot_html = afficher_ortho($mot);
		$panneau .= "<div class='suggest-inactif' id='suggest$id'>";
		$panneau .= "<span class='ortho-dico'>$mot_html</span>";
		$panneau .= "<div class='detail'>\n";
		$panneau .= "<i>"._T('ortho_ce_mot_connu')."</i>";
		$panneau .= "<br />";
		$lien = parametre_url(self(), 'ajout_ortho', '');
		$lien = parametre_url($lien, 'supp_ortho', $mot);
		$panneau .= icone_horizontale(_T('ortho_supprimer_ce_mot'), $lien, "ortho-24.gif", "supprimer.gif");
		$panneau .= "</div>\n";
		$panneau .= "</div>\n";
	}
	return $panneau;
}


// http://doc.spip.org/@souligner_match_ortho
function souligner_match_ortho(&$texte, $cherche, $remplace) {
	// Eviter les &mdash;, etc.
	if ($cherche{0} == '&' AND $cherche{strlen($cherche) - 1} == ';') return;

	if ($cherche{0} == '>') 
		$texte = str_replace($cherche, $remplace, $texte);
	else {
		// Ne pas remplacer a l'interieur des tags HTML
		$table = explode($cherche, $texte);
		unset($avant);
		$texte = '';
		foreach ($table as $s) {
			if (!isset($avant)) {
				$avant = $s;
				continue;
			}
			$ok = true;
			$texte .= $avant;
			// Detecter si le match a eu lieu dans un tag HTML
			if (is_int($deb_tag = strrpos($texte, '<'))) {
				if (strrpos($texte, '>') <= $deb_tag)
					$ok = false;
			}
			if ($ok) $texte .= $remplace;
			else $texte .= $cherche;
			$avant = $s;
		}
		$texte .= $avant;
	}
}

// http://doc.spip.org/@souligner_ortho
function souligner_ortho($texte, $lang, $ortho_result) {
	global $id_suggest;
	$vu = array();

	$mauvais = $ortho_result['mauvais'];
	$bons = $ortho_result['bons'];

	// Neutraliser l'apostrophe unicode pour surligner correctement les fautes
	$texte = " ".str_replace("\xE2\x80\x99", "'", $texte)." ";
	// Chercher et remplacer les mots un par un
	$delim = '[^-\''.pcre_lettres_unicode().']';
	foreach ($mauvais as $mot => $suggest) {
		$pattern = ",$delim".$mot."$delim,us";
		// Recuperer les occurences du mot dans le texte
		if (preg_match_all($pattern, $texte, $regs, PREG_SET_ORDER)) {
			$id = $id_suggest[$mot];
			$mot_html = afficher_ortho($mot);
			foreach ($regs as $r) {
				if ($vu[$cherche = $r[0]]) continue;
				$vu[$cherche] = 1;
				$html = "<a class='ortho' onclick=\"suggest($id);return false;\" href=''>$mot_html</a>";
				$remplace = str_replace($mot, $html, $cherche);
				souligner_match_ortho($texte, $cherche, $remplace);
			}
		}
	}
	foreach ($bons as $mot) {
		$pattern = ",$delim".$mot."$delim,us";
		// Recuperer les occurences du mot dans le texte
		if (preg_match_all($pattern, $texte, $regs, PREG_SET_ORDER)) {
			$id = $id_suggest[$mot];
			$mot_html = afficher_ortho($mot);
			foreach ($regs as $r) {
				if ($vu[$cherche = $r[0]]) continue;
				$vu[$cherche] = 1;
				$html = "<a class='ortho-dico' onclick=\"suggest($id);return false;\" href=''>$mot_html</a>";
				$remplace = str_replace($mot, $html, $cherche);
				souligner_match_ortho($texte, $cherche, $remplace);
			}
		}
	}
	
	$texte = preg_replace(',(^ | $),', '', $texte);
	return $texte;
}

// http://doc.spip.org/@init_ortho
function init_ortho() {
	global $duree_cache_ortho, $duree_cache_miroirs_ortho;
 
 	$duree_cache_ortho = 7 * 24 * 3600;
	$duree_cache_miroirs_ortho = 24 * 3600;
	lire_miroirs_ortho();
}

init_ortho();

?>
