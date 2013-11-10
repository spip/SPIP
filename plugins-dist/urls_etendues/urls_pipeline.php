<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function urls_autoriser($f){return $f;}

function autoriser_url_administrer($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	return (
		isset($GLOBALS['meta']['urls_activer_controle'])
		AND $GLOBALS['meta']['urls_activer_controle']=='oui'
	  AND $qui['statut']=='0minirezo'
	  AND !$qui['restreint']);
}

function autoriser_controlerurls_menu_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	return autoriser('administrer','url');
}

function urls_afficher_fiche_objet($flux){
	if (isset($GLOBALS['meta']['urls_activer_controle'])
		AND $GLOBALS['meta']['urls_activer_controle']=='oui'
		AND $objet = $flux['args']['type']
		AND $id_objet = $flux['args']['id']
	  AND objet_info($objet,'page')){
		$p = strpos($flux['data'],'fiche_objet');
		$p = strpos($flux['data'],'<!--/hd-->',$p);
		$p = strrpos(substr($flux['data'],0,$p),'<div');

		$res = recuperer_fond('prive/objets/editer/url',array('id_objet'=>$id_objet,'objet'=>$objet),array('ajax'=>true));
		$flux['data'] = substr_replace($flux['data'],$res, $p, 0);
	}
	return $flux;
}
?>