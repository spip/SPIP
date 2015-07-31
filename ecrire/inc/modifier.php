<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// Une fonction generique pour l'API de modification de contenu
// $options est un array() avec toutes les options
//
// Pour l'instant fonctionne pour les types :
//   article, auteur, document, forum
// renvoie false si rien n'a ete modifie, true sinon
//
// http://doc.spip.org/@modifier_contenu
function modifier_contenu($type, $id, $options, $c=false) {
	include_spip('inc/filtres');

	$table_objet = table_objet($type);
	$id_table_objet = id_table_objet($type);

	// Gerer les champs non vides
	if (is_array($options['nonvide']))
	foreach ($options['nonvide'] as $champ => $sinon) {
		if (_request($champ, $c) === '') {
			$c = set_request($champ, $sinon, $c);
		}
	}

	$champs = array();
	if (is_array($options['champs']))
	foreach ($options['champs'] as $champ) {
		$val = _request($champ, $c);
		if ($val !== NULL)
			$champs[$champ] = corriger_caracteres($val);
	}

	// recuperer les extras
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		if ($extra = extra_update($table_objet, $id, $c))
			$champs['extra'] = $extra;
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_'.$table_objet,
				'id_objet' => $id
			),
			'data' => $champs
		)
	);

	$update = array();
	foreach ($champs as $champ => $val)
		$update[] = $champ . '=' . _q($val);

	if (!count($update))
		return false;

	spip_query($q = "UPDATE spip_$table_objet SET ".join(', ',$update)." WHERE $id_table_objet=$id");


	// marquer le fait que l'objet est travaille par toto a telle date
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		signale_edition ($id, $GLOBALS['auteur_session'], $type);
	}

	// Invalider les caches
	if ($options['invalideur']) {
		include_spip('inc/invalideur');
		suivre_invalideur($options['invalideur']);
	}

	// Demander une reindexation
	if ($options['indexation']) {
		include_spip('inc/indexation');
		marquer_indexer('spip_'.$table_objet, $id);
	}

	// Notifications, gestion des revisions...
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_'.$table_objet,
				'id_objet' => $id
			),
			'data' => $champs
		)
	);

	return true;
}

// http://doc.spip.org/@revision_document
function revision_document($id_document, $c=false) {

	return modifier_contenu('document', $id_document,
		array(
			'champs' => array('titre', 'descriptif')
			//,'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c);

	return ''; // pas d'erreur
}

// http://doc.spip.org/@revision_signature
function revision_signature($id_signature, $c=false) {

	return modifier_contenu('signature', $id_signature,
		array(
			'champs' => array('nom_email', 'ad_email', 'nom_site', 'url_site', 'message'),
			'nonvide' => array('nom_email' => _T('info_sans_titre'))
		),
		$c);

	return ''; // pas d'erreur
}


// http://doc.spip.org/@revision_auteur
function revision_auteur($id_auteur, $c=false) {

	modifier_contenu('auteur', $id_auteur,
		array(
			'champs' => array('nom', 'bio', 'pgp', 'nom_site', 'lien_site', 'email'),
			'nonvide' => array('nom' => _T('ecrire:item_nouvel_auteur'))
		),
		$c);
}


// http://doc.spip.org/@revision_mot
function revision_mot($id_mot, $c=false) {

	modifier_contenu('mot', $id_mot,
		array(
			'champs' => array('titre', 'descriptif', 'texte'),
			'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c);
}

// Nota: quand on edite un forum existant, il est de bon ton d'appeler
// au prealable conserver_original($id_forum)
// http://doc.spip.org/@revision_forum
function revision_forum($id_forum, $c=false) {

	$s = spip_query("SELECT * FROM spip_forum WHERE id_forum="._q($id_forum));
	if (!$t = spip_fetch_array($s)) {
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
	$u = _request('url_site', $c);
	if (isset($u))
		$c = set_request('url_site', vider_url($u, false));

	$r = modifier_contenu('forum', $id_forum,
		array(
			'champs' => array('titre', 'texte', 'auteur', 'email_auteur', 'nom_site', 'url_site'),
			'nonvide' => array('titre' => _T('info_sans_titre')),
			'invalideur' => $invalideur
		),
		$c);

	// s'il y a vraiment eu une modif, on stocke le numero IP courant
	// ainsi que le nouvel id_auteur dans le message modifie ;
	if ($r) {
		spip_query("UPDATE spip_forum SET ip="._q($GLOBALS['ip']).", id_auteur="._q($GLOBALS['auteur_session']['id_auteur'])." WHERE id_forum="._q($id_forum));
	}
}


// pipeline appelant la fonction de sauvegarde de la premiere revision
// d'un article avant chaque modification de contenu
// http://doc.spip.org/@premiere_revision
function premiere_revision($x) {
	// Stockage des versions : creer une premiere version si non-existante
	if  ($GLOBALS['flag_revisions']
	AND $GLOBALS['meta']["articles_versions"]=='oui') {
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
	if  ($GLOBALS['flag_revisions']
	AND $GLOBALS['meta']["articles_versions"]=='oui') {
		include_spip('inc/revisions');
		$x = enregistrer_nouvelle_revision($x);
	}
	return $x;
}

?>
