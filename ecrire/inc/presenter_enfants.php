<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/autoriser');
include_spip('inc/presentation');

// http://doc.spip.org/@enfant_rub
function enfant_rub($collection){
	global $spip_display, $spip_lang_left, $spip_lang_right, $spip_lang;

	$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND isset($GLOBALS['meta']['image_process']) AND $GLOBALS['meta']['image_process'] != "non");

	if ($voir_logo) {
		$voir_logo = "float: $spip_lang_right; margin-$spip_lang_right: 0px; margin-top: 0px;";
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
	} else $logo ='';

	$res = "";

	$result = sql_select("id_rubrique, id_parent, titre, descriptif, lang ", "spip_rubriques", "id_parent=$collection",'', '0+titre,titre');

	while($row=sql_fetch($result)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=$row['titre'];

		if (autoriser('voir','rubrique',$id_rubrique)){

			$les_sous_enfants = sous_enfant_rub($id_rubrique);

			changer_typo($row['lang']);
			$lang_dir = lang_dir($row['lang']);
			$descriptif=propre($row['descriptif']);

			if ($voir_logo) {
				if ($logo = $chercher_logo($id_rubrique, 'id_rubrique', 'on')) {
					list($fid, $dir, $nom, $format) = $logo;
					include_spip('inc/filtres_images_mini');
					$logo = image_reduire("<img src='$fid' alt='' />", 48, 36);
					if ($logo)
						$logo =  "\n<div style='$voir_logo'>$logo</div>";
				}
			}

			$lib_bouton = (!acces_restreint_rubrique($id_rubrique) ? "" :
			   http_img_pack("admin-12.gif", '', " width='12' height='12'", _T('image_administrer_rubrique'))) .
			  " <span dir='$lang_dir'><a href='" .
			  generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") .
			  "'>".
			  typo($titre) .
			  "</a></span>";

			  $titre = (is_string($logo) ? $logo : '') .
				  bouton_block_depliable($lib_bouton,$les_sous_enfants ?false:-1,"enfants$id_rubrique");

			$les_enfants = "\n<div class='enfants'>" .
			  debut_cadre_sous_rub(($id_parent ? "rubrique-24.gif" : "secteur-24.gif"), true, "", $titre) .
			  (!$descriptif ? '' : "\n<div class='verdana1'>$descriptif</div>") .
			  (($spip_display == 4) ? '' : $les_sous_enfants) .
			  "\n<div style='clear:both;'></div>"  .
			  fin_cadre_sous_rub(true) .
			  "</div>";

			$res .= ($spip_display != 4)
			? $les_enfants
			: "\n<li>$les_enfants</li>";
		}
	}

	changer_typo($spip_lang); # remettre la typo de l'interface pour la suite
	return (($spip_display == 4) ? "\n<ul>$res</ul>\n" :  $res);

}

// http://doc.spip.org/@sous_enfant_rub
function sous_enfant_rub($collection2){
	global $spip_lang_left;

	$result3 =  sql_select("id_rubrique, id_parent, titre, lang", "spip_rubriques", "id_parent=$collection2",'', '0+titre,titre');

	$retour = '';
	while($row=sql_fetch($result3)){
		$id_rubrique2=$row['id_rubrique'];
		$id_parent2=$row['id_parent'];
		$titre2=$row['titre'];
		changer_typo($row['lang']);
		$lang_dir = lang_dir($row['lang']);
		if (autoriser('voir','rubrique',$id_rubrique2))
			$retour.="\n<li class='arial11 rubrique_12' dir='$lang_dir'><a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique2") . "'>".typo($titre2)."</a></li>\n";
	}

	if (!$retour) return '';

	return debut_block_depliable(false,"enfants$collection2")
	."\n<ul style='margin: 0px; padding: 0px; padding-top: 3px;'>\n"
	. $retour
	. "</ul>\n\n".fin_block()."\n\n";
}

// http://doc.spip.org/@afficher_enfant_rub
function afficher_enfant_rub($id_rubrique, $bouton=false, $return=false) {
	global  $spip_lang_left,$spip_lang_right, $spip_display;

	$les_enfants = enfant_rub($id_rubrique);
	$n = strlen(trim($les_enfants));

	if (!$n && !$bouton) return "";

	if (!($x = strpos($les_enfants,"\n<div class='enfants'>",round($n/2)))) {
		$les_enfants2="";
	}else{
		$les_enfants2 = substr($les_enfants, $x);
		$les_enfants = substr($les_enfants,0,$x);
		if ($spip_display == 4) {
		  $les_enfants .= '</li></ul>';
		  $les_enfants2 = '<ul><li>' . $les_enfants2;
		}
	}

	$res =
	"<div class='gauche'>"
	. $les_enfants
	. "</div>"
	. "<div class='droite'>"
	. $les_enfants2
	. "</div>"
	. "&nbsp;"
	. "<div style='float:"
	. $spip_lang_right
	. ";position:relative;'>"
	. (!$bouton ? ''
		 : (!$id_rubrique
		    ? icone(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav"), "secteur-24.gif", "creer.gif",$spip_lang_right, false)
		    : icone(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav&id_parent=$id_rubrique"), "rubrique-24.gif", "creer.gif",$spip_lang_right,false)))
	. "</div>";

	if ($return) return $res; else echo_log('afficher_enfant_rub',$res);
}

?>
