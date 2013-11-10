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

// http://doc.spip.org/@action_editer_signature_dist
function action_editer_signature_dist($arg=null) {

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// si id_signature n'est pas un nombre, c'est une creation
	// mais on verifie qu'on a toutes les donnees qu'il faut.
	if (!$id_signature = intval($arg)) {
		$id_petition = _request('id_petition');
		if (!($id_petition))
			return array(0,'');
		$id_signature = signature_inserer($id_petition);
	}

	// Enregistre l'envoi dans la BD
	if ($id_signature > 0)
		$err = signature_modifier($id_signature);

	return array($id_signature,$err);
}

/**
 * Mettre a jour une signature existante
 * 
 * @param int $id_signature
 * @param array $set
 * @return string
 */
function signature_modifier($id_signature, $set=null) {
	$err = '';

	include_spip('inc/modifier');
	$c = collecter_requests(
		// white list
		array(
		 "nom_email","ad_email",
		 "nom_site","url_site","message","statut"
		),
		// black list
		array('statut','id_petition','date_time'),
		// donnees eventuellement fournies
		$set
	);

	if ($err = objet_modifier_champs('signature', $id_signature,
		array(
			'nonvide' => array('nom_email' => _T('info_sans_titre'))
		),
		$c))
		return $err;

	// Modification de statut
	$c = collecter_requests(array('statut','id_petition','date_time'),array(),$set);
	$err .= signature_instituer($id_signature, $c);

	return $err;
}

/**
 * Inserer une signature en base
 * @param int $id_petition
 * @return int
 */
function signature_inserer($id_petition) {

	// Si $id_petition vaut 0 ou n'est pas definie, echouer
	if (!$id_petition = intval($id_petition))
		return 0;

	$champs = array(
		'id_petition' => $id_petition,
		'statut' =>  'prepa',
		'date_time' => date('Y-m-d H:i:s'));

	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_signatures',
			),
			'data' => $champs
		)
	);

	$id_signature = Sql::insertq("spip_signatures", $champs);

	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_signatures',
				'id_objet' => $id_signature
			),
			'data' => $champs
		)
	);

	return $id_signature;
}


// $c est un array ('statut', 'id_petition' = changement de petition)
// il n'est pas autoriser de deplacer une signature
// http://doc.spip.org/@signature_instituer
function signature_instituer($id_signature, $c, $calcul_rub=true) {

	include_spip('inc/autoriser');
	include_spip('inc/modifier');

	$row = Sql::fetsel("S.statut, S.date_time, P.id_article", "spip_signatures AS S JOIN spip_petitions AS P ON S.id_petition=P.id_petition", "S.id_signature=".intval($id_signature));
	$statut_ancien = $statut = $row['statut'];
	$date_ancienne = $date = $row['date_time'];
	$champs = array();

	$d = isset($c['date_time'])?$c['date_time']:null;
	$s = isset($c['statut'])?$c['statut']:$statut;

	// cf autorisations dans inc/signature_instituer
	if ($s != $statut OR ($d AND $d != $date)) {
		$statut = $champs['statut'] = $s;

		// En cas de publication, fixer la date a "maintenant"
		// sauf si $c commande autre chose
		// ou si l'signature est deja date dans le futur
		// En cas de proposition d'un signature (mais pas depublication), idem
		if ($champs['statut'] == 'publie') {
			if ($d)
				$champs['date_time'] = $date = $d;
			else
				$champs['date_time'] = $date = date('Y-m-d H:i:s');
		}
		// on peut redater une signature qu'on relance
		elseif($d)
			$champs['date_time'] = $date = $d;
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_signatures',
				'id_objet' => $id_signature,
				'action'=>'instituer',
				'statut_ancien' => $statut_ancien,
			),
			'data' => $champs
		)
	);

	if (!count($champs)) return;

	// Envoyer les modifs.
	Sql::updateq('spip_signatures',$champs,'id_signature='.intval($id_signature));

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='signature/$id_signature'");
	suivre_invalideur("id='article/".$row['id_article']."'");

	// Pipeline
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_signatures',
				'id_objet' => $id_signature,
				'action'=>'instituer',
				'statut_ancien' => $statut_ancien,
			),
			'data' => $champs
		)
	);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications('instituersignature', $id_signature,
			array('statut' => $statut, 'statut_ancien' => $statut_ancien, 'date'=>$date)
		);
	}

	return ''; // pas d'erreur
}


/**
 * Pour eviter le recours a un verrou (qui bloque l'acces a la base),
 * on commence par inserer systematiquement la signature
 * puis on demande toutes celles ayant la propriete devant etre unique
 * (mail ou site). S'il y en a plus qu'une on les retire sauf la premiere
 * En cas d'acces concurrents il y aura des requetes de retraits d'elements
 * deja detruits. Bizarre ?  C'est mieux que de bloquer!
 *
 * http://doc.spip.org/@signature_entrop
 *
 * @param string $where
 * @return array
 */
function signature_entrop($where)
{
	$entrop = array();
	$where .= " AND statut='publie'";
	$res = Sql::select('id_signature', 'spip_signatures', $where,'',"date_time desc");
	$n = Sql::count($res);
	if ($n>1) {
		while($r=Sql::fetch($res))
			$entrop[]=$r['id_signature'];
		// garder la premiere signature
		array_shift($entrop);
	}
	Sql::free($res);

	if (count($entrop)){
		Sql::delete('spip_signatures', Sql::in('id_signature', $entrop));
	}

	return $entrop;
}

// obsolete
function revision_signature($id_signature, $c=false) {
	return signature_modifier($id_signature,$c);
}

?>
