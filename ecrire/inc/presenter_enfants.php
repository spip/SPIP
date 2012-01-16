<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
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
	$voir_logo = (isset($GLOBALS['meta']['image_process']) AND $GLOBALS['meta']['image_process'] != "non");
	$logo = "";

	if ($voir_logo) {
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
		include_spip('inc/filtres_images_mini');
	}

	$res = array();
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
					$logo = image_reduire("<img src='$fid' alt='' />", 48, 36);
					if ($logo)
						$logo =  inserer_attribut($logo,'class','logo');
				}
			}

			$lib_bouton = (!acces_restreint_rubrique($id_rubrique) ? "" :
			   http_img_pack('auteur-0minirezo-16.png', '', " width='16' height='16'", _T('image_administrer_rubrique'))) .
			  " <a dir='$lang_dir' href='" .
			  generer_url_entite($id_rubrique,'rubrique') .
			  "'>".
			  typo($titre) .
			  "</a>";

			$titre = (is_string($logo) ? $logo : '') .
				bouton_block_depliable($lib_bouton,$les_sous_enfants ?false:-1,"enfants$id_rubrique");

			$res[] =
			  debut_cadre_sous_rub(($id_parent ? "rubrique-24.png" : "secteur-24.png"), true, "", $titre) .
			  (!$descriptif ? '' : "\n<div class='descriptif'>$descriptif</div>") .
			  $les_sous_enfants .
			  fin_cadre_sous_rub(true);
		}
	}

	changer_typo($GLOBALS['spip_lang']); # remettre la typo de l'interface pour la suite
	return $res;
}

// http://doc.spip.org/@sous_enfant_rub
function sous_enfant_rub($collection2){
	$result =  sql_select("id_rubrique, id_parent, titre, lang", "spip_rubriques", "id_parent=$collection2",'', '0+titre,titre');

	$retour = '';
	while($row=sql_fetch($result)){
		$id_rubrique2=$row['id_rubrique'];
		$id_parent2=$row['id_parent'];
		$titre2=$row['titre'];
		changer_typo($row['lang']);
		$lang_dir = lang_dir($row['lang']);
		if (autoriser('voir','rubrique',$id_rubrique2))
			$retour.="\n<li class='item' dir='$lang_dir'><a href='" . generer_url_entite($id_rubrique2,'rubrique') . "'>".typo($titre2)."</a></li>\n";
	}

	if (!$retour) return '';

	return debut_block_depliable(false,"enfants$collection2")
	."\n<ul class='liste-items sous-sous-rub'>\n"
	. $retour
	. "</ul>\n".fin_block()."\n\n";
}

// http://doc.spip.org/@afficher_enfant_rub
function afficher_enfant_rub($id_rubrique=0) {
	$les_enfants = enfant_rub($id_rubrique);

	if (!$n = count($les_enfants)) return "";

	if ($n==1) {
		$les_enfants=reset($les_enfants);
		$les_enfants2="";
	}
	else{
		$n = ceil($n/2);
		$les_enfants2 = implode('',array_slice($les_enfants,$n));
		$les_enfants = implode('',array_slice($les_enfants,0,$n));
	}

	$res =
	"<div class='gauche'>"
	. $les_enfants
	. "</div>"
	. "<div class='droite'>"
	. $les_enfants2
	. "</div>";

	return $res;
}

?>
