<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


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
	// car elles ne relevent pas de autoriser(article, modifier) ;
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

	// recuperer les extras
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		if ($extra = extra_update($table_objet, $id, $champs))
			$champs['extra'] = $extra;
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => $spip_table_objet,
				'id_objet' => $id,
				'champs' => $options['champs']
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


	$champs = array_map('sql_quote', $champs);

	// On veut savoir si notre modif va avoir un impact ; en mysql
	// on pourrait employer mysql_affected_rows() mais pas en multi-base
	// donc on fait autrement, avec verification prealable
	$verifier = array();
	foreach ($champs as $ch => $val)
		$verifier[] = "($ch IS NULL OR $ch!=$val)";
	if (!sql_countsel($spip_table_objet, "($id_table_objet=$id) AND (" . join(' OR ',$verifier). ")",
	null,null,null,$serveur))
		return false;

	// la modif peut avoir lieu

	// faut-il ajouter date_modif ?
	if ($options['date_modif']
	AND !isset($champs[$options['date_modif']]))
		$champs[$options['date_modif']] = 'NOW()';

	// allez on commit la modif
	sql_update($spip_table_objet, $champs, "$id_table_objet=$id", $serveur);

	// Invalider les caches
	if ($options['invalideur']) {
		include_spip('inc/invalideur');
		suivre_invalideur($options['invalideur']);
	}

	// marquer les documents vus dans le texte si il y a lieu
	include_spip('base/auxiliaires');
	if (isset($GLOBALS['tables_auxiliaires']["spip_documents_$table_objet"]["field"]["vu"]))
		marquer_doublons_documents($champs,$id,$id_table_objet,$table_objet);

	// Notifications, gestion des revisions...
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => $spip_table_objet,
				'id_objet' => $id,
				'champs' => $options['champs']
			),
			'data' => $champs
		)
	);

	return true;
}

// http://doc.spip.org/@marquer_doublons_documents
function marquer_doublons_documents($champs,$id,$id_table_objet,$table_objet){
	if (!isset($champs['texte']) AND !isset($champs['chapo'])) return;
	$load = "";

	// charger le champ manquant en cas de modif partielle de l'objet
	if (!isset($champs['texte'])) $load = 'texte';
	if (!isset($champs['chapo'])) $load = 'chapo';
	if ($load){
		$champs[$load] = "";
		$res = sql_select("$load", $spip_table_objet, "$id_table_objet=".sql_quote($id));
		if ($row = sql_fetch($res) AND isset($row[$load]))
			$champs[$load] = $row[$load];
	}
	include_spip('inc/texte');
	include_spip('base/abstract_sql');
	$GLOBALS['doublons_documents_inclus'] = array();
	traiter_modeles($champs['chapo'].$champs['texte'],true); // detecter les doublons
	sql_updateq("spip_documents_$table_objet", array("vu" => 'non'), "$id_table_objet=$id");
	if (count($GLOBALS['doublons_documents_inclus'])){
		// on repasse par une requete sur spip_documents pour verifier que les documents existent bien !
		$in_liste = sql_in('id_document',
			$GLOBALS['doublons_documents_inclus']);
		$res = sql_select("id_document", "spip_documents", $in_liste);
		while ($row = sql_fetch($res)) {
			sql_updateq("spip_documents_$table_objet", array("vu" => 'oui'), "$id_table_objet=$id AND id_document=" . $row['id_document']);
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
			'date_modif' => 'date_modif' // champ a mettre a NOW() s'il y a modif
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
		$result = sql_select("titre", "spip_groupes_mots", "id_groupe=".intval($id_groupe));
		if ($row = sql_fetch($result))
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

	$s = sql_select("*", "spip_forum", "id_forum=".sql_quote($id_forum));
	if (!$t = sql_fetch($s)) {
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


	// Modification des id_article etc
	// (non autorise en standard mais utile pour des crayons)
	// on deplace tout le thread {sauf les originaux}.
	if (count($cles = array_intersect(array_keys($c),
		array('id_article', 'id_rubrique', 'id_syndic', 'id_breve')))
	) {
		$thread = sql_fetsel("id_thread", "spip_forum", "id_forum=$id_forum");
		foreach ($cles as $k)
			sql_updateq("spip_forum", array("$k" => $c[$k]), "id_thread=".$thread['id_thread']." AND statut!='original'");
		// on n'affecte pas $r, car un deplacement ne change pas l'auteur
	}

	// s'il y a vraiment eu une modif, on stocke le numero IP courant
	// ainsi que le nouvel id_auteur dans le message modifie ; et on
	// enregistre le nouveau date_thread
	if ($r) {
		sql_updateq('spip_forum', array('ip'=>($GLOBALS['ip']), 'id_auteur'=>($GLOBALS['visiteur_session']['id_auteur'])),"id_forum=".sql_quote($id_forum));

		sql_update("spip_forum", array("date_thread" => "NOW()"), "id_thread=".$t['id_thread']);
	}
}


// pipeline appelant la fonction de sauvegarde de la premiere revision
// d'un article avant chaque modification de contenu
// http://doc.spip.org/@premiere_revision
function premiere_revision($x) {
	// Stockage des versions : creer une premiere version si non-existante
	if  ($GLOBALS['meta']["articles_versions"]=='oui') {
		include_spip('inc/revisions');
		$x = enregistrer_premiere_revision($x);
	}
	return $x;
}

// pipeline appelant la fonction de sauvegarde de la nouvelle revision
// d'un article apres chaque modification de contenu
// http://doc.spip.org/@nouvelle_revision
function nouvelle_revision($x) {
	// Stockage des versions : creer une premiere version si non-existante
	if  ($GLOBALS['meta']["articles_versions"]=='oui') {
		include_spip('inc/revisions');
		$x = enregistrer_nouvelle_revision($x);
	}
	return $x;
}

?>
