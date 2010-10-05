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


/**
 * Compter les articles publies lies a un auteur, dans une boucle auteurs
 * pour la vue prive/liste/auteurs.html
 *
 * @param <type> $idb
 * @param <type> $boucles
 * @param <type> $crit
 * @param <type> $left
 */
function critere_compteur_articles_filtres_dist($idb, &$boucles, $crit, $left=false){
	$boucle = &$boucles[$idb];

	$_statut = calculer_liste($crit->param[0], array(), $boucles, $boucle->id_parent);

	$not="";
	if ($crit->not)
		$not=", 'NOT'";
	$boucle->from['auteurs_articles'] = 'spip_auteurs_articles';
	$boucle->from_type['auteurs_articles'] = 'left';
	$boucle->join['auteurs_articles'] = array("'auteurs'","'id_auteur'");

	$boucle->from['articles'] = 'spip_articles';
	$boucle->from_type['articles'] = 'left';
	$boucle->join['articles'] = array("'auteurs_articles'","'id_article'","'id_article'","'(articles.statut IS NULL OR '.sql_in('articles.statut',$_statut$not).')'");

	$boucle->select[]= "COUNT(articles.id_article) AS compteur_articles";
	$boucle->group[] = 'auteurs.id_auteur';
}
/**
 * Compter les articles publies lies a un auteur, dans une boucle auteurs
 * pour la vue prive/liste/auteurs.html
 *
 * @param <type> $p
 * @return <type>
 */
function balise_COMPTEUR_ARTICLES_dist($p) {
	return rindex_pile($p, 'compteur_articles', 'compteur_articles_filtres');
}


/**
 * Calculer l'initiale d'un nom
 *
 * @param <type> $nom
 * @return <type>
 */
function initiale($nom){
	return spip_substr(trim(strtoupper($nom)),0,1);
}

/**
 * Afficher l'initiale pour la navigation par lettres
 *
 * @staticvar string $memo
 * @param <type> $url
 * @param <type> $initiale
 * @param <type> $compteur
 * @param <type> $debut
 * @param <type> $pas
 * @return <type>
 */
function afficher_initiale($url,$initiale,$compteur,$debut,$pas){
	static $memo = null;
	$res = '';
	if (!$memo
		OR (!$initiale AND !$url)
		OR ($initiale!==$memo['initiale'])
		){
		$newcompt = intval(floor(($compteur-1)/$pas)*$pas);
		// si fin de la pagination et une seule entree, ne pas l'afficher, ca ne sert a rien
		if (!$initiale AND !$url AND !$memo['entree']) return "";
		if ($memo){
			$on = (($memo['compteur']<=$debut)
				AND (
						$newcompt>$debut OR ($newcompt==$debut AND $newcompt==$memo['compteur'])
						));
			$res = lien_ou_expose($memo['url'],$memo['initiale'],$on,'lien_pagination');
		}
		if ($initiale)
			$memo = array('entree'=>isset($memo['entree'])?$memo['entree']+1:0,'initiale'=>$initiale,'url'=>parametre_url($url,'i',$initiale),'compteur'=>$newcompt);
	}
	return $res;
}

/**
 * Calculer l'url vers la messagerie :
 * - si l'auteur accepte les messages internes et que la messagerie est activee
 * et qu'il est en ligne, on propose le lien vers la messagerie interne
 * - sinon on propose un lien vers un email si possible
 * - sinon rien
 *
 * @staticvar string $time
 * @param int $id_auteur
 * @param date $en_ligne
 * @param string $statut
 * @param string $imessage
 * @param string $email
 * @return string
 */
function auteur_lien_messagerie($id_auteur,$en_ligne,$statut,$imessage,$email=''){
	static $time = null;
	if (!in_array($statut, array('0minirezo', '1comite')))
		return '';

	if (is_null($time))
		$time = time();
	$parti = (($time-strtotime($en_ligne))>15*60);

	if ($imessage != 'non' AND !$parti AND $GLOBALS['meta']['messagerie_agenda'] != 'non')
		return generer_action_auteur("editer_message","normal/$id_auteur");

	elseif (strlen($email) AND autoriser('voir', 'auteur', $id_auteur))
		return 'mailto:' . $email;

	else
		return '';

}

?>