<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_virtualiser_article_charger_dist($id_article,$retour=''){

	$row = sql_fetsel('id_article,chapo','spip_articles','id_article='.intval($id_article));
	if (!$row['id_article'])
		return false;

	$virtuel = (strncmp($row["chapo"],'=',1)!==0) ? '' : chapo_redirige(substr($row["chapo"], 1));

	if (!$virtuel
		AND $GLOBALS['meta']['articles_redirection'] != 'oui')
		return false;


	$valeurs = array(
		'virtuel'=>$virtuel,
		'id'=>$id_article,
		'_afficher_url' => ($virtuel?propre("[->$virtuel]"):''),
		);
	return $valeurs;
}

function formulaires_virtualiser_article_traiter_dist($id_article,$retour=''){

	$url = preg_replace(",^\s*https?://$,i", "", rtrim(_request('virtuel')));
	if ($url) $url = corriger_caracteres("=$url");

	include_spip('action/editer_article');
	articles_set($id_article, array('chapo'=>$url));

	return array('message_ok'=>'','editable'=>true);
}
?>