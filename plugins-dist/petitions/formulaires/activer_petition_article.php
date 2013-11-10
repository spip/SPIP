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

/**
 * Charger
 *
 * @param int $id_article
 * @return array
 */
function formulaires_activer_petition_article_charger_dist($id_article){
	
	$valeurs = array();
	
	$valeurs['editable'] = true;
	
	if (!autoriser('modererpetition', 'article', $id_article))
		$valeurs['editable'] = false;

	include_spip('inc/presentation');
	$nb_signatures = 0;
	$petition = Sql::fetsel('*', 'spip_petitions', "id_article=$id_article");
	if ($petition)
		$nb_signatures = Sql::countsel('spip_signatures', 'id_petition='.intval($petition['id_petition']));

	$valeurs['id_article'] = $id_article;
	$valeurs['petition'] = $petition;
	$valeurs['_controle_petition'] = $nb_signatures?singulier_ou_pluriel($nb_signatures,'petitions:une_signature','petitions:nombre_signatures'):"";
	
	return $valeurs;
	
}

/**
 * Traiter
 *
 * @param int $id_article
 * @return array
 */
function formulaires_activer_petition_article_traiter_dist($id_article){
	
	include_spip('inc/autoriser');
	
	if (autoriser('modererpetition', 'article', $id_article)){
		switch(_request('change_petition')) {
		case 'on':
			foreach(array('email_unique','site_obli','site_unique','message') as $k)
				if (_request($k) != 'oui')
					set_request($k,'non');

			include_spip('action/editer_petition');
			if (!$id_petition=Sql::getfetsel('id_petition','spip_petitions','id_article='.intval($id_article))){
				$id_petition = petition_inserer($id_article);
			}

			petition_modifier(
				$id_petition,
			  array('email_unique' => _request('email_unique'),
							'site_obli' => _request('site_obli'),
							'site_unique' => _request('site_unique'),
							'message' => _request('message'),
			        'texte'=>_request('texte_petition'),
				      'statut'=>'publie',
			  )
			);
			break;
		case 'off':
			if ($id_petition=Sql::getfetsel('id_petition','spip_petitions','id_article='.intval($id_article))){
				include_spip('action/editer_petition');
				petition_modifier($id_petition,array('statut'=>'poubelle'));
			}
			break;
		}
	}
		
	return array('message_ok'=>_T('config_info_enregistree'));
	
}

?>
