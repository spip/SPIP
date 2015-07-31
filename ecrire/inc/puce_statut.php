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

// http://doc.spip.org/@inc_puce_statut_dist
function inc_puce_statut_dist($id_objet, $statut, $id_rubrique, $type, $ajax=false) {
	if (function_exists($f = "puce_statut_$type")
	OR function_exists($f = "puce_statut_${type}_dist"))
		return $f($id_objet, $statut, $id_rubrique, $type, $ajax);
	else
		return "<img src='" . chemin_image("$type-24.gif") . "' alt='' />";
}

// http://doc.spip.org/@puce_statut_document_dist
function puce_statut_document_dist($id, $statut, $id_rubrique, $type, $ajax='') {
	return "<img src='" . chemin_image("attachment.gif") . "' alt=''  />";
}

// http://doc.spip.org/@puce_statut_auteur_dist
// Hack de compatibilite: les appels directs ont un  $type != 'auteur'
// si l'auteur ne peut pas se connecter
// http://doc.spip.org/@puce_statut_auteur_dist
function puce_statut_auteur_dist($id, $statut, $id_rubrique, $type, $ajax='') {

	static $titre_des_statuts ='';
	static $images_des_statuts ='';

	// eviter de retraduire a chaque appel
	if (!$titre_des_statuts) {
	  $titre_des_statuts = array(
		"info_administrateurs" => _T('titre_image_administrateur'),
		"info_redacteurs" => _T('titre_image_redacteur_02'),
		"info_visiteurs" =>  _T('titre_image_visiteur'),
		"info_statut_site_4" => _T('titre_image_auteur_supprime')
		);

	  $images_des_statuts = array(
			   "info_administrateurs" => 'admin-12.gif',
			   "info_redacteurs" =>'redac-12.gif',
			   "info_visiteurs" => 'visit-12.gif',
			   "info_statut_site_4" => 'poubelle.gif'
			   );
	}

	if ($statut == 'nouveau') return '';

	$index = array_search($statut, $GLOBALS['liste_des_statuts']);

	if (!$index) $index = 'info_visiteurs';

	$img = $images_des_statuts[$index];
	$alt = $titre_des_statuts[$index];

	if ($type != 'auteur') {
	  $img2 = "croix-rouge.gif";
	  $titre = _T('titre_image_redacteur');
	  $fond = http_style_background($img2, 'top right no-repeat; padding-right: 4px');
	} else {$fond = ''; $titre = $alt;}
	  
	return http_img_pack($img, $alt, $fond, $titre);
}

// http://doc.spip.org/@bonhomme_statut
function bonhomme_statut($row) {
	$puce_statut = charger_fonction('puce_statut', 'inc');
	return $puce_statut(0, $row['statut'], 0, 'auteur');
}


// http://doc.spip.org/@puce_statut_mot_dist
function puce_statut_mot_dist($id, $statut, $id_rubrique, $type, $ajax='') {
	return "<img src='" . chemin_image('petite-cle.gif') . "' alt='' />";
}

// http://doc.spip.org/@puce_statut_rubrique_dist
function puce_statut_rubrique_dist($id, $statut, $id_rubrique, $type, $ajax='') {

	return "<img src='" . chemin_image('rubrique-12.gif') . "' alt='' />";
}

// http://doc.spip.org/@puce_statut_article_dist
function puce_statut_article_dist($id, $statut, $id_rubrique, $type='article', $ajax = false) {
	global $lang_objet;
	
	static $coord = array('publie' => 2,
			      'prepa' => 0,
			      'prop' => 1,
			      'refuse' => 3,
			      'poubelle' => 4);

	$lang_dir = lang_dir($lang_objet);
	if (!$id) {
	  $id = $id_rubrique;
	  $ajax_node ='';
	} else	$ajax_node = " id='imgstatut$type$id'";


	$inser_puce = puce_statut($statut, " width='9' height='9' style='margin: 1px;'$ajax_node");

	if (!autoriser('publierdans', 'rubrique', $id_rubrique)
	OR !_ACTIVER_PUCE_RAPIDE)
		return $inser_puce;

	$titles = array(
			  "blanche" => _T('texte_statut_en_cours_redaction'),
			  "orange" => _T('texte_statut_propose_evaluation'),
			  "verte" => _T('texte_statut_publie'),
			  "rouge" => _T('texte_statut_refuse'),
			  "poubelle" => _T('texte_statut_poubelle'));

	$clip = 1+ (11*$coord[$statut]);

	if ($ajax){
		return 	"<span class='puce_article_fixe'>"
		. $inser_puce
		. "</span>"
		. "<span class='puce_article_popup' id='statutdecal$type$id' style='margin-left: -$clip"."px;'>"
		  . afficher_script_statut($id, $type, -1, 'puce-blanche.gif', 'prepa', $titles['blanche'])
		  . afficher_script_statut($id, $type, -12, 'puce-orange.gif', 'prop', $titles['orange'])
		  . afficher_script_statut($id, $type, -23, 'puce-verte.gif', 'publie', $titles['verte'])
		  . afficher_script_statut($id, $type, -34, 'puce-rouge.gif', 'refuse', $titles['rouge'])
		  . afficher_script_statut($id, $type, -45, 'puce-poubelle.gif', 'poubelle', $titles['poubelle'])
		  . "</span>";
	}

	$nom = "puce_statut_";

	if ((! _SPIP_AJAX) AND $type != 'article') 
	  $over ='';
	else {

	  $action = generer_url_ecrire('puce_statut',"",true);
	  $action = "if (!this.puce_loaded) { this.puce_loaded = true; prepare_selec_statut('$nom', '$type', '$id', '$action'); }";
	  $over = "\nonmouseover=\"$action\"";
	}

	return 	"<span class='puce_article' id='$nom$type$id' dir='$lang_dir'$over>"
	. $inser_puce
	. '</span>';
}


// http://doc.spip.org/@puce_statut_breve_dist
function puce_statut_breve_dist($id, $statut, $id_rubrique, $type, $ajax='') {
	global $lang_objet;
	static $coord = array('publie' => 1,
			      'prop' => 0,
			      'refuse' => 2,
			      'poubelle' => 3);

	$lang_dir = lang_dir($lang_objet);
	$puces = array(
		       0 => 'puce-orange-breve.gif',
		       1 => 'puce-verte-breve.gif',
		       2 => 'puce-rouge-breve.gif',
		       3 => 'puce-blanche-breve.gif');

	switch ($statut) {
		case 'prop':
			$clip = 0;
			$puce = $puces[0];
			$title = _T('titre_breve_proposee');
			break;
		case 'publie':
			$clip = 1;
			$puce = $puces[1];
			$title = _T('titre_breve_publiee');
			break;
		case 'refuse':
			$clip = 2;
			$puce = $puces[2];
			$title = _T('titre_breve_refusee');
			break;
		default:
			$clip = 0;
			$puce = $puces[3];
			$title = '';
	}

	$type1 = "statut$type$id"; 
	$inser_puce = http_img_pack($puce, $title, "id='img$type1' style='margin: 1px;'");

	if (!autoriser('publierdans','rubrique',$id_rubrique)
	OR !_ACTIVER_PUCE_RAPIDE)
		return $inser_puce;

	$titles = array(
			  "blanche" => _T('texte_statut_en_cours_redaction'),
			  "orange" => _T('texte_statut_propose_evaluation'),
			  "verte" => _T('texte_statut_publie'),
			  "rouge" => _T('texte_statut_refuse'),
			  "poubelle" => _T('texte_statut_poubelle'));
			  
	$clip = 1+ (11*$coord[$statut]);

	if ($ajax){
		return 	"<span class='puce_breve_fixe'>"
		. $inser_puce
		. "</span>"
		. "<span class='puce_breve_popup' id='statutdecal$type$id' style='margin-left: -$clip"."px;'>"
		. afficher_script_statut($id, $type, -1, $puces[0], 'prop', $titles['orange'])
		. afficher_script_statut($id, $type, -10, $puces[1], 'publie', $titles['verte'])
	  	. afficher_script_statut($id, $type, -19, $puces[2], 'refuse', $titles['rouge'])
		  . "</span>";
	}

	$nom = "puce_statut_";

	if ((! _SPIP_AJAX) AND $type != 'breve') 
	  $over ='';
	else {

	  $action = generer_url_ecrire('puce_statut',"",true);
	  $action = "if (!this.puce_loaded) { this.puce_loaded = true; prepare_selec_statut('$nom', '$type', '$id', '$action'); }";
	  $over = "\nonmouseover=\"$action\"";
	}

	return 	"<span class='puce_$type' id='$nom$type$id' dir='$lang_dir'$over>"
	. $inser_puce
	. '</span>';

}

// http://doc.spip.org/@puce_statut_site_dist
function puce_statut_site_dist($id, $statut, $id_rubrique, $type, $ajax=''){
	static $coord = array('publie' => 1,
			      'prop' => 0,
			      'refuse' => 2,
			      'poubelle' => 3);
	if ($type=='syndic') $type='site';

	$lang_dir = lang_dir($lang_objet);
	$puces = array(
		       0 => 'puce-orange-breve.gif',
		       1 => 'puce-verte-breve.gif',
		       2 => 'puce-rouge-breve.gif',
		       3 => 'puce-blanche-breve.gif');

	$t = sql_getfetsel("syndication", "spip_syndic", "id_syndic=".sql_quote($id));

	if ($t == 'off' OR $t == 'sus')
		$anim = 'anim';
	else
		$anim = 'breve';
		
	switch ($statut) {
		case 'publie': 
			$puce = 'puce-verte-' . $anim .'.gif';
			$title = _T('info_site_reference');
			break;
		case 'prop':
			$puce = 'puce-orange-' . $anim .'.gif';
			$title = _T('info_site_attente');
			break;
		case 'refuse':
		default:
			$puce = 'puce-poubelle-' . $anim .'.gif';
			$title = _T('info_site_refuse');
			break;
	}
	$type1 = "statut$type$id"; 
	$inser_puce = http_img_pack($puce, $title, "id='img$type1' style='margin: 1px;'");

	if ($anim!='breve' OR !autoriser('publierdans','rubrique',$id_rubrique)
	OR !_ACTIVER_PUCE_RAPIDE)
		return $inser_puce;

	// c'est comme les breves :

	$titles = array(
			  "blanche" => _T('texte_statut_en_cours_redaction'),
			  "orange" => _T('texte_statut_propose_evaluation'),
			  "verte" => _T('texte_statut_publie'),
			  "rouge" => _T('texte_statut_refuse'),
			  "poubelle" => _T('texte_statut_poubelle'));
			  
	$clip = 1+ (11*$coord[$statut]);

	if ($ajax){
		return 	"<span class='puce_site_fixe'>"
		. $inser_puce
		. "</span>"
		. "<span class='puce_site_popup' id='statutdecal$type$id' style='margin-left: -$clip"."px;'>"
		. afficher_script_statut($id, $type, -1, $puces[0], 'prop', $titles['orange'])
		. afficher_script_statut($id, $type, -10, $puces[1], 'publie', $titles['verte'])
	  	. afficher_script_statut($id, $type, -19, $puces[2], 'refuse', $titles['rouge'])
		  . "</span>";
	}

	$nom = "puce_statut_";

	if ((! _SPIP_AJAX)) 
	  $over ='';
	else {
	  $action = generer_url_ecrire('puce_statut',"",true);
	  $action = "if (!this.puce_loaded) { this.puce_loaded = true; prepare_selec_statut('$nom', '$type', '$id', '$action'); }";
	  $over = "\nonmouseover=\"$action\"";
	}

	return 	"<span class='puce_$type' id='$nom$type$id' dir='$lang_dir'$over>"
	. $inser_puce
	. '</span>';
}

// http://doc.spip.org/@puce_statut_syndic_article_dist
function puce_statut_syndic_article_dist($id_syndic, $statut, $id_rubrique, $type, $ajax=''){
	if ($statut=='publie') {
		$puce='puce-verte.gif';
	}
	else if ($statut == "refuse") {
		$puce = 'puce-poubelle.gif';
	}
	else if ($statut == "dispo") { // moderation : a valider
		$puce = 'puce-rouge.gif';
	}
	else  // i.e. $statut=="off" feed d'un site en mode "miroir"
		$puce = 'puce-rouge-anim.gif';

	return http_img_pack($puce, $statut, "class='puce'");
}


// La couleur du statut
// http://doc.spip.org/@puce_statut
function puce_statut($statut, $atts='') {
	switch ($statut) {
		case 'publie':
			$img = 'puce-verte.gif';
			$alt = _T('info_article_publie');
			return http_img_pack($img, $alt, $atts);
		case 'prepa':
			$img = 'puce-blanche.gif';
			$alt = _T('info_article_redaction');
			return http_img_pack($img, $alt, $atts);
		case 'prop':
			$img = 'puce-orange.gif';
			$alt = _T('info_article_propose');
			return http_img_pack($img, $alt, $atts);
		case 'refuse':
			$img = 'puce-rouge.gif';
			$alt = _T('info_article_refuse');
			return http_img_pack($img, $alt, $atts);
		case 'poubelle':
			$img = 'puce-poubelle.gif';
			$alt = _T('info_article_supprime');
			return http_img_pack($img, $alt, $atts);
	}
	return http_img_pack($img, $alt, $atts);
}

// http://doc.spip.org/@afficher_script_statut
function afficher_script_statut($id, $type, $n, $img, $statut, $titre, $act='') {
	$i = http_wrapper($img);
	$h = generer_action_auteur("instituer_$type","$id-$statut");
	$h = "javascript:selec_statut('$id', '$type', $n, '$i', '$h');";
	$t = supprimer_tags($titre);
	$inf = getimagesize($i);
	return "<a href=\"$h\"\ntitle=\"$t\"$act><img src='$i' $inf[3] alt=' '/></a>";
}



?>
