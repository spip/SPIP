<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// http://doc.spip.org/@action_editer_site_dist
function action_editer_site_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// Envoi depuis le formulaire d'edition d'un site existant
	if ($id_syndic = intval($arg)) {

		// reload si on change une des valeurs de syndication
		if (
		(_request('url_syndic') OR _request('syndication')  OR _request('resume') OR _request('syndication'))
		AND $s = spip_query("SELECT url_syndic,syndication,resume FROM spip_syndic WHERE id_syndic="._q($id_syndic))
		AND $t = spip_fetch_array($s)
		AND (
			(_request('url_syndic') AND _request('url_syndic') != $t['url_syndic'])
			OR
			(_request('syndication') AND _request('syndication') != $t['syndication'])
			OR
			(_request('resume') AND _request('resume') != $t['resume'])
			))
			set_request('reload', 'oui');
		else if (_request('nouveau_statut'))
			spip_query("UPDATE spip_syndic SET statut="._q(_request('nouveau_statut'))." WHERE id_syndic=$id_syndic");

		revisions_sites($id_syndic);
	}
	// Envoi depuis le formulaire de creation d'un site
	else if ($arg == 'oui') {
		set_request('reload', 'oui');
		$id_syndic = insert_syndic(_request('id_parent'));
		revisions_sites($id_syndic);
	}
	// Envoi depuis le formulaire d'analyse automatique d'un site
	else if ($arg == 'auto') {
		if ($auto = analyser_site(_request('url'))) {
			$id_syndic = insert_syndic(_request('id_parent'));
			revisions_sites($id_syndic, $auto);
			if ($auto['syndication'] == 'oui')
				set_request('reload', 'oui');
		}
		else
			redirige_par_entete(
				generer_url_ecrire('sites_edit', 'id_rubrique='._request('id_parent'),'&')
			);
	}
	// Erreur
	else {
		redirige_par_entete('./');
	}

	// Re-syndiquer le site
	if (_request('reload') == 'oui') {
		// Effacer les messages si on supprime la syndication
		if (_request('syndication') == 'non')
			spip_query("DELETE FROM spip_syndic_articles WHERE id_syndic="._q($id_syndic));

		$s = spip_query("SELECT id_syndic FROM spip_syndic WHERE id_syndic=$id_syndic AND syndication IN ('oui', 'sus', 'off') LIMIT 1");
		if (spip_num_rows($s)) {
			include_spip('inc/syndic');
			syndic_a_jour($id_syndic);
		}
	}

	// Rediriger le navigateur
	$redirect = parametre_url(urldecode(_request('redirect')),
		'id_syndic', $id_syndic, '&');
	redirige_par_entete($redirect);
}

// http://doc.spip.org/@insert_syndic
function insert_syndic($id_rubrique) {

	include_spip('base/abstract_sql');
	include_spip('inc/rubriques');

	// Si id_rubrique vaut 0 ou n'est pas definie, creer le site
	// dans la premiere rubrique racine
	if (!$id_rubrique = intval($id_rubrique)) {
		$row = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0 ORDER by 0+titre,titre LIMIT 1"));
		$id_rubrique = $row['id_rubrique'];
	}

	/* pas de langue pour les sites

	// La langue a la creation : c'est la langue de la rubrique
	$row = spip_fetch_array(spip_query("SELECT lang, id_secteur FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
	$choisie = 'non';
	$lang = $row['lang'];
	$id_rubrique = $row['id_secteur']; // garantir la racine
	*/

	$id_syndic = spip_abstract_insert("spip_syndic",
		"(id_rubrique, statut, date)",
		"($id_rubrique, 'prop', NOW())");

	return $id_syndic;
}


// Enregistre une revision de syndic
// $new indique si c'est un INSERT
// $c est un contenu (par defaut on prend le contenu via _request())
// http://doc.spip.org/@revisions_sites
function revisions_sites ($id_syndic, $c=false) {

	include_spip('inc/filtres');
	include_spip('inc/rubriques');

	// Ces champs seront pris nom pour nom (_POST[x] => spip_syndic.x)
	$champs_normaux = array('nom_site', 'url_site', 'descriptif', 'url_syndic', 'syndication', 'url_propre');

	// ne pas accepter de titre vide
	if (_request('nom_site', $c) === '')
		$c = set_request('nom_site', _T('ecrire:info_sans_titre'), $c);

	$champs = array();
	foreach ($champs_normaux as $champ) {
		$val = _request($champ, $c);
		if ($val !== NULL)
			$champs[$champ] = corriger_caracteres($val);
	}

	// Changer le statut du site ?
	include_spip('inc/auth');
	auth_rubrique($GLOBALS['auteur_session']['id_auteur'], $GLOBALS['auteur_session']['statut']);

	$s = spip_query("SELECT statut, id_rubrique FROM spip_syndic WHERE id_syndic=$id_syndic");
	$row = spip_fetch_array($s);
	$id_rubrique = $row['id_rubrique'];
	$statut = $row['statut'];

	if (_request('statut', $c)
	AND _request('statut', $c) != $statut
	AND acces_rubrique($id_rubrique)) {
		$statut = $champs['statut'] = _request('statut', $c);
	}

	// Changer de rubrique ?
	// Verifier que la rubrique demandee est differente
	// de la rubrique actuelle
	if ($id_parent = intval(_request('id_parent', $c))
	AND $id_parent != $id_rubrique
	AND (spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_rubrique=$id_parent")))) {
		$champs['id_rubrique'] = $id_parent;

		// si le site est publie
		// et que le demandeur n'est pas admin de la rubrique
		// repasser le site en statut 'prop'.
		if ($statut == 'publie') {
			if ($GLOBALS['auteur_session']['statut'] != '0minirezo')
				$champs['statut'] = $statut = 'prop';
			else {
				if (!acces_rubrique($id_parent))
					$champs['statut'] = $statut = 'prop';
			}
		}
	}

	// recuperer les extras
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		if ($extra = extra_update('syndic', $id_syndic, $c))
			$champs['extra'] = $extra;
	}

	// Envoyer aux plugins
	include_spip('inc/modifier'); # temporaire pour eviter un bug
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_syndic',
				'id_objet' => $id_syndic
			),
			'data' => $champs
		)
	);

	$update = array();
	foreach ($champs as $champ => $val)
		$update[] = $champ . '=' . _q($val);

	if (!count($update)) return;

	// Enregistrer les modifications
	spip_query("UPDATE spip_syndic SET ".join(', ',$update)." WHERE id_syndic=$id_syndic");

	// marquer le fait que le site est travaille par toto a telle date
	// une alerte sera donnee aux autres redacteurs sur exec=sites
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		signale_edition ($id_syndic, $GLOBALS['auteur_session'], 'syndic');
	}

	// Si on deplace le site
	// - propager les secteurs
	// - changer sa langue (si heritee)
	if (isset($champs['id_rubrique'])) {
		propager_les_secteurs();

		$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_syndic WHERE id_syndic=$id_syndic"));
		$langue_old = $row['lang'];
		$langue_choisie_old = $row['langue_choisie'];

		if ($langue_choisie_old != "oui") {
			$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
			$langue_new = $row['lang'];
			if ($langue_new != $langue_old)
				spip_query("UPDATE spip_syndic SET lang = '$langue_new' WHERE id_syndic = $id_syndic");
		}
	}

	//
	// Post-modifications
	//

	// Invalider les caches
	if ($statut == 'publie') {
		include_spip('inc/invalideur');
		suivre_invalideur("id='id_syndic/$id_syndic'");
	}

	// Demander une reindexation du site
	if ($statut == 'publie') {
		include_spip('inc/indexation');
		marquer_indexer('spip_syndic', $id_syndic);
	}

	// Recalculer les rubriques (statuts et dates) si l'on deplace
	// un site publie
	if ($statut == 'publie'
	AND isset($champ['id_rubrique'])) {
		calculer_rubriques();
	}

	// Notification ?
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_syndic',
				'id_objet' => $id_syndic
			),
			'data' => $champs
		)
	);
}


// http://doc.spip.org/@analyser_site
function analyser_site($url) {
	include_spip('inc/filtres'); # pour filtrer_entites()
	include_spip('inc/distant');

	// Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) $url = 'http://'.$url;

	$texte = recuperer_page($url, true);
	if (!$texte) return false;

	if (preg_match(',<(channel|feed)([:[:space:]][^>]*)?'
	.'>(.*)</\1>,ims', $texte, $regs)) {
		$result['syndication'] = 'oui';
		$result['url_syndic'] = $url;
		$channel = $regs[3];

		list($header) = preg_split(
		',<(entry|item)([:[:space:]][^>]*)?'.'>,Uims', $channel,2);
		if (preg_match(',<title[^>]*>(.*)</title>,Uims', $header, $r))
			$result['nom_site'] = supprimer_tags(filtrer_entites($r[1]));
		if (preg_match(
		',<link[^>]*[[:space:]]rel=["\']?alternate[^>]*>(.*)</link>,Uims',
		$header, $regs))
			$result['url_site'] = filtrer_entites($regs[1]);
		else if (preg_match(',<link[^>]*[[:space:]]rel=.alternate[^>]*>,Uims',
		$header, $regs))
			$result['url_site'] = filtrer_entites(extraire_attribut($regs[0], 'href'));
		else if (preg_match(',<link[^>]*>(.*)</link>,Uims', $header, $regs))
			$result['url_site'] = filtrer_entites($regs[1]);
		else if (preg_match(',<link[^>]*>,Uims', $header, $regs))
			$result['url_site'] = filtrer_entites(extraire_attribut($regs[0], 'href'));
		$result['url_site'] = url_absolue($result['url_site'], $url);

		if (preg_match(',<(description|tagline)([[:space:]][^>]*)?'
		.'>(.*)</\1>,Uims', $header, $r))
			$result['descriptif'] = filtrer_entites($r[3]);
	}
	else {
		$result['syndication'] = 'non';
		$result['url_site'] = $url;
		if (eregi('<head>(.*)', $texte, $regs))
			$head = filtrer_entites(eregi_replace('</head>.*', '', $regs[1]));
		else
			$head = $texte;
		if (eregi('<title[^>]*>(.*)', $head, $regs))
			$result['nom_site'] = filtrer_entites(supprimer_tags(eregi_replace('</title>.*', '', $regs[1])));
		if (eregi('<meta[[:space:]]+(name|http\-equiv)[[:space:]]*=[[:space:]]*[\'"]?description[\'"]?[[:space:]]+(content|value)[[:space:]]*=[[:space:]]*[\'"]([^>]+)[\'"]>', $head, $regs))
			$result['descriptif'] = filtrer_entites(supprimer_tags($regs[3]));

		// Cherchons quand meme un backend
		include_spip('inc/distant');
		include_spip('inc/feedfinder');
		$feeds = get_feed_from_url($url, $texte);
		if (count($feeds)>1) {
			spip_log("feedfinder.php :\n".join("\n", $feeds));
			$result['url_syndic'] = "select: ".join(' ',$feeds);
		} else
			$result['url_syndic'] = $feeds[0];
	}
	return $result;
}

?>
