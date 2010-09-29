<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@inc_puce_statut_dist
function inc_puce_statut_dist($id_objet, $statut, $id_rubrique, $type, $ajax=false) {
	$type = objet_type($type);
	if ($f = charger_fonction($type,'puce_statut',true))
		return $f($id_objet, $statut, $id_rubrique, $type, $ajax);
	else
		return "<img src='" . chemin_image("$type-24.png") . "' alt='' />";
}

// http://doc.spip.org/@puce_statut_document_dist
function puce_statut_document_dist($id, $statut, $id_rubrique, $type, $ajax='') {
	return "<img src='" . chemin_image("attachment-16.png") . "' alt=''  />";
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
			   "info_administrateurs" => chemin_image('auteur-0minirezo-16.png'),
			   "info_redacteurs" =>chemin_image('auteur-1comite-16.png'),
			   "info_visiteurs" => chemin_image('auteur-6forum-16.png'),
			   "info_statut_site_4" => chemin_image('auteur-5poubelle-16.png')
			   );
	}

	if ($statut == 'nouveau') return '';

	$index = array_search($statut, $GLOBALS['liste_des_statuts']);

	if (!$index) $index = 'info_visiteurs';

	$img = $images_des_statuts[$index];
	$alt = $titre_des_statuts[$index];

	if ($type != 'auteur') {
	  $img2 = chemin_image('del-16.png');
	  $titre = _T('titre_image_redacteur');
	  $fond = http_style_background($img2, 'top left no-repeat;');
	} else {$fond = ''; $titre = $alt;}
	  
	return http_img_pack($img, $alt, $fond, $titre);
}

// http://doc.spip.org/@bonhomme_statut
function bonhomme_statut($row) {
	$puce_statut = charger_fonction('puce_statut', 'inc');
	return $puce_statut(0, $row['statut'], 0, 'auteur');
}


// http://doc.spip.org/@puce_statut_mot_dist
function puce_statut_mot_dist($id, $statut, $id_groupe, $type, $ajax='') {
	return http_img_pack(chemin_image('mot-16.png'), "");
}

// http://doc.spip.org/@puce_statut_rubrique_dist
function puce_statut_rubrique_dist($id, $statut, $id_rubrique, $type, $ajax='') {

	return "<img src='" . chemin_image('rubrique-12.png') . "' alt='' />";
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


	$inser_puce = puce_statut($statut, " width='8' height='8' style='margin: 1px;'$ajax_node");

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
		  . afficher_script_statut($id, $type, -1, 'puce-preparer-8.png', 'prepa', $titles['blanche'])
		  . afficher_script_statut($id, $type, -12, 'puce-proposer-8.png', 'prop', $titles['orange'])
		  . afficher_script_statut($id, $type, -23, 'puce-publier-8.png', 'publie', $titles['verte'])
		  . afficher_script_statut($id, $type, -34, 'puce-refuser-8.png', 'refuse', $titles['rouge'])
		  . afficher_script_statut($id, $type, -45, 'puce-supprimer-8.png', 'poubelle', $titles['poubelle'])
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


// La couleur du statut
// http://doc.spip.org/@puce_statut
function puce_statut($statut, $atts='') {
	switch ($statut) {
		case 'publie':
			$img = 'puce-publier-8.png';
			$alt = _T('info_article_publie');
			return http_img_pack($img, $alt, $atts);
		case 'prepa':
			$img = 'puce-preparer-8.png';
			$alt = _T('info_article_redaction');
			return http_img_pack($img, $alt, $atts);
		case 'prop':
			$img = 'puce-proposer-8.png';
			$alt = _T('info_article_propose');
			return http_img_pack($img, $alt, $atts);
		case 'refuse':
			$img = 'puce-refuser-8.png';
			$alt = _T('info_article_refuse');
			return http_img_pack($img, $alt, $atts);
		case 'poubelle':
			$img = 'puce-supprimer-8.png';
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
