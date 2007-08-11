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
	$resyndiquer = false;

	if (preg_match(',options/(\d+),',$arg, $r)) {
		$id_syndic = $r[1];
		$resyndiquer = editer_site_options($id_syndic);
	// Envoi depuis le formulaire d'edition d'un site existant
	} else if ($id_syndic = intval($arg)) {

		// reload si on change une des valeurs de syndication
		if (
		(_request('url_syndic') OR _request('resume') OR _request('syndication'))
		AND $s = spip_query("SELECT url_syndic,syndication,resume FROM spip_syndic WHERE id_syndic="._q($id_syndic))
		AND $t = spip_abstract_fetch($s)
		AND (
			(_request('url_syndic') AND _request('url_syndic') != $t['url_syndic'])
			OR
			(_request('syndication') AND _request('syndication') != $t['syndication'])
			OR
			(_request('resume') AND _request('resume') != $t['resume'])
			)
		)
			set_request('reload', 'oui');

		revisions_sites($id_syndic);
	
	// Envoi depuis le formulaire de creation d'un site
	} else if ($arg == 'oui') {
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

			// Enregistrer le logo s'il existe
			if ($auto['logo'] AND $auto['format_logo'])
				@rename($auto['logo'],
				_DIR_IMG . 'siteon'.$id_syndic.'.'.$auto['format_logo']);
		}
		else
			redirige_par_entete(
				generer_url_ecrire('sites_edit', 'id_rubrique='._request('id_parent'),'&')
			);
	}
	// Erreur
	else {
		redirige_par_entete(generer_url_ecrire());
	}

	// Re-syndiquer le site
	if (_request('reload') == 'oui') {
		// Effacer les messages si on supprime la syndication
		if (_request('syndication') == 'non')
			spip_query("DELETE FROM spip_syndic_articles WHERE id_syndic="._q($id_syndic));

		$s = spip_query("SELECT id_syndic, descriptif FROM spip_syndic WHERE id_syndic=$id_syndic AND syndication IN ('oui', 'sus', 'off') LIMIT 1");
		if ($t = spip_abstract_fetch($s)) {

			// Si on n'a pas de descriptif ou pas de logo, on va le chercher
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if (!$logo = $chercher_logo($id_syndic, 'id_syndic', 'on')
			OR $t['descriptif'] == '') {
				$auto = analyser_site(_request('url_syndic'));
				revisions_sites($id_syndic,
					array('descriptif' => $auto['descriptif'])
				);
				if (!$logo
				AND $auto['logo'] AND $auto['format_logo'])
					@rename($auto['logo'],
					_DIR_IMG . 'siteon'.$id_syndic.'.'.$auto['format_logo']);
			}
			$resyndiquer = true;
		}
	}

	if ($resyndiquer) {
	  // ah si PHP connaisait les fermetures...
	  // Cette globale est utilisee exclusivement dans la fct suivante.
		$GLOBALS['cron_syndic_now'] = $id_syndic;
		// forcer l'execution immediate de cette tache
		// (i.e. appeler la fct suivante avec gestion du verrou)
		cron(true, array('syndic' => -91));
	}
	// Rediriger le navigateur
	$redirect = parametre_url(urldecode(_request('redirect')),
		'id_syndic', $id_syndic, '&');
	redirige_par_entete($redirect);
}

// Cette fonction redefinit la tache standard de syndication
// pour la forcer a syndiquer le site dans la globale cron_syndic_now

// http://doc.spip.org/@cron_syndic
function cron_syndic($t) {
	include_spip('cron/syndic');
	$t = syndic_a_jour($GLOBALS['cron_syndic_now']);
	return $t ? 0 : $GLOBALS['cron_syndic_now'];
}

// http://doc.spip.org/@insert_syndic
function insert_syndic($id_rubrique) {

	include_spip('base/abstract_sql');
	include_spip('inc/rubriques');

	// Si id_rubrique vaut 0 ou n'est pas definie, creer le site
	// dans la premiere rubrique racine
	if (!$id_rubrique = intval($id_rubrique)) {
		$row = spip_abstract_fetch(spip_abstract_select("id_rubrique", "spip_rubriques", "id_parent=0",'', '0+titre,titre', "1"));
		$id_rubrique = $row['id_rubrique'];
	}


	// Le secteur a la creation : c'est le secteur de la rubrique
	$row = spip_abstract_fetch(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
	$id_secteur = $row['id_secteur'];

	$id_syndic = spip_abstract_insert("spip_syndic",
		"(id_rubrique, id_secteur, statut, date)",
		"($id_rubrique, $id_secteur, 'prop', NOW())");

	return $id_syndic;
}


// Enregistre une revision de syndic
// $new indique si c'est un INSERT
// $c est un contenu (par defaut on prend le contenu via _request())
// http://doc.spip.org/@revisions_sites
function revisions_sites ($id_syndic, $c=false) {

	include_spip('inc/filtres');
	include_spip('inc/autoriser');
	include_spip('inc/rubriques');

	$id_auteur = isset($c['id_auteur']) ? $c['id_auteur'] : NULL;   

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

	$s = spip_query("SELECT statut, id_rubrique FROM spip_syndic WHERE id_syndic=$id_syndic");
	$row = spip_abstract_fetch($s);
	$id_rubrique = $row['id_rubrique'];
	$statut_ancien = $row['statut'];

	$statut = _request('statut', $c);

	if ($statut
	AND $statut != $statut_ancien
	AND autoriser('publierdans','rubrique',$id_rubrique, $id_auteur)) {
		$champs['statut'] = $statut;
		if ($statut == 'publie') {
			if ($d = _request('date', $c)) {
				$champs['date'] = $d;
			} else {
				# on prend la date de MySQL pour eviter un decalage cf. #975
				$d = spip_query("SELECT NOW() AS d");
				$d = spip_abstract_fetch($d);
				$champs['date'] = $d['d'];
			}
		}
	} else
		$statut = $statut_ancien;

	// Changer de rubrique ?
	// Verifier que la rubrique demandee est differente
	// de la rubrique actuelle
	if ($id_parent = intval(_request('id_parent', $c))
	AND $id_parent != $id_rubrique
	AND (spip_abstract_fetch(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_rubrique=$id_parent")))) {
		$champs['id_rubrique'] = $id_parent;

		// si le site est publie
		// et que le demandeur n'est pas admin de la rubrique
		// repasser le site en statut 'prop'.
		if ($statut == 'publie') {
			if (!autoriser('publierdans','rubrique',$id_parent, $id_auteur))
				$champs['statut'] = $statut = 'prop';
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

		$row = spip_abstract_fetch(spip_query("SELECT lang, langue_choisie FROM spip_syndic WHERE id_syndic=$id_syndic"));
		$langue_old = $row['lang'];
		$langue_choisie_old = $row['langue_choisie'];

		if ($langue_choisie_old != "oui") {
			$row = spip_abstract_fetch(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
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

	calculer_rubriques_if($id_rubrique, $champs, $statut_ancien);

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
			$result['nom_site'] = supprimer_tags(filtrer_entites(trim($r[1])));
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
			$result['descriptif'] = supprimer_tags(filtrer_entites($r[3]));

		if (preg_match(',<image.*<url.*>(.*)</url>.*</image>,Uims',
		$header, $r)
		AND preg_match(',(https?://.*/.*(gif|png|jpg)),ims', $r[1], $r)
		AND $image = recuperer_infos_distantes($r[1])) {
			if (in_array($image['extension'], array('gif', 'jpg', 'png'))) {
				$result['format_logo'] = $image['extension'];
				$result['logo'] = $image['fichier'];
			}
			else if ($image['fichier']) {
				spip_unlink($image['fichier']);
			}
		}
	}
	else {
		$result['syndication'] = 'non';
		$result['url_site'] = $url;
		if (preg_match(',<head>(.*(description|title).*)</head>,Uims', $texte, $regs)) {
			$head = filtrer_entites($regs[1]);
		} else
			$head = $texte;
		if (preg_match(',<title[^>]*>(.*),i', $head, $regs))
			$result['nom_site'] = filtrer_entites(supprimer_tags(preg_replace(',</title>.*,i', '', $regs[1])));
		if (preg_match(',<meta[[:space:]]+(name|http\-equiv)[[:space:]]*=[[:space:]]*[\'"]?description[\'"]?[[:space:]]+(content|value)[[:space:]]*=[[:space:]]*[\'"]([^>]+)[\'"]>,Uims', $head, $regs))
			$result['descriptif'] = filtrer_entites(supprimer_tags($regs[3]));

		// Cherchons quand meme un backend
		include_spip('inc/distant');
		include_spip('inc/feedfinder');
		$feeds = get_feed_from_url($url, $texte);
		// si on a a trouve un (ou plusieurs) on le note avec select:
		// ce qui constitue un signal pour exec=sites qui proposera de choisir
		// si on syndique, et quelle url.
		if (count($feeds)>=1) {
			spip_log("feedfinder.php :\n".join("\n", $feeds));
			$result['url_syndic'] = "select: ".join(' ',$feeds);
		}
	}
	return $result;
}

// Enregistrre les options et retourne True s'il faut syndiquer.

// http://doc.spip.org/@editer_site_options
function editer_site_options($id_syndic)
{
	$moderation = _request('moderation');
	$miroir = _request('miroir');
	$oubli = _request('oubli');
	$resume = _request('resume');

	if ($moderation == 'oui' OR $moderation == 'non')
		spip_query("UPDATE spip_syndic SET moderation='$moderation' WHERE id_syndic=$id_syndic");
	if ($miroir == 'oui' OR $miroir == 'non')
		spip_query("UPDATE spip_syndic SET miroir='$miroir'	WHERE id_syndic=$id_syndic");
	if ($oubli == 'oui' OR $oubli == 'non')
		spip_query("UPDATE spip_syndic SET oubli='$oubli' WHERE id_syndic=$id_syndic");

	if (!($resume == 'oui' OR $resume == 'non')) return false;

	spip_query("UPDATE spip_syndic SET resume='$resume'	WHERE id_syndic=$id_syndic");
	return true;
}

?>
