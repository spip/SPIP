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

function formulaires_rediriger_article_charger_dist($id_article,$retour=''){

	$row = sql_fetsel('id_article,chapo','spip_articles','id_article='.intval($id_article));
	if (!$row['id_article'])
		return false;

	$redirection = (strncmp($row["chapo"],'=',1)!==0) ? '' : chapo_redirige(substr($row["chapo"], 1));

	if (!$redirection
		AND $GLOBALS['meta']['articles_redirection'] != 'oui')
		return false;


	$valeurs = array(
		'redirection'=>$redirection,
		'id'=>$id_article,
		'_afficher_url' => ($redirection?propre("[->$redirection]"):''),
		);
	return $valeurs;
}

function formulaires_rediriger_article_traiter_dist($id_article,$retour=''){

	$url = preg_replace(",^\s*https?://$,i", "", rtrim(_request('redirection')));
	if ($url) $url = corriger_caracteres("=$url");

	include_spip('action/editer_article');
	articles_set($id_article, array('chapo'=>$url));

	return array('message_ok'=>'','editable'=>true);
}
?>