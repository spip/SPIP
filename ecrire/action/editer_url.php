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

function action_editer_url_dist() {

	// Rien a faire ici pour le moment
	#$securiser_action = charger_fonction('securiser_action', 'inc');
	#$arg = $securiser_action();

}


function url_nettoyer($titre,$longueur_maxi,$longueur_min=0,$separateur='-',$filtre=''){
	if (!defined('_TRANSLITTERER_URL')) define('_TRANSLITTERER_URL', true);
	
	$titre = supprimer_tags(supprimer_numero(extraire_multi($titre)));
	$url = corriger_caracteres($titre);
	
	
	if (_TRANSLITTERER_URL) $url = translitteration($url);

	if ($filtre)
		$url = $filtre($url);

	// on va convertir tous les caracteres de ponctuation et espaces
	// a l'exception de l'underscore (_), car on veut le conserver dans l'url
	$url = str_replace('_', chr(7), $url);
	$url = @preg_replace(',[[:punct:][:space:]]+,u', ' ', $url);
	$url = str_replace(chr(7), '_', $url);

	// S'il reste trop de caracteres non latins, les gerer comme wikipedia
	// avec rawurlencode :
	if (_TRANSLITTERER_URL && preg_match_all(",[^a-zA-Z0-9 _]+,", $url, $r, PREG_SET_ORDER)) {
		foreach ($r as $regs) {
			$url = substr_replace($url, rawurlencode($regs[0]),
				strpos($url, $regs[0]), strlen($regs[0]));
		}
	}

	// S'il reste trop peu, renvoyer vide
	if (strlen($url) < $longueur_min)
		return '';

	// Sinon couper les mots et les relier par des $separateur
	if (_TRANSLITTERER_URL) $mots = preg_split(",[^a-zA-Z0-9_%]+,", $url); 
	else $mots = preg_split(",[\s]+,", $url);
	$url = '';
	foreach ($mots as $mot) {
		if (!strlen($mot)) continue;
		$url2 = $url.$separateur.$mot;

		// Si on depasse $longueur_maxi caracteres, s'arreter
		// ne pas compter 3 caracteres pour %E9 mais un seul
		$long = preg_replace(',%.,', '', $url2);
		if (strlen($long) > $longueur_maxi) {
			break;
		}

		$url = $url2;
	}
	$url = substr($url, 1);

	// On enregistre en utf-8 dans la base
	$url = rawurldecode($url);

	if (strlen($url) < $longueur_min)
		return '';
	return $url;
}

function url_insert(&$set,$confirmer,$separateur){
	// Si l'insertion echoue, c'est une violation d'unicite.
	if (@sql_insertq('spip_urls', $set) <= 0) {
		// On veut chiper une ancienne adresse ?
		if (
		// un vieux url
		$vieux = sql_fetsel('*', 'spip_urls', 'url='.sql_quote($set['url']))
		// l'objet a une url plus recente
		AND $courant = sql_fetsel('*', 'spip_urls',
			'type='.sql_quote($vieux['type']).' AND id_objet='.sql_quote($vieux['id_objet'])
			.' AND date>'.sql_quote($vieux['date']), '', 'date DESC', 1
		)) {
			if ($confirmer AND !_request('ok2')) {
				die ("Vous voulez chiper l'URL de l'objet ".$courant['type']." "
					. $courant['id_objet']." qui a maintenant l'url "
					. $courant['url']);
			}

			// si oui on le chipe
			sql_updateq('spip_urls', $set, 'url='.sql_quote($set['url']));
			sql_updateq('spip_urls', array('date' => date('Y-m-d H:i:s')), 'url='.sql_quote($set['url']));
		}

		// Sinon
		else

		// Soit c'est un Come Back d'une ancienne url propre de l'objet
		// Soit c'est un vrai conflit. Rajouter l'ID jusqu'a ce que ca passe,
		// mais se casser avant que ca ne casse.

		// il peut etre du a un changement de casse de l'url simplement
		// pour ce cas, on reecrit systematiquement l'url en plus d'actualiser la date
		do {
			$where = "type=".sql_quote($set['type'])." AND id_objet=".intval($set['id_objet'])." AND url=";
			if (sql_countsel('spip_urls', $where  .sql_quote($set['url']))) {
				sql_updateq('spip_urls', array('url'=>$set['url'], 'date' => date('Y-m-d H:i:s')), $where  .sql_quote($set['url']));
				spip_log("reordonne ".$set['type']." ".$set['id_objet']);
				return true;
			}
			else {
				$set['url'] .= $separateur.$set['id_objet'];
				if (strlen($set['url']) > 200)
					//serveur out ? retourner au mieux
					return false;
				elseif (sql_countsel('spip_urls', $where . sql_quote($set['url']))) {
					sql_updateq('spip_urls', array('url'=>$set['url'], 'date' => date('Y-m-d H:i:s')), 'url='.sql_quote($set['url']));
					return true;
				}
			}
		} while (@sql_insertq('spip_urls', $set) <= 0);
	}

	sql_updateq('spip_urls', array('date' => date('Y-m-d H:i:s')), 'url='.sql_quote($set['url']));
	spip_log("Creation de l'url propre '" . $set['url'] . "' pour ".$set['type']." ".$set['id_objet']);
	return true;
}

function url_verrouiller($objet,$id_objet,$url){
	$where = "id_objet=".intval($id_objet)." AND type=".sql_quote($objet);
	$where .= " AND url=".sql_quote($url);

	// pour verrouiller une url, on fixe sa date dans le futur, dans 10 ans
	sql_updateq('spip_urls', array('date' => date('Y-m-d H:i:s',time()+10*365.25*24*3600)), $where);
}

function url_delete($objet,$id_objet,$url=""){
	$where = "id_objet=".intval($id_objet)." AND type=".sql_quote($objet);
	if (strlen($url))
		$where .= " AND url=".sql_quote($url);

	sql_delete("spip_urls",$where);
}
?>
