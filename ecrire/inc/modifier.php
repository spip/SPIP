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

/**
 * Fonctions d'aides pour les fonctions d'objets de modification de contenus
 *
 * @package SPIP\Objets\Modifications
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Collecte des champs postés
 * 
 * Fonction générique pour la collecte des posts
 * dans action/editer_xxx
 *
 * @param array $white_list
 *     Les champs à récupérer
 * @param array $black_list
 *     Les champs à ignorer
 * @param array|null $set
 *     array : Tableau des champs postés
 *     null  : Les champs sont obtenus par des _request() sur les noms de la white liste
 * @param bool $tous
 *     true : Recupère tous les champs de white_list meme ceux n'ayant pas ete postés
 * @return array
 *     Tableau des champs et valeurs collectées 
 */
function collecter_requests($white_list, $black_list, $set=null, $tous=false){
	$c = $set;
	if (!$c){
		$c = array();
		foreach($white_list as $champ) {
			// on ne collecte que les champs reellement envoyes par defaut.
			// le cas d'un envoi de valeur NULL peut du coup poser probleme.
			$val = _request($champ);
			if ($tous OR $val !== NULL) {
				$c[$champ] = $val;
			}
		}
		// on ajoute toujours la lang en saisie possible
		// meme si pas prevu au depart pour l'objet concerne
		if ($l = _request('changer_lang')){
			$c['lang'] = $l;
		}
	}
	foreach($black_list as $champ) {
		unset($c[$champ]);
	}
	
	return $c;
}

/**
 * Modifie le contenu d'un objet
 * 
 * Fonction generique pour l'API de modification de contenu, qui se
 * charge entre autres choses d'appeler les pipelines pre_edition
 * et post_edition
 *
 * Attention, pour éviter des hacks on interdit des champs
 * (statut, id_secteur, id_rubrique, id_parent),
 * mais la securite doit étre assurée en amont
 *
 * @api
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param array $options
 *     Toutes les options
 * @param array|null $c
 *     Couples champ/valeur à modifier
 * @param string $serveur
 *     Nom du connecteur à la base de données
 * @return bool|string
 *     - false  : Aucune modification, aucun champ n'est à modifier
 *     - chaîne vide : Vide si tout s'est bien passé
 *     - chaîne : Texte d'un message d'erreur
 */
function objet_modifier_champs($objet, $id_objet, $options, $c=null, $serveur='') {
	if (!$id_objet = intval($id_objet)) {
		spip_log('Erreur $id_objet non defini', 'warn');
		return _T('erreur_technique_enregistrement_impossible');
	}

	include_spip('inc/filtres');

	$table_objet = table_objet($objet,$serveur);
	$spip_table_objet = table_objet_sql($objet,$serveur);
	$id_table_objet = id_table_objet($objet,$serveur);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($spip_table_objet, $serveur);

	// Appels incomplets (sans $c)
	if (!is_array($c)) {
		spip_log('erreur appel objet_modifier_champs('.$objet.'), manque $c');
		return _T('erreur_technique_enregistrement_impossible');
	}

	// Securite : certaines variables ne sont jamais acceptees ici
	// car elles ne relevent pas de autoriser(xxx, modifier) ;
	// il faut passer par instituer_XX()
	// TODO: faut-il passer ces variables interdites
	// dans un fichier de description separe ?
	unset($c['statut']);
	unset($c['id_parent']);
	unset($c['id_rubrique']);
	unset($c['id_secteur']);

	// Gerer les champs non vides
	if (isset($options['nonvide']) AND is_array($options['nonvide'])) {
		foreach ($options['nonvide'] as $champ => $sinon) {
			if ($c[$champ] === '') {
				$c[$champ] = $sinon;
			}
		}
	}


	// N'accepter que les champs qui existent
	// TODO: ici aussi on peut valider les contenus
	// en fonction du type
	$champs = array();
	foreach($desc['field'] as $champ => $ignore)
		if (isset($c[$champ]))
			$champs[$champ] = $c[$champ];

	// Nettoyer les valeurs
	$champs = array_map('corriger_caracteres', $champs);

	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => $spip_table_objet, // compatibilite
				'table_objet' => $table_objet,
				'spip_table_objet' => $spip_table_objet,
				'type' =>$objet,
				'id_objet' => $id_objet,
				'champs' => isset($options['champs']) ? $options['champs'] : array(), // [doc] c'est quoi ?
				'serveur' => $serveur,
				'action' => 'modifier'
			),
			'data' => $champs
		)
	);

	if (!$champs) return false;


	// marquer le fait que l'objet est travaille par toto a telle date
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		signale_edition ($id_objet, $GLOBALS['visiteur_session'], $objet);
	}

	// Verifier si les mises a jour sont pertinentes, datees, en conflit etc
	include_spip('inc/editer');
	$conflits = controler_md5($champs, $_POST, $objet, $id_objet, $serveur);
	// cas hypothetique : normalement inc/editer verifie en amont le conflit edition
	// et gere l'interface
	// ici on ne renvoie donc qu'un messsage d'erreur, au cas ou on y arrive quand meme
	if ($conflits)
		return _T('titre_conflit_edition');

	if ($champs) {
		// cas particulier de la langue : passer par instituer_langue_objet
		if (isset($champs['lang'])){
			if ($changer_lang=$champs['lang']){
				$id_rubrique = 0;
				if ($desc['field']['id_rubrique']){
					$parent = ($objet=='rubrique')?'id_parent':'id_rubrique';
					$id_rubrique = sql_getfetsel($parent, $spip_table_objet, "$id_table_objet=".intval($id_objet));
				}
				$instituer_langue_objet = charger_fonction('instituer_langue_objet','action');
				$champs['lang'] = $instituer_langue_objet($objet,$id_objet, $id_rubrique, $changer_lang);
			}
			// on laisse 'lang' dans $champs,
			// ca permet de passer dans le pipeline post_edition et de journaliser
			// et ca ne gene pas qu'on refasse un sql_updateq dessus apres l'avoir
			// deja pris en compte
		}

		// la modif peut avoir lieu

		// faut-il ajouter date_modif ?
		if (isset($options['date_modif']) AND $options['date_modif']
		AND !isset($champs[$options['date_modif']]))
			$champs[$options['date_modif']] = date('Y-m-d H:i:s');

		// allez on commit la modif
		sql_updateq($spip_table_objet, $champs, "$id_table_objet=".intval($id_objet), $serveur);

		// on verifie si elle est bien passee
		$moof = sql_fetsel(array_keys($champs), $spip_table_objet, "$id_table_objet=".intval($id_objet), array(), array(), '', array(), $serveur);
		// si difference entre les champs, reperer les champs mal enregistres
		if ($moof != $champs) {
			$liste = array();
			foreach($moof as $k=>$v)
				if ($v !== $champs[$k]
					// ne pas alerter si le champ est numerique est que les valeurs sont equivalentes
					AND (!is_numeric($v) OR intval($v)!=intval($champs[$k]))
					) {
					$liste[] = $k;
					$conflits[$k]['post'] = $champs[$k];
					$conflits[$k]['save'] = $v;
				}
			// si un champ n'a pas ete correctement enregistre, loger et retourner une erreur
			// c'est un cas exceptionnel
			if (count($liste)){
				spip_log("Erreur enregistrement en base $objet/$id_objet champs :".var_export($conflits,true),'modifier.'._LOG_CRITIQUE);
				return _T('erreur_technique_enregistrement_champs',array('champs'=>"<i>'".implode("'</i>,<i>'",$liste)."'</i>"));
			}
		}

		// Invalider les caches
		if (isset($options['invalideur']) and $options['invalideur']) {
			include_spip('inc/invalideur');
			if (is_array($options['invalideur']))
				array_map('suivre_invalideur',$options['invalideur']);
			else
				suivre_invalideur($options['invalideur']);
		}

		// Notifications, gestion des revisions...
		// en standard, appelle |nouvelle_revision ci-dessous
		pipeline('post_edition',
			array(
				'args' => array(
					'table' => $spip_table_objet,
					'table_objet' => $table_objet,
					'spip_table_objet' => $spip_table_objet,
					'type' =>$objet,
					'id_objet' => $id_objet,
					'champs' => isset($options['champs']) ? $options['champs'] : array(), // [doc] kesako ?
					'serveur' => $serveur,
					'action' => 'modifier'
				),
				'data' => $champs
			)
		);
	}

	// journaliser l'affaire
	// message a affiner :-)
	include_spip('inc/filtres_mini');
	$qui = ((isset($GLOBALS['visiteur_session']['nom']) AND $GLOBALS['visiteur_session']['nom'])?$GLOBALS['visiteur_session']['nom']:$GLOBALS['ip']);
	journal(_L($qui.' a &#233;dit&#233; l&#8217;'.$objet.' '.$id_objet.' ('.join('+',array_diff(array_keys($champs), array('date_modif'))).')'), array(
		'faire' => 'modifier',
		'quoi' => $objet,
		'id' => $id_objet
	));

	return '';
}

/**
 * Modifie un contenu
 * 
 * Dépreciée :
 * Fonction générique pour l'API de modification de contenu
 *
 * @deprecated
 * @param string $type
 *     Type d'objet
 * @param int $id
 *     Identifiant de l'objet
 * @param array $options
 *     Toutes les options
 * @param array|null $c
 *     Couples champ/valeur à modifier
 * @param string $serveur
 *     Nom du connecteur à la base de données
 * @return bool
 *     true si quelque chose est modifié correctement
 *     false sinon (erreur ou aucun champ modifié)
 */
function modifier_contenu($type, $id, $options, $c=null, $serveur='') {
	$res = objet_modifier_champs($type, $id, $options, $c, $serveur);
	return ($res===''?true:false);
}

/**
 * Crée une modification d'un objet
 * 
 * Wrapper pour remplacer tous les obsoletes revision_xxx
 *
 * @deprecated
 *     Utiliser objet_modifier();
 * @see objet_modifier();
 * 
 * @param string $objet
 *     Nom de l'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param array $c
 *     Couples des champs/valeurs modifiées
 * @return mixed|string
 */
function revision_objet($objet,$id_objet,$c=null){
	$objet = objet_type($objet); // securite
	include_spip('action/editer_objet');
	return objet_modifier($objet,$id_objet,$c);
}


?>
