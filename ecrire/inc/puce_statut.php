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
if (!defined('_ACTIVER_PUCE_RAPIDE'))
	define('_ACTIVER_PUCE_RAPIDE', true);

/**
 * Afficher la puce statut d'un objet
 *
 *
 * http://doc.spip.org/@inc_puce_statut_dist
 *
 * @param int $id_objet
 * @param string $statut
 * @param int $id_parent
 * @param string $type
 * @param bool $ajax
 *   indique qu'il ne faut renvoyer que le coeur du menu car on est dans la requete ajax post changement rapide
 * @param bool $menu_rapide
 * @return string
 */
function inc_puce_statut_dist($id_objet, $statut, $id_parent, $type, $ajax=false, $menu_rapide=_ACTIVER_PUCE_RAPIDE) {
	static $f_puce_statut = array();
	$type = objet_type($type);
	// cas prioritaire : fonction perso, qui permet aussi de gerer les cas historiques
	if (!isset($f_puce_statut[$type]) OR is_null($f_puce_statut[$type]))
		$f_puce_statut[$type] = charger_fonction($type,'puce_statut',true);
	if ($f_puce_statut[$type])
		return $f_puce_statut[$type]($id_objet, $statut, $id_parent, $type, $ajax, $menu_rapide);

	// si statut_image trouve quelque chose (et '' est quelque chose)
	// composer une puce, avec si possible changement rapide
	elseif(!is_null($puce=puce_statut_changement_rapide($id_objet,$statut,$id_parent,$type,$ajax,$menu_rapide))){
		return $puce;
	}

	// sinon fausse puce avec le type de l'image
	else
		return http_img_pack("$type-16.png",'');
}

/**
 * Recuperer l'image correspondant au statut, telle que declaree dans
 * declarer_tables_objets_sql
 * sous la forme
 * array('imagepardefaut.png','statut1'=>'imagestatut1.png','statut2'=>imagestatut2.png' ...)
 * mettre une chaine vide pour ne pas avoir d'image pour un statut particulier
 *
 * si rien de declare et que le statut est dans les cas connus (prepa, prop, publie, refuse, poubelle)
 * renvoyer l'image par defaut
 *
 * @param string $objet
 * @param string $statut
 * @return string
 *   null si pas capable de determiner l'image
 */
function statut_image($objet,$statut){
	$src = null;
	$table = table_objet_sql($objet);
	$desc = lister_tables_objets_sql($table);
	if (isset($desc['statut_images'])){
		// si une declaration statut_images
		// mais rien pour le statut demande, ne rien afficher
		$src = '';
		if (isset($desc['statut_images'][$statut]))
			$src = $desc['statut_images'][$statut];
		// sinon image par defaut ?
		elseif (isset($desc['statut_images'][0]))
			$src = $desc['statut_images'][0];
	}
	else {
		switch ($statut) {
			case 'prepa':
				$src = 'puce-preparer-8.png';
				break;
			case 'prop':
				$src = 'puce-proposer-8.png';
				break;
			case 'publie':
				$src = 'puce-publier-8.png';
				break;
			case 'refuse':
				$src = 'puce-refuser-8.png';
				break;
			case 'poubelle':
			case 'poub':
				$src = 'puce-supprimer-8.png';
				break;
		}
	}
	return $src;
}

/**
 * Recuperer le titre correspondant au statut, tel que declaree dans
 * declarer_tables_objets_sql
 * sous la forme
 * array('titre par defaut','statut1'=>'titre statut 1','statut2'=>'titre statut 2' ...)
 * mettre une chaine vide pour ne pas avoir de titre pour un statut particulier
 *
 * si rien de declare et que le statut est dans les cas connus (prepa, prop, publie, refuse, poubelle)
 * renvoyer le texte par defaut
 *
 * @param string $objet
 * @param string $statut
 * @return string
 */
function statut_titre($objet,$statut){
	$titre = '';
	$table = table_objet_sql($objet);
	$desc = lister_tables_objets_sql($table);
	if (isset($desc['statut_titres'])){
		// si une declaration statut_titres
		// mais rien pour le statut demande, ne rien afficher
		if (isset($desc['statut_titres'][$statut]))
			$titre = $desc['statut_titres'][$statut];
		// sinon image par defaut ?
		elseif (isset($desc['statut_titres'][0]))
			$titre = $desc['statut_titres'][0];
	}
	else {
		switch ($statut) {
			case 'prepa':
				$titre = 'texte_statut_en_cours_redaction';
				break;
			case 'prop':
				$titre = 'texte_statut_propose_evaluation';
				break;
			case 'publie':
				$titre = 'texte_statut_publie';
				break;
			case 'refuse':
				$titre = 'texte_statut_refuse';
				break;
			case 'poubelle':
			case 'poub':
				$titre = 'texte_statut_poubelle';
				break;
		}
	}
	return $titre?_T($titre):'';
}


/**
 * Recuperer le texte correspondant au choix de statut, tel que declare dans
 * declarer_tables_objets_sql
 * sous la forme
 * array('statut1'=>'texte statut 1','statut2'=>'texte statut 2' ...)
 * mettre une chaine vide pour ne pas proposer un statut
 * les statuts seront proposes dans le meme ordre que dans la declaration
 *
 * si rien de declare et que le statut est dans les cas connus (prepa, prop, publie, refuse, poubelle)
 * renvoyer le texte par defaut
 *
 * @param string $objet
 * @param string $statut
 * @return string
 */
function statut_texte_instituer($objet,$statut){
	$texte = '';
	$table = table_objet_sql($objet);
	$desc = lister_tables_objets_sql($table);
	if (isset($desc['statut_textes_instituer'])){
		// si une declaration statut_titres
		// mais rien pour le statut demande, ne rien afficher
		if (isset($desc['statut_textes_instituer'][$statut]))
			$texte = $desc['statut_textes_instituer'][$statut];
	}
	else {
		switch ($statut) {
			case 'prepa':
				$texte = 'texte_statut_en_cours_redaction';
				break;
			case 'prop':
				$texte = 'texte_statut_propose_evaluation';
				break;
			case 'publie':
				$texte = 'texte_statut_publie';
				break;
			case 'refuse':
				$texte = 'texte_statut_refuse';
				break;
			case 'poubelle':
			case 'poub':
				$texte = 'texte_statut_poubelle';
				break;
		}
	}
	return $texte?_T($texte):'';
}


/**
 * Afficher la puce statut d'un auteur
 *
 * Ne semble plus servir : desactive
 * Hack de compatibilite: les appels directs ont un  $type != 'auteur'
 * si l'auteur ne peut pas se connecter
 *
 * http://doc.spip.org/@puce_statut_auteur_dist
 *
 * @param int $id
 * @param string $statut
 * @param int $id_parent
 * @param string $type
 * @param string $ajax
 * @param bool $menu_rapide
 * @return string
 */
function puce_statut_auteur_dist($id, $statut, $id_parent, $type, $ajax='', $menu_rapide=_ACTIVER_PUCE_RAPIDE) {
	$img = statut_image('auteur',$statut);
	if (!$img) return '';
	$alt = statut_titre('auteur',$statut);

	$fond = ''; $titre = '';
	/*
	if ($type != 'auteur') {
	  $img2 = chemin_image('del-16.png');
	  $titre = _T('titre_image_redacteur');
	  $fond = http_style_background($img2, 'top left no-repeat;');
	}
	else {
	}
	*/
	return http_img_pack($img, $alt, $fond, $alt);
}


// http://doc.spip.org/@puce_statut_rubrique_dist
function puce_statut_rubrique_dist($id, $statut, $id_rubrique, $type, $ajax='',$menu_rapide=_ACTIVER_PUCE_RAPIDE) {
	return http_img_pack('rubrique-16.png', '');
}

// http://doc.spip.org/@puce_statut_article_dist
function puce_statut_changement_rapide($id, $statut, $id_rubrique, $type='article', $ajax = false, $menu_rapide=_ACTIVER_PUCE_RAPIDE) {
	$src = statut_image($type, $statut);
	if (!$src)
		return $src;

	if (!$id
	  OR !_SPIP_AJAX
	  OR !$menu_rapide) {
	  $ajax_node ='';
	}
	else
		$ajax_node = " class='imgstatut$type$id'";


	$t = statut_titre($type, $statut);
	$inser_puce = http_img_pack($src,$t,$ajax_node,$t);

	if (!$ajax_node)
		return $inser_puce;

	$table = table_objet_sql($type);
	$desc = lister_tables_objets_sql($table);
	if (!isset($desc['statut_textes_instituer']))
		return $inser_puce;

	// cas ou l'on a un parent connu (devrait disparaitre au profit du second cas plus generique)
	if ($id_rubrique){
		if (!autoriser('publierdans', 'rubrique', $id_rubrique))
			return $inser_puce;
	}
	// si pas d'id_rubrique fourni, tester directement instituer type avec le statut publie
	else {
		if (!autoriser('instituer', $type, $id, null, array('statut'=>'publie')))
			return $inser_puce;
	}

	$coord = array_flip(array_keys($desc['statut_textes_instituer']));
	if (!isset($coord[$statut]))
		return $inser_puce;

	$unit = 8/*widh de img*/+4/*padding*/;
	$margin = 4; /* marge a gauche + droite */
	$zero = 1 /*border*/ + $margin/2 + 2 /*padding*/;
	$clip = $zero+ ($unit*$coord[$statut]);

	if ($ajax){
		$width = $unit*count($desc['statut_textes_instituer'])+$margin;
		$out = "<span class='puce_objet_fixe $type'>"
		. $inser_puce
		. "</span>"
		. "<span class='puce_objet_popup $type statutdecal$type$id' style='width:{$width}px;margin-left:-{$clip}px;'>";
		$i=0;
		foreach($desc['statut_textes_instituer'] as $s=>$t){
			$out .= afficher_script_statut($id, $type, -$zero-$i++*$unit, statut_image($type,$s), $s, _T($t));
		}
		$out .= "</span>";
		return $out;
	}
	else {

		$nom = "puce_statut_";
	  $action = generer_url_ecrire('puce_statut',"",true);
	  $action = "if (!this.puce_loaded) { this.puce_loaded = true; prepare_selec_statut(this, '$nom', '$type', '$id', '$action'); }";
	  $over = " onmouseover=\"$action\"";

		$lang_dir = lang_dir(lang_typo());
		return 	"<span class='puce_objet $type' id='$nom$type$id' dir='$lang_dir'$over>"
		. $inser_puce
		. '</span>';
	}
}

// http://doc.spip.org/@afficher_script_statut
function afficher_script_statut($id, $type, $n, $img, $statut, $titre, $act='') {
	$h = generer_action_auteur("instituer_objet","$type-$id-$statut");
	$h = "selec_statut('$id', '$type', $n, jQuery('img',this).attr('src'), '$h');return false;";
	$t = supprimer_tags($titre);
	return "<a href=\"#\" onclick=\"$h\" title=\"$t\"$act>".http_img_pack($img,$t)."</a>";
}

// compat
// La couleur du statut
// http://doc.spip.org/@puce_statut

function puce_statut($statut, $atts='') {
	$src = statut_image('article',$statut);
	if (!$src) return '';
	return http_img_pack($src, statut_titre('article',$statut), $atts);
}

?>
