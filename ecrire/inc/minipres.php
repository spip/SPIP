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

include_spip('inc/lang');
utiliser_langue_visiteur();

//
// Presentation des pages d'installation et d'erreurs
//

function install_debut_html($titre = 'AUTO') {

	if ($titre=='AUTO')
		$titre=_T('info_installation_systeme_publication');

	include_spip('inc/headers');

	http_no_cache();

	# le charset est en utf-8, pour recuperer le nom comme il faut
	# lors de l'installation
	if (!headers_sent())
		header('Content-Type: text/html; charset=utf-8');

	echo  _DOCTYPE_ECRIRE ,
	  "<html lang='",$GLOBALS['spip_lang'],
	  "' dir='",($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr'),"'>\n" ,
	  "<head>\n",
	  "<title>",
	  $titre,
	  "</title>
	  <style type='text/css'><!--\n/*<![CDATA[*/\n\n\n",
	  "a {text-decoration: none; }",
	  "\n\n]]>\n--></style>\n\n
</head>
<body bgcolor='#FFFFFF' text='#000000' link='#E86519' vlink='#6E003A' alink='#FF9900'>
<center><table style='margin-top:50px; width: 450px'>
<tr><th style='color: #970038;text-align: left;font-family: Verdana; font-weigth: bold; font-size: 18px'>",
	  $titre ,
	  "</th></tr>
<tr><td  class='serif'>";
}

function install_fin_html() {

	echo '</td></tr></table></body></html>';
}

function minipres($titre, $corps="")
{
	install_debut_html($titre);
	echo $corps;
	install_fin_html();
	exit;
}

//
// Aide
//

// en hebreu le ? ne doit pas etre inverse
function aide_lang_dir($spip_lang,$spip_lang_rtl) {
	return ($spip_lang<>'he') ? $spip_lang_rtl : '';
}

function aide($aide='') {
	global $spip_lang, $spip_lang_rtl, $spip_display;

	if (!$aide OR $spip_display == 4) return;

	return "&nbsp;&nbsp;<a class='aide' href='" . generer_url_ecrire("aide_index", "aide=$aide&var_lang=$spip_lang")
		. "' target=\"spip_aide\" "
		. "onclick=\"javascript:window.open(this.href,"
		. "'spip_aide', 'scrollbars=yes, resizable=yes, width=740, "
		. "height=580'); return false;\">"
		. http_img_pack("aide".aide_lang_dir($spip_lang,$spip_lang_rtl).".gif",
			_T('info_image_aide'), "title=\""._T('titre_image_aide')
			. "\" width=\"12\" height=\"12\" border=\"0\" align=\"middle\"")
		. "</a>";
}

//
// Mention de la revision SVN courante de l'espace restreint standard
// (numero non garanti pour l'espace public et en cas de mutualisation)
function version_svn_courante($dir) {
	if (!$dir) $dir = '.';
	if (!lire_fichier($dir . '/.svn/entries', $c)) return 0;
	preg_match_all(',committed-rev="([0-9]+)",', $c, $r1, PREG_PATTERN_ORDER);
	preg_match_all(',name="([^"]+)[^>]*kind="dir",',$c, $r2, PREG_PATTERN_ORDER);
	return max(max($r1[1]), @max(array_map('version_svn_courante', $r2[1])));
}

function info_copyright() {
	global $spip_version_affichee, $spip_lang;

	$version = $spip_version_affichee;

	//
	// Mention, le cas echeant, de la revision SVN courante
	//
	if ($svn_revision = version_svn_courante(_DIR_INCLUDE))
		$version .= " SVN [<a href='http://trac.rezo.net/trac/spip/changeset/$svn_revision' target='_blank'>$svn_revision</a>]";

	echo _T('info_copyright', 
		   array('spip' => "<b>SPIP $version</b> ",
			 'lien_gpl' => 
			 "<a href='". generer_url_ecrire("aide_index", "aide=licence&var_lang=$spip_lang") . "' target='spip_aide' onClick=\"javascript:window.open(this.href, 'aide_spip', 'scrollbars=yes,resizable=yes,width=740,height=580'); return false;\">" . _T('info_copyright_gpl')."</a>"));

}

// normalement il faudrait definir inc_info.php, mais pour mettre juste ca:

function exec_info_dist() {
	global $connect_statut;
	if ($connect_statut == '0minirezo') phpinfo();
}

// Afficher le bouton "preview" dans l'espace public
function afficher_bouton_preview() {
		$x = _T('previsualisation');
		return '<div style="
		display: block;
		color: #eeeeee;
		background-color: #111111;
		padding-right: 5px;
		padding-top: 2px;
		padding-bottom: 5px;
		font-size: 20px;
		top: 0px;
		left: 0px;
		position: absolute;
		">' 
		. http_img_pack('naviguer-site.png', $x, '')
		. '&nbsp;' . majuscules($x) . '</div>';
}

// Fabrique une balise A, avec tous les attributs possibles
// attention au cas ou la href est du Javascript avec des "'"
// pour un href conforme au validateur W3C, faire & --> &amp; avant

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

function http_img_pack($img, $alt, $att, $title='') {
	return "<img src='" . _DIR_IMG_PACK . $img
	  . ("'\nalt=\"" .
	     ($alt ? $alt : ($title ? $title : ereg_replace('\..*$','',$img)))
	     . '" ')
	  . ($title ? " title=\"$title\"" : '')
	  . $att . " />";
}

function http_href_img($href, $img, $att, $title='', $style='', $class='', $evt='') {
	return  http_href($href, http_img_pack($img, $title, $att), $title, $style, $class, $evt);
}


function http_style_background($img, $att='')
{
  return " style='background: url(\"" . _DIR_IMG_PACK . $img .  '")' .
    ($att ? (' ' . $att) : '') . ";'";
}

// Pour les formulaires en methode POST,
// mettre les arguments a la fois en input-hidden et dans le champ action:
// 1) on peut ainsi memoriser le signet comme si c'etait un GET
// 2) ca suit http://en.wikipedia.org/wiki/Representational_State_Transfer

// Attention: generer_url_ecrire peut rajouter des args

function generer_url_post_ecrire($script, $args='', $name='', $ancre='', $onchange='') {
	include_spip('inc/filtres');
	$action = generer_url_ecrire($script, $args);
	if ($name) $name = " name='$name'";
	return "\n<form action='$action$ancre'$name method='post'$onchange>"
	.form_hidden($action);
}

?>
