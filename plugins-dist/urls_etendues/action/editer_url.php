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

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_editer_url_dist() {

	// Rien a faire ici pour le moment
	#$securiser_action = charger_fonction('securiser_action', 'inc');
	#$arg = $securiser_action();

}


function url_nettoyer($titre,$longueur_maxi,$longueur_min=0,$separateur='-',$filtre=''){

	$titre = supprimer_tags(supprimer_numero(extraire_multi($titre)));
	$url = translitteration(corriger_caracteres($titre));

	if ($filtre)
		$url = $filtre($url);

	// on va convertir tous les caracteres de ponctuation et espaces
	// a l'exception de l'underscore (_), car on veut le conserver dans l'url
	$url = str_replace('_', chr(7), $url);
	$url = @preg_replace(',[[:punct:][:space:]]+,u', ' ', $url);
	$url = str_replace(chr(7), '_', $url);

	// S'il reste trop de caracteres non latins, les gerer comme wikipedia
	// avec rawurlencode :
	if (preg_match_all(",[^a-zA-Z0-9 _]+,", $url, $r, PREG_SET_ORDER)) {
		foreach ($r as $regs) {
			$url = substr_replace($url, rawurlencode($regs[0]),
				strpos($url, $regs[0]), strlen($regs[0]));
		}
	}

	// S'il reste trop peu, renvoyer vide
	if (strlen($url) < $longueur_min)
		return '';

	// Sinon couper les mots et les relier par des $separateur
	$mots = preg_split(",[^a-zA-Z0-9_%]+,", $url);
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
	$has_parent = true;
	# assurer la coherence des champs techniques si non fournis
	if (!isset($set['id_parent'])){
		$has_parent = false;
		$set['id_parent'] = 0;
	}
	if (!isset($set['segments']))
		$set['segments'] = count(explode('/',$set['url']));
	$perma = false;
	if (isset($set['perma']) AND $set['perma']){
		unset($set['perma']);
		$perma = true;
	}
	$redate = true;

	# le separateur ne peut pas contenir de /
	if (strpos($separateur,'/')!==false)
		$separateur = "-";

	// Si l'insertion echoue, c'est une violation d'unicite.
	$where_urllike = 'url LIKE '.url_sql_quote_like($set['url']);
	$where_thisurl = $where_urllike." AND id_parent=".intval($set['id_parent']);
	if (
		// si pas de parent defini, il faut que cette url soit unique, independamment de id_parent
		// il faut utiliser un LIKE pour etre case unsensitive en sqlite
	  (!$has_parent AND Sql::countsel("spip_urls",$where_urllike))
		OR @Sql::insertq('spip_urls', $set) <= 0) {

		// On veut chiper une ancienne adresse ou prendre celle d'un repertoire deja present?
		if (
			(!is_dir(_DIR_RACINE.$set['url']) AND !file_exists(_DIR_RACINE.$set['url']))
			// un vieux url
			AND $vieux = Sql::fetsel('*', 'spip_urls', $where_thisurl)
			// qui n'est pas permanente
			AND !$vieux['perma']
		  // et dont l'objet a une url plus recente
		  AND $courant = Sql::fetsel('*', 'spip_urls',
			  'type='.Sql::quote($vieux['type']).' AND id_objet='.Sql::quote($vieux['id_objet'])
			  .' AND date>'.Sql::quote($vieux['date']), '', 'date DESC', 1)
		  ) {
			if ($confirmer AND !_request('ok2')) {
				die ("Vous voulez chiper l'URL de l'objet ".$courant['type']." "
					. $courant['id_objet']." qui a maintenant l'url "
					. $courant['url']);
			}
			$where_thisurl = "url=".Sql::quote($vieux['url'])." AND id_parent=".intval($vieux['id_parent']);
			// si oui on le chipe
			Sql::updateq('spip_urls', $set, $where_thisurl);
			Sql::updateq('spip_urls', array('date' => date('Y-m-d H:i:s')), $where_thisurl);
		}

		// Sinon
		else {

			// Soit c'est un Come Back d'une ancienne url propre de l'objet
			// Soit c'est un vrai conflit. Rajouter l'ID jusqu'a ce que ca passe,
			// mais se casser avant que ca ne casse.

			// il peut etre du a un changement de casse de l'url simplement
			// pour ce cas, on reecrit systematiquement l'url en plus d'actualiser la date
			do {
				$where = "type=".Sql::quote($set['type'])
								 ." AND id_objet=".intval($set['id_objet'])
								 ." AND id_parent=".intval($set['id_parent'])
								 ." AND url LIKE ";
				if (
					!is_dir(_DIR_RACINE.$set['url']) AND !file_exists(_DIR_RACINE.$set['url'])
					AND Sql::countsel('spip_urls', $where  .url_sql_quote_like($set['url']))) {
					Sql::updateq('spip_urls', array('url'=>$set['url'], 'date' => date('Y-m-d H:i:s')), $where  .url_sql_quote_like($set['url']));
					spip_log("reordonne ".$set['type']." ".$set['id_objet']);
					$redate = false;
					continue;
				}
				else {
					$set['url'] .= $separateur.$set['id_objet'];
					if (strlen($set['url']) > 200)
						//serveur out ? retourner au mieux
						return false;
					elseif (Sql::countsel('spip_urls', $where . url_sql_quote_like($set['url']))) {
						Sql::updateq('spip_urls', array('url'=>$set['url'], 'date' => date('Y-m-d H:i:s')), $where .url_sql_quote_like($set['url']));
						$redate = false;
						continue;
					}
				}
			} while ($redate AND @Sql::insertq('spip_urls', $set) <= 0);

		}
	}

	$where_thisurl = 'url='.Sql::quote($set['url'])." AND id_parent=".intval($set['id_parent']); // maj
	if ($redate)
		Sql::updateq('spip_urls', array('date' => date('Y-m-d H:i:s')), $where_thisurl);

	// si url perma, poser le flag sur la seule url qu'on vient de mettre
	if ($perma)
		Sql::update('spip_urls', array('perma' => "($where_thisurl)"), "type=".Sql::quote($set['type'])." AND id_objet=".intval($set['id_objet']));
	
	spip_log("Creation de l'url propre '" . $set['url'] . "' pour ".$set['type']." ".$set['id_objet']." (parent ".$set['id_parent']." perma $perma)","urls");
	return true;
}

function url_sql_quote_like($url){
	return Sql::quote(str_replace(array("%","_"),array("\\%","\\_"),$url))." ESCAPE ".Sql::quote('\\');
}

function url_verrouiller($objet,$id_objet,$url){
	$where = "id_objet=".intval($id_objet)." AND type=".Sql::quote($objet);
	$where .= " AND url=".Sql::quote($url);

	// pour verrouiller une url, on fixe sa date dans le futur, dans 10 ans
	Sql::updateq('spip_urls', array('date' => date('Y-m-d H:i:s',time()+10*365.25*24*3600)), $where);
}

function url_delete($objet,$id_objet,$url=""){
	$where = "id_objet=".intval($id_objet)." AND type=".Sql::quote($objet);
	if (strlen($url))
		$where .= " AND url=".Sql::quote($url);

	Sql::delete("spip_urls",$where);
}
?>
