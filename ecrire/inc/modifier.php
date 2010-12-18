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


// Une fonction generique pour l'API de modification de contenu
// $options est un array() avec toutes les options
//
// renvoie false si rien n'a ete modifie, true sinon
//
// Attention, pour eviter des hacks on interdit les champs
// (statut, id_secteur, id_rubrique, id_parent),
// mais la securite doit etre assuree en amont
//
// http://doc.spip.org/@modifier_contenu
function modifier_contenu($type, $id, $options, $c=false, $serveur='') {
	if (!$id = intval($id)) {
		spip_log('Erreur $id non defini', 'warn');
		return false;
	}

	include_spip('inc/filtres');

	$table_objet = table_objet($type);
	$spip_table_objet = table_objet_sql($type);
	$id_table_objet = id_table_objet($type);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_objet, $serveur);

	// Appels incomplets (sans $c)
	if (!is_array($c)) {
		spip_log('erreur appel modifier_contenu('.$type.'), manque $c');
		return false;
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
	if (is_array($options['nonvide']))
	foreach ($options['nonvide'] as $champ => $sinon)
		if ($c[$champ] === '')
			$c[$champ] = $sinon;


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
				'type' =>$type,
				'id_objet' => $id,
				'champs' => $options['champs'],
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
		signale_edition ($id, $GLOBALS['visiteur_session'], $type);
	}

	// Verifier si les mises a jour sont pertinentes, datees, en conflit etc
	include_spip('inc/editer');
	$conflits = controler_md5($champs, $_POST, $type, $id, $serveur);

	if ($champs) {

		// la modif peut avoir lieu

		// faut-il ajouter date_modif ?
		if ($options['date_modif']
		AND !isset($champs[$options['date_modif']]))
			$champs[$options['date_modif']] = date('Y-m-d H:i:s');

		// allez on commit la modif
		sql_updateq($spip_table_objet, $champs, "$id_table_objet=$id", $serveur);

		// on verifie si elle est bien passee
		// pour detecter le cas ou un caractere illicite a ete utilise dans un champ texte
		// et provoque la troncature du champ lors de l'enregistrement
		$moof = sql_fetsel(array_keys($champs), $spip_table_objet, "$id_table_objet=$id", array(), array(), '', array(), $serveur);
		if ($moof != $champs) {
			foreach($moof as $k=>$v) {
				if ($v !== $champs[$k]
					// ne pas alerter si le champ est d'un type numerique ou date
				  // car c'est surement un cast sql, tout a fait normal
				  // sinon cela provoque des fausses alertes a la moindre saisie vide
				  // ou n'ayant pas la bonne resolution numerique ou le bon format
					AND (!preg_match(',(int|float|double|date|time|year|enum|decimal),',$desc['field'][$k]))
					) {
					$conflits[$k]['post'] = $champs[$k];
					$conflits[$k]['save'] = $v;
				}
			}
		}


		// Cas particulier des groupes de mots dont le titre est repris
		// dans la table spip_mots
		if ($spip_table_objet == 'spip_groupes_mots'
		AND isset($champs['titre']))
			sql_updateq('spip_mots', array('type' => $champs['titre']),
			'id_groupe='.$id);

		// Invalider les caches
		if ($options['invalideur']) {
			include_spip('inc/invalideur');
			suivre_invalideur($options['invalideur']);
		}

		if (!in_array($type,array('forum','signature'))) {
			// marquer les documents vus dans le texte si il y a lieu
			include_spip('base/auxiliaires');
			marquer_doublons_documents($champs,$id,$type,$id_table_objet,$table_objet,$spip_table_objet, $desc, $serveur);
		}

		// Notifications, gestion des revisions...
		// appelle |enregistrer_nouvelle_revision @inc/revisions
		pipeline('post_edition',
			array(
				'args' => array(
					'table' => $spip_table_objet,
					'table_objet' => $table_objet,
					'spip_table_objet' => $spip_table_objet,
					'type' =>$type,
					'id_objet' => $id,
					'champs' => $options['champs'],
					'serveur' => $serveur,
					'action' => 'modifier'
				),
				'data' => $champs
			)
		);
	}
	
	// S'il y a un conflit, prevenir l'auteur de faire un copier/coller
	if ($conflits) {
		$redirect = url_absolue(
			parametre_url(rawurldecode(_request('redirect')), $id_table_objet, $id)
		);
		signaler_conflits_edition($conflits, $redirect);
		exit;
	}

	return true;
}

// http://doc.spip.org/@marquer_doublons_documents
function marquer_doublons_documents($champs,$id,$type,$id_table_objet,$table_objet,$spip_table_objet, $desc=array(), $serveur=''){
	if (!isset($champs['texte']) AND !isset($champs['chapo'])) return;
	if (!$desc){
		$trouver_table = charger_fonction('trouver_table', 'base');
		$desc = $trouver_table($table_objet, $serveur);
	}
	$load = "";

	// charger le champ manquant en cas de modif partielle de l'objet
	// seulement si le champ existe dans la table demande
	if (!isset($champs['texte']) && isset($desc['field']['texte'])) $load = 'texte';
	if (!isset($champs['chapo']) && isset($desc['field']['chapo'])) $load = 'chapo';
	if ($load){
		$champs[$load] = "";
		$row = sql_fetsel($load, $spip_table_objet, "$id_table_objet=".sql_quote($id));
		if ($row AND isset($row[$load]))
			$champs[$load] = $row[$load];
	}
	include_spip('inc/texte');
	include_spip('base/abstract_sql');
	$GLOBALS['doublons_documents_inclus'] = array();
	traiter_modeles($champs['chapo'].$champs['texte'],true); // detecter les doublons
	sql_updateq("spip_documents_liens", array("vu" => 'non'), "id_objet=$id AND objet=".sql_quote($type));
	if (count($GLOBALS['doublons_documents_inclus'])){
		// on repasse par une requete sur spip_documents pour verifier que les documents existent bien !
		$in_liste = sql_in('id_document',
			$GLOBALS['doublons_documents_inclus']);
		$res = sql_select("id_document", "spip_documents", $in_liste);
		while ($row = sql_fetch($res)) {
			// Mettre le lien a jour ou le creer s'il n'existe pas deja
			if (!sql_updateq("spip_documents_liens", array("vu" => 'oui'), "id_objet=$id AND objet=".sql_quote($type)." AND id_document=".$row['id_document']) OR
			!sql_getfetsel("id_document", "spip_documents_liens", "id_document=".$row['id_document']." AND id_objet=$id AND objet=".sql_quote($type))) {
				sql_insertq("spip_documents_liens", array('id_objet' => $id, 'objet' => $type, 'id_document' => $row['id_document'], 'vu' => 'oui'));
				pipeline('post_edition',
					array(
						'args' => array(
							'operation' => 'lier_document',
							'table' => 'spip_documents',
							'id_objet' => $row['id_document'],
							'objet' => $type,
							'id' => $id
						),
						'data' => null
					)
				);
			}
		}
	}
}

// Enregistre une revision d'article
// http://doc.spip.org/@revision_article
function revision_article ($id_article, $c=false) {

	// Si l'article est publie, invalider les caches et demander sa reindexation
	$t = sql_getfetsel("statut", "spip_articles", "id_article=$id_article");
	if ($t == 'publie') {
		$invalideur = "id='id_article/$id_article'";
		$indexation = true;
	}

	modifier_contenu('article', $id_article,
		array(
			'nonvide' => array('titre' => _T('info_sans_titre')),
			'invalideur' => $invalideur,
			'indexation' => $indexation,
			'date_modif' => 'date_modif' // champ a mettre a date('Y-m-d H:i:s') s'il y a modif
		),
		$c);

	return ''; // pas d'erreur
}

// http://doc.spip.org/@revision_document
function revision_document($id_document, $c=false) {

	return modifier_contenu('document', $id_document,
		array(
			// 'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c);
}

// http://doc.spip.org/@revision_signature
function revision_signature($id_signature, $c=false) {

	return modifier_contenu('signature', $id_signature,
		array(
			'nonvide' => array('nom_email' => _T('info_sans_titre'))
		),
		$c);
}


// http://doc.spip.org/@revision_auteur
function revision_auteur($id_auteur, $c=false) {

	$r = modifier_contenu('auteur', $id_auteur,
		array(
			'nonvide' => array('nom' => _T('ecrire:item_nouvel_auteur'))
		),
		$c);

	// .. mettre a jour les fichiers .htpasswd et .htpasswd-admin
	if (isset($c['login'])
	OR isset($c['pass'])
	OR isset($c['statut'])
	) {
		include_spip('inc/acces');
		ecrire_acces();
	}

	// .. mettre a jour les sessions de cet auteur
	include_spip('inc/session');
	$c['id_auteur'] = $id_auteur;
	actualiser_sessions($c);
}


// http://doc.spip.org/@revision_mot
function revision_mot($id_mot, $c=false) {

	// regler le groupe
	if (isset($c['id_groupe']) OR isset($c['type'])) {
		$row = sql_fetsel("titre", "spip_groupes_mots", "id_groupe=".intval($c['id_groupe']));
		if ($row)
			$c['type'] = $row['titre'];
		else
			unset($c['type']);
	}

	modifier_contenu('mot', $id_mot,
		array(
			'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c);
}

// http://doc.spip.org/@revision_groupe_mot
function revision_groupe_mot($id_groupe, $c=false) {

	modifier_contenu('groupe_mot', $id_groupe,
		array(
			'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c);
}

// http://doc.spip.org/@revision_petition
function revision_petition($id_article, $c=false) {

	modifier_contenu('petition', $id_article,
		array(),
		$c);
}


// Nota: quand on edite un forum existant, il est de bon ton d'appeler
// au prealable conserver_original($id_forum)
// http://doc.spip.org/@revision_forum
function revision_forum($id_forum, $c=false) {

	$t = sql_fetsel("*", "spip_forum", "id_forum=".sql_quote($id_forum));
	if (!$t) {
		spip_log("erreur forum $id_forum inexistant");
		return;
	}

	// Calculer l'invalideur des caches lies a ce forum
	if ($t['statut'] == 'publie') {
		include_spip('inc/invalideur');
		$invalideur = "id='id_forum/"
			. calcul_index_forum(
				$t['id_article'],
				$t['id_breve'],
				$t['id_rubrique'],
				$t['id_syndic']
			)
			. "'";
	} else
		$invalideur = '';

	// Supprimer 'http://' tout seul
	if (isset($c['url_site'])) {
		include_spip('inc/filtres');
		$c['url_site'] = vider_url($c['url_site'], false);
	}

	$r = modifier_contenu('forum', $id_forum,
		array(
			'nonvide' => array('titre' => _T('info_sans_titre')),
			'invalideur' => $invalideur
		),
		$c);

	$id_thread = $t["id_thread"];
	$cles = array();
	foreach (array('id_article', 'id_rubrique', 'id_syndic', 'id_breve')
		 as $k) {
		if (isset($c[$k])) $cles[$k] = $c[$k];
	}

	// Modification des id_article etc
	// (non autorise en standard mais utile pour des crayons)
	// on deplace tout le thread {sauf les originaux}.
	if ($cles) {
		sql_updateq("spip_forum", $cles, "id_thread=$id_thread AND statut!='original'");
		// on n'affecte pas $r, car un deplacement ne change pas l'auteur
	}

	// s'il y a vraiment eu une modif, on
	// enregistre le nouveau date_thread,
	// si le message est bien publie ou si c'est un thread non public
	if ($r AND
		($t['statut'] == 'publie' OR !sql_countsel("spip_forum", "statut='publie' AND id_thread=".intval($id_thread)))) {
		// on ne stocke ni le numero IP courant ni le nouvel id_auteur
		// dans le message modifie (trop penible a l'usage) ; mais du
		// coup attention a la responsabilite editoriale
		/*
		sql_updateq('spip_forum', array('ip'=>($GLOBALS['ip']), 'id_auteur'=>($GLOBALS['visiteur_session']['id_auteur'])),"id_forum=".sql_quote($id_forum));
		*/

		// & meme ca ca pourrait etre optionnel
		sql_updateq("spip_forum", array("date_thread" => date('Y-m-d H:i:s')), "id_thread=".intval($id_thread));
	}
}

?>
