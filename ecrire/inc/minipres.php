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

include_spip('inc/lang');
include_spip('inc/texte');

//
// Presentation des pages d'installation et d'erreurs
//

// http://doc.spip.org/@install_debut_html
function install_debut_html($titre = 'AUTO', $onLoad = '') {
	global $spip_lang_right,$spip_lang_left;
	
	include_spip('inc/filtres');
	include_spip('inc/headers');
	utiliser_langue_visiteur();

	http_no_cache();

	if ($titre=='AUTO')
		$titre=_T('info_installation_systeme_publication');

	# le charset est en utf-8, pour recuperer le nom comme il faut
	# lors de l'installation
	if (!headers_sent())
		header('Content-Type: text/html; charset=utf-8');
	$dir_img_pack = _DIR_IMG_PACK;
	
	return  _DOCTYPE_ECRIRE.
		html_lang_attributes().
		"<head>\n".
		"<title>".
		textebrut($titre).
		"</title>
		<link rel='stylesheet' href='".find_in_path('minipres.css')."' type='text/css' media='all' />
		<script type='text/javascript' src='" . _DIR_JAVASCRIPT . "spip_barre.js'></script>\n". // cet appel permet d'assurer un copier-coller du nom du repertoire a creer dans tmp (esj)
#	"<script type='text/javascript' src='" . _DIR_JAVASCRIPT . "jquery.js'></script>".
"</head>
<body".$onLoad.">
	<div id='minipres'>
	<h1>".
	  $titre .
	  "</h1>
	<div>\n";
}

// http://doc.spip.org/@install_fin_html
function install_fin_html() {
	return "\n\t</div>\n\t</div>\n</body>\n</html>";
}

// http://doc.spip.org/@fieldset
function fieldset($legend, $champs = array(), $horchamps='') {
	$fieldset = "<fieldset>\n" .
	($legend ? "<legend>".$legend."</legend>\n" : '');
	foreach ($champs as $nom => $contenu) {
		$type = $contenu['hidden'] ? 'hidden' : (preg_match(',^pass,', $nom) ? 'password' : 'text');
		$class = $contenu['hidden'] ? '' : "class='formo' size='40' ";
		if(is_array($contenu['alternatives'])) {
			$fieldset .= $contenu['label'] ."\n";
			foreach($contenu['alternatives'] as $valeur => $label) {
				$fieldset .= "<input type='radio' name='".$nom .
				"' id='$nom-$valeur' value='$valeur'"
				  .(($valeur==$contenu['valeur'])?"\nchecked='checked'":'')."/>\n";
				$fieldset .= "<label for='$nom-$valeur'>".$label."</label>\n";
			}
			$fieldset .= "<br />\n";
		}
		else {
			$fieldset .= "<label for='".$nom."'>".$contenu['label']."</label>\n";
			$fieldset .= "<input ".$class."type='".$type."' id='" . $nom . "' name='".$nom."'\nvalue='".$contenu['valeur']."' />\n";
		}
	}
	$fieldset .= "$horchamps</fieldset>\n";
	return $fieldset;
}

// http://doc.spip.org/@bouton_suivant
function bouton_suivant($code = '') {
	if($code=='') $code = _T('bouton_suivant');
	static $suivant = 0;
	$id = 'suivant'.(($suivant>0)?strval($suivant):'');
	$suivant +=1;
	return "\n<span class='suivant'><input id='".$id."' type='submit' class='fondl'\nvalue=\"" .
		$code .
		" >>\" /></span>\n";
}

// http://doc.spip.org/@minipres
function minipres($titre='', $corps="", $onload='')
{
	if (!$titre) {
		$titre = _T('info_acces_interdit');
		$corps = _request(_DIR_RESTREINT ? 'action' : 'exec');
		spip_log($GLOBALS['auteur_session']['nom'] . " $titre " . $_SERVER['REQUEST_URI']);
	}

	return install_debut_html($titre, $onload)
	. $corps
	. install_fin_html();
}

//
// Mention de la revision SVN courante de l'espace restreint standard
// (numero non garanti pour l'espace public et en cas de mutualisation)
// on est negatif si on est sur .svn, et positif si on utilise svn.revision
// http://doc.spip.org/@version_svn_courante
function version_svn_courante($dir) {
	if (!$dir) $dir = '.';

	// version installee par paquet ZIP
	if (lire_fichier($dir.'/svn.revision', $c)
	AND preg_match(',Revision: (\d+),', $c, $d))
		return intval($d[1]);

	// version installee par SVN
	if (lire_fichier($dir . '/.svn/entries', $c)
	AND (
	(preg_match_all(
	',committed-rev="([0-9]+)",', $c, $r1, PREG_PATTERN_ORDER)
	AND $v = max($r1[1])
	)
	OR
	(preg_match(',^8.*dir[\r\n]+(\d+),ms', $c, $r1) # svn >= 1.4
	AND $v = $r1[1]
	)))
		return -$v;

	// Bug ou paquet fait main
	return 0;
}

// http://doc.spip.org/@info_copyright
function info_copyright() {
	global $spip_version_affichee, $spip_lang;

	$version = $spip_version_affichee;

	//
	// Mention, le cas echeant, de la revision SVN courante
	//
	if ($svn_revision = version_svn_courante(_DIR_RACINE)) {
		$version .= ' ' . (($svn_revision < 0) ? 'SVN ':'')
		. "[<a href='http://trac.rezo.net/trac/spip/changeset/"
		. abs($svn_revision) . "' onclick=\"window.open(this.href); return false;\">"
		. abs($svn_revision) . "</a>]";
	}

	return _T('info_copyright', 
		   array('spip' => "<b>SPIP $version</b> ",
			 'lien_gpl' => 
			 "<a href='". generer_url_ecrire("aide_index", "aide=licence&var_lang=$spip_lang") . "' onclick=\"window.open(this.href, 'spip_aide', 'scrollbars=yes,resizable=yes,width=740,height=580'); return false;\">" . _T('info_copyright_gpl')."</a>"));

}

// normalement il faudrait creer exec/info.php, mais pour mettre juste ca:

// http://doc.spip.org/@exec_info_dist
function exec_info_dist() {
	global $connect_statut;
	if ($connect_statut == '0minirezo') phpinfo();
}

// Idem faudrait creer exec/test_ajax, mais c'est si court.
// Tester si Ajax fonctionne pour ce brouteur
// (si on arrive la c'est que c'est bon, donc poser le cookie)

// http://doc.spip.org/@exec_test_ajax_dist
function exec_test_ajax_dist() {
	switch (_request('js')) {
		// on est appele par <noscript>
		case -1:
			spip_setcookie('spip_accepte_ajax', -1);
			include_spip('inc/headers');
			redirige_par_entete(_DIR_IMG_PACK.'puce-orange-anim.gif');
			break;

		// ou par ajax
		case 1:
		default:
			spip_setcookie('spip_accepte_ajax', 1);
			break;
	}
}


// Fabrique une balise A, avec tous les attributs possibles
// attention au cas ou la href est du Javascript avec des "'"
// pour un href conforme au validateur W3C, faire & --> &amp; avant

// http://doc.spip.org/@http_href
function http_href($href, $clic, $title='', $style='', $class='', $evt='') {
	return '<a href="' .
		$href .
		'"' .
		(!$title ? '' : ("\ntitle=\"" . supprimer_tags($title)."\"")) .
		(!$style ? '' : ("\nstyle=\"" . $style . "\"")) .
		(!$class ? '' : ("\nclass=\"" . $class . "\"")) .
		($evt ? "\n$evt" : '') .
		'>' .
		$clic .
		'</a>';
}

// produit une balise img avec un champ alt d'office si vide
// attention le htmlentities et la traduction doivent etre appliques avant.

// http://doc.spip.org/@http_wrapper
function http_wrapper($img){
	static $wrapper_state=NULL;
	static $wrapper_table = array();
	
	if (strpos($img,'/')===FALSE) // on ne prefixe par _DIR_IMG_PACK que si c'est un nom de fichier sans chemin
		$f = _DIR_IMG_PACK . $img;
	else { // sinon, le path a ete fourni
		$f = $img;
		// gerer quand meme le cas des hacks pre 1.9.2 ou l'on faisait un path relatif depuis img_pack
		if (substr($f,0,strlen("../"._DIR_PLUGINS))=="../"._DIR_PLUGINS)
			$f = substr($img,3); // on enleve le ../ qui ne faisait que ramener au rep courant
	}
	
	if ($wrapper_state==NULL){
		global $browser_name;
		if (!strlen($browser_name)){include_spip('inc/layer');}
		$wrapper_state = ($browser_name=="MSIE");
	}
	if ($wrapper_state){
		if (!isset($wrapper_table[$d=dirname($f)])) {
			$wrapper_table[$d] = false;
			if (file_exists("$d/wrapper.php"))
				$wrapper_table[$d] = "$d/wrapper.php?file=";
		}
		if ($wrapper_table[$d])
			$f = $wrapper_table[$d] . urlencode(basename($img));
	}
	return $f;
}
// http://doc.spip.org/@http_img_pack
function http_img_pack($img, $alt, $att, $title='') {

	return  "<img src='" . http_wrapper($img)
	  . ("'\nalt=\"" .
	     str_replace('"','', textebrut($alt ? $alt : ($title ? $title : '')))
	     . '" ')
	  . ($title ? "title=\"$title\" " : '')
	  . $att
	  . " />";
}

// http://doc.spip.org/@http_style_background
function http_style_background($img, $att='')
{
  return " style='background: url(\"".http_wrapper($img)."\")" .
	    ($att ? (' ' . $att) : '') . ";'";
}
function info_progression_etape($en_cours,$phase,$dir){
	//$en_cours = _request('etape')?_request('etape'):"";
	$liste = find_all_in_path($dir,$phase.'(([0-9])+|fin)[.]php');
	$debut = 1; $etat = "ok";
	$last = count($liste);
	
	$aff_etapes = "<span id='etapes'>";
	foreach($liste as $etape=>$fichier){
		if ($etape=="$phase$en_cours.php"){
			$etat = "encours";
		}
		$aff_etapes .= "<span class='$etat'><em>".(($debut<$last)?$debut:"go")."</em><span>"
		 .(($debut<$last)?", ":" !")."</span></span>";
		if ($etat == "encours")
			$etat = 'todo';
		$debut++;
	}
	$aff_etapes .= "<br class='nettoyeur' />&nbsp;</span>\n";
	return $aff_etapes;
}
?>
