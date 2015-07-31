<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/filtres');

// L'aide en ligne de SPIP est disponible sous forme d'articles de www.spip.net
// qui ont des reperes nommes arrtitre, artdesc etc.
// La fonction inc_aider(_dist) recoit soit ces reperes, 
// soit le nom du champ de saisie, le nom du squelette le contenant et enfin
// l'environnement d'execution du squelette (inutilise pour le moment).
// Le tableau ci-dessous donne le repere correspondant a ces informations.

$GLOBALS['aider_index'] = array(
	'editer_article.html' => array (
		'surtitre' => 'arttitre',
		'titre' => 'arttitre',
		'soustitre' => 'arttitre',
		'id_parent' => 'artrub',
		'descriptif' => 'artdesc',
		'virtuel' => 'artvirt',
		'chapo' => 'arttitre',
		'text_area' => 'arttexte'),

	'editer_breve.html' => array(
		'id_parent' => 'brevesrub',
		'lien_titre' => 'breveslien',
		'statut' => 'brevesstatut'),

	'editer_groupe_mot.html' => array(
		'titre' => 'motsgroupes'),

	'editer_mot.html' => array(
		'titre' => 'mots',
		'id_groupe' => 'motsgroupes'),

	'editer_rubrique.html' => array(
		'titre' => 'arttitre',
		'id_parent' => 'rubrub',
		'text_area' => 'raccourcis')

				);



/**
 * Generer un lien d'aide (icone + lien)
 *
 * @param string $aide
 * 		cle d'identification de l'aide souhaitee
 * @param strink $skel
 * 		Nom du squelette qui appelle ce bouton d'aide
 * @param array $env
 * 		Environnement du squelette
 * @param bool $aide_spip_directe
 * 		false : Le lien genere est relatif a notre site (par defaut)
 * 		true : Le lien est realise sur spip.net/aide/ directement ...
 * @return 
**/
function inc_aider_dist($aide='', $skel='', $env=array(), $aide_spip_directe = false) {
	global $spip_lang, $aider_index;

	if (($skel = basename($skel))
	AND isset($aider_index[$skel])
	AND isset($aider_index[$skel][$aide]))
		$aide = $aider_index[$skel][$aide];

	if ($aide_spip_directe) {
		// on suppose que spip.net est le premier present
		// dans la liste des serveurs. C'est forcement le cas
		// a l'installation tout du moins
		$help_server = $GLOBALS['help_server'];
		$url = array_shift($help_server) . '/';
		$url = parametre_url($url, 'exec', 'aide');
		$url = parametre_url($url, 'aide', $aide);
		$url = parametre_url($url, 'var_lang', $spip_lang);
	} else {
		$args = "aide=$aide&var_lang=$spip_lang";
		$url = generer_url_ecrire("aide", $args);
	}
	
	return aider_icone($url);
}

function aider_icone($url, $clic= '')
{
	global $spip_lang, $spip_lang_rtl;

	if (!$clic) {
		$t = _T('titre_image_aide');
		$clic = http_img_pack("aide".aide_lang_dir($spip_lang,$spip_lang_rtl)."-16.png",
			_T('info_image_aide'),
			" title=\"$t\" class='aide'");
	}
	return "\n&nbsp;&nbsp;<a class='aide popin'\nhref='"
	.  $url
	. "' target='_blank'>"
	. $clic
	. "</a>";
}

// en hebreu le ? ne doit pas etre inverse
// http://doc.spip.org/@aide_lang_dir
function aide_lang_dir($spip_lang,$spip_lang_rtl) {
	return ($spip_lang<>'he') ? $spip_lang_rtl : '';
}

// Les sections d'un fichier aide sont reperees ainsi:
define('_SECTIONS_AIDE', ',<h([12])(?:\s+class="spip")?'. '>([^/]+?)(?:/(.+?))?</h\1>,ism');

function aide_fichier($path, $help_server) {

	$md5 = md5(serialize($help_server));
	$fichier_aide = _DIR_AIDE . substr($md5,0,16) . "-" . $path;
	$lastm = @filemtime($fichier_aide);
	$lastversion = @filemtime(_DIR_RESTREINT . 'inc_version.php');
	$here = @(is_readable($fichier_aide) AND ($lastm >= $lastversion));
	$contenu = '';

	if ($here) {
		lire_fichier($fichier_aide, $contenu);
		return array($contenu, $lastm);
	}

	// mettre en cache (tant pis si echec)
	sous_repertoire(_DIR_AIDE,'','',true);
	$contenu = array();
	include_spip('inc/distant');
	foreach ($help_server as $k => $server) {
		// Remplacer les liens aux images par leur gestionnaire de cache
		$url = "$server/$path";
		$local = _DIR_AIDE.substr(md5($url),0,8)."-".preg_replace(",[^\w.]+,i","_",$url);
		$local = _DIR_RACINE . copie_locale($url, 'modif', $local);

		lire_fichier($local,$page);
		$page = aide_fixe_img($page,$server);
		// les liens internes ne doivent pas etre deguises en externes
		$url = parse_url($url);
		$re = '@(<a\b[^>]*\s+href=["\'])' .
		  '(?:' . $url['scheme'] . '://' . $url['host'] . ')?' .
		  $url['path'] . '([^"\']*)@ims';
		$page = preg_replace($re,'\\1\\2', $page);

		preg_match_all(_SECTIONS_AIDE, $page, $sections, PREG_SET_ORDER);
		// Fusionner les aides ayant meme nom de section
		$vus = array();
		foreach ($sections as $section) {
			list($tout,$prof, $sujet,) = $section;
			if (in_array($sujet, $vus)) continue;
			$corps = aide_section($sujet, $page, $prof);
			foreach ($contenu as $k => $s) {
			  if ($sujet == $k) {
			    // Section deja vue qu'il faut completer
			    // Si le complement a des sous-sections,
			    // ne pas en tenir compte quand on les rencontrera
			    // lors des prochains passages dans la boucle
			    preg_match_all(_SECTIONS_AIDE, $corps, $s, PREG_PATTERN_ORDER);
			    if ($s) {$vus = array_merge($vus, $s[2]);}
			    $contenu[$k] .= $corps;
			    $corps = '';
			    break;
			  }
			}
			// Si totalement nouveau, inserer le titre
			// mais pas le corps s'il contient des sous-sections:
			// elles vont venir dans les passages suivants
			if ($corps) {
			  $corps = aide_section($sujet, $page);
			  $contenu[$sujet] = $tout . "\n" . $corps;
			}
		}
	}

	$contenu = '<div>' . join('',$contenu) . '</div>';

	// Renvoyer les liens vraiment externes dans une autre fenetre
	$contenu = preg_replace('@<a href="(http://[^"]+)"([^>]*)>@',
				'<a href="\\1"\\2 target="_blank">',
				$contenu);

	// Correction typo dans la langue demandee
	#changer_typo($lang_aide);
	$contenu = '<body>' . $contenu . '</body>';

	if (strlen($contenu) <= 100) return array(false, false);
	ecrire_fichier ($fichier_aide, $contenu);
	return array($contenu, time());
}

function generer_url_aide_img($args){
	return generer_url_action('aide_img', $args, false, true);
}


// Les aides non mises a jour ont un vieux Path a remplacer
// (mais ce serait bien de le faire en SQL une bonne fois)
define('_REPLACE_IMG_PACK', "@(<img([^<>]* +)?\s*src=['\"])img_pack\/@ims");

// Remplacer les URL des images par l'URL du gestionnaire de cache local
function aide_fixe_img($contenu, $server){
	$html = "";
	$re = "@(<img([^<>]* +)?\s*src=['\"])((AIDE|IMG|local)/([-_a-zA-Z0-9]*/?)([^'\"<>]*))@imsS";
	while (preg_match($re, $contenu, $r)) {
		$p = strpos($contenu, $r[0]);
		$i = $server . '/' . $r[3];
		$html .= substr($contenu, 0, $p) .  $r[1] . $i;
		$contenu = substr($contenu, $p + strlen($r[0]));
	}
	$html .= $contenu;

	// traiter les vieilles doc
	return  preg_replace(_REPLACE_IMG_PACK,"\\1"._DIR_IMG_PACK, $html);
}


// Extraire la seule section demandee,
// qui commence par son nom entouree d'une balise h2
// et se termine par la prochaine balise h2 ou h1 ou le /body final.

function aide_section($aide, $contenu, $prof=2){
	$maxprof = ($prof >=2) ? "12" : "1";
	$r = "@<h$prof" . '(?: class="spip")?' . '>\s*' . $aide
	  ."\s*(?:/.+?)?</h$prof>(.*?)<(?:(?:h[$maxprof])|/body)@ism";

	if (preg_match($r, $contenu, $m))
	  return $m[1];
#	spip_log("aide inconnue $r dans " . substr($contenu, 0, 150));
	return '';
}


?>
