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

}


// http://doc.spip.org/@revision_auteur
function revision_auteur($id_auteur, $c=false) {

	return modifier_contenu('auteur', $id_auteur,
		array(
			'champs' => array('nom', 'bio', 'pgp', 'nom_site', 'lien_site', 'email'),
			'nonvide' => array('nom' => _T('ecrire:item_nouvel_auteur'))
		),
		$c);

}

// Quand on edite un forum, on tient a conserver l'original
// sous forme d'un forum en reponse, de statut 'original'
function conserver_original($id_forum) {
	$s = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent="._q($id_forum)." AND statut='original'");

	if (spip_num_rows($s))
		return true;

	// recopier le forum
	$t = spip_fetch_array(
		spip_query("SELECT date_heure,titre,texte,auteur,email_auteur,nom_site,url_site,ip,id_auteur,idx,id_thread FROM spip_forum WHERE id_forum="._q($id_forum))
	);

	if ($t
	AND spip_query("INSERT spip_forum (date_heure,titre,texte,auteur,email_auteur,nom_site,url_site,ip,id_auteur,idx,id_thread) VALUES (".join(',',array_map('_q', $t)).")")) {
		$id_copie = spip_insert_id();
		spip_query("UPDATE spip_forum SET id_parent="._q($id_forum).", statut='original' WHERE id_forum=$id_copie");
		return true;
	}

	return false;
}

// http://doc.spip.org/@revision_auteur
function revision_forum($id_forum, $c=false) {

	if (!conserver_original($id_forum)) {
		spip_log("erreur de sauvegarde de l'original");
		return;
	}

	$r = modifier_contenu('forum', $id_forum,
		array(
			'champs' => array('titre', 'texte', 'auteur', 'email_auteur', 'nom_site', 'url_site', 'ip'),
			'nonvide' => array('titre' => _T('info_sans_titre'))
		),
		$c);

	// s'il y a vraiment eu une modif, on stocke le numero IP courant
	// ainsi que le nouvel id_auteur dans le message modifie
	if ($r) {
		spip_query("UPDATE spip_forum SET ip="._q($GLOBALS['ip']).", id_auteur="._q($GLOBALS['auteur_session']['id_auteur'])." WHERE id_forum="._q($id_forum));
	}

	return $r;
}


?>
