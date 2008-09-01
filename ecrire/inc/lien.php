<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Calcul des raccourcis
// Idealement generer_url_entite delegue au jeu 'type_urls' qui les gere
// Par souci de compatibilite on teste toujours l'existence des fonctions
// generer_url_<RACCOURCI> et generer_url_<RACCOURCI>_dist

// http://doc.spip.org/@inc_lien_dist
function inc_lien_dist($type,$id,$args,$ancre,$texte='',$pour='url',$connect='') {
	if ($connect) {
		$id_type = ($type != 'site') ? "id_$type" : 'id_syndic';
		$res = get_spip_script('./')
		. "?"._SPIP_PAGE."=$type&$id_type=$id&connect=$connect"
		. (!$args ? '' : "&$args");
	} else {
		$g = 'generer_url_' . $type;
		if (function_exists($g) OR function_exists($g .= '_dist')) 
			$g = $g($id, $args, $ancre);
		else $g = false;
		if (!$g) $g = generer_url_entite($id, $type, $args, $ancre);
		if (!$g) return false;
		$res = $g;
	}
	if ($pour == 'url') return array($res);
	$g = 'calculer_url_' . $type;
	if (!(function_exists($g) OR function_exists($g .= '_dist')))
		return array($res);
	return $g($id, $texte, $res, $connect);
}

// http://doc.spip.org/@calculer_url_article_dist
function calculer_url_article_dist($id, $texte='', $lien='', $connect='') {

	$row = sql_fetsel('titre,lang', 'spip_articles', "id_article=$id",'','','','',$connect);
	if (!trim($texte))
		$texte = supprimer_numero($row['titre']);
	if (!trim($texte))
		$texte = _T('article') . $id;
	return array($lien, 'spip_in', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_rubrique_dist
function calculer_url_rubrique_dist($id, $texte='', $lien='', $connect='')
{
	$row = sql_fetsel('titre,lang', 'spip_rubriques', "id_rubrique=$id",'','','','',$connect);
	if (!trim($texte)) {
		$texte = supprimer_numero($row['titre']);
		if (!trim($texte)) $texte = $id;
	}

	return array($lien, 'spip_in', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_breve_dist
function calculer_url_breve_dist($id, $texte='', $lien='', $connect='')
{
	$row = sql_fetsel('titre,lang', 'spip_breves', "id_breve=$id",'','','','',$connect);
	if (!trim($texte)) {
		$texte = supprimer_numero($row['titre']);
		if (!trim($texte)) $texte = $id;
	}
	return array($lien, 'spip_in', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_auteur_dist
function calculer_url_auteur_dist($id, $texte='', $lien='', $connect='')
{
	if ($texte=='') {
		$row = sql_fetsel('nom', 'spip_auteurs', "id_auteur=$id",'','','','',$connect);
		$texte = $row['nom'];
	}
	return array($lien, 'spip_in', $texte); # pas de hreflang
}

// http://doc.spip.org/@calculer_url_mot_dist
function calculer_url_mot_dist($id, $texte='', $lien='', $connect='')
{
	if (!trim($texte)) {
		$row = sql_fetsel('titre', 'spip_mots', "id_mot=$id",'','','','',$connect);
		$texte = supprimer_numero($row['titre']);
		if (!trim($texte)) $texte = $id;
	}
	return array($lien, 'spip_in', $texte);
}

// http://doc.spip.org/@calculer_url_document_dist
function calculer_url_document_dist($id, $texte='', $lien='', $connect='')
{
	if ($texte=='') {
		$row = sql_fetsel('titre,fichier', 'spip_documents', "id_document=$id",'','','','',$connect);

		$texte = $row['titre'];
		if (!trim($texte))
			$texte = preg_replace(",^.*/,","",$row['fichier']);
		if (!trim($texte))
		    $texte = $id;
	}
	return array($lien, 'spip_in', $texte); # pas de hreflang
}

// http://doc.spip.org/@calculer_url_site_dist
function calculer_url_site_dist($id, $texte='', $lien='', $connect='')
{
	# attention dans le cas des sites le lien pointe non pas sur
	# la page locale du site, mais directement sur le site lui-meme
	$row =sql_fetsel('nom_site,url_site', 'spip_syndic', "id_syndic=$id",'','','','',$connect);
	if ($row) {
		$lien = $row['url_site'];
		if (!trim($texte))
			$texte = supprimer_numero($row['nom_site']);
		if (!trim($texte)) $texte = $id;
	}
	return array($lien, 'spip_out', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_forum_dist
function calculer_url_forum_dist($id, $texte='', $lien='', $connect='')
{
	if (!trim($texte)) {
		$row = sql_fetsel('titre', 'spip_forum', "id_forum=$id AND statut='publie'",'','','','',$connect);
		$texte = $row['titre'];
		if (!trim($texte)) $texte = $id;
	}
	return array($lien, 'spip_in', $texte); # pas de hreflang
}
?>
