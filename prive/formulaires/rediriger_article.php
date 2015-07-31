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

function formulaires_rediriger_article_charger_dist($id_article,$retour=''){

	include_spip('inc/autoriser');
	if (!autoriser('modifier', 'article', $id_article))
		return false;

	$row = sql_fetsel('id_article,virtuel','spip_articles','id_article='.intval($id_article));
	if (!$row['id_article'])
		return false;
	include_spip('inc/lien');
	$redirection = virtuel_redirige($row["virtuel"]);

	if (!$redirection
		AND $GLOBALS['meta']['articles_redirection'] != 'oui')
		return false;


	include_spip('inc/texte');
	$valeurs = array(
		'redirection'=>$redirection,
		'id'=>$id_article,
		'_afficher_url' => ($redirection?propre("[->$redirection]"):''),
		);
	return $valeurs;
}

function formulaires_rediriger_article_verifier_dist($id_article,$retour=''){
	$erreurs = array();

	if(($redirection = _request('redirection')) == $id_article || $redirection == 'art'.$id_article)
		$erreurs['redirection'] = _T('info_redirection_boucle');

	return $erreurs;
}

function formulaires_rediriger_article_traiter_dist($id_article,$retour=''){

	$url = preg_replace(",^\s*https?://$,i", "", rtrim(_request('redirection')));
	if ($url) $url = corriger_caracteres($url);

	include_spip('action/editer_article');
	articles_set($id_article, array('virtuel'=>$url));

	$js = _AJAX ? '<script type="text/javascript">if (window.ajaxReload) ajaxReload("wysiwyg");</script>':'';

	return array('message_ok'=>($url?_T('info_redirection_activee'):_T('info_redirection_desactivee')).$js,'editable'=>true);
}
?>
