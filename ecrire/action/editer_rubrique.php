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

include_spip('inc/actions');
include_spip('inc/rubriques');

// http://doc.spip.org/@action_editer_rubrique_dist
function action_editer_rubrique_dist() {

	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	if (!preg_match(";^(\d+),(\w*),(\d+)$;", $arg, $r)) {
		 spip_log("action_editer_rubrique_dist $arg pas compris");
	} else action_editer_rubrique_post($r);
}

// http://doc.spip.org/@action_editer_rubrique_post
function action_editer_rubrique_post($r)
{

	list($x, $old_parent, $new, $id_rubrique) = $r;
	$id_parent = intval(_request('id_parent'));
	if ($new == 'oui')
		$id_rubrique = enregistre_creer_naviguer($id_parent);

	enregistre_modifier_naviguer($id_rubrique,
				$id_parent,
				_request('titre'),
				_request('texte'),
				_request('descriptif'),
				$old_parent);

	calculer_rubriques();
	calculer_langues_rubriques();

	// invalider les caches marques de cette rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_rubrique/$id_rubrique'");

        $redirect = parametre_url(urldecode(_request('redirect')),
				  'id_rubrique', $id_rubrique, '&');
        redirige_par_entete($redirect);
}


// http://doc.spip.org/@enregistre_creer_naviguer
function enregistre_creer_naviguer($id_parent)
{
	include_spip('base/abstract_sql');
	return spip_abstract_insert("spip_rubriques", 
			"(titre, id_parent)",
			"('"._T('item_nouvelle_rubrique')."', '$id_parent')");
}

// http://doc.spip.org/@enregistre_modifier_naviguer
function enregistre_modifier_naviguer($id_rubrique, $id_parent, $titre, $texte, $descriptif, $old_parent=0)
{
	// interdiction de deplacer vers ou a partir d'une rubrique
	// qu'on n'administre pas.

	$parent = '';
	if ($id_parent != $old_parent)	  {
		include_spip('inc/auth');
		$r = auth_rubrique($GLOBALS['auteur_session']['id_auteur'], $GLOBALS['auteur_session']['statut']);

		if (is_int($r)
		OR (is_array($r)
			AND $r[$id_parent]
			AND (!$old_parent OR $r[$old_parent])))
			$parent = "id_parent=" . intval($id_parent) . ", ";
		else {
			spip_log("deplacement de $id_rubrique vers $id_parent refuse a " . $GLOBALS['auteur_session']['id_auteur'] . ' '.  $GLOBALS['auteur_session']['statut']);
			$id_parent = '';
		}
	}

	// si c'est une rubrique-secteur contenant des breves, ne deplacer
	// que si $confirme_deplace == 'oui', et changer l'id_rubrique des
	// breves en question
	if (_request('confirme_deplace') == 'oui'
	AND $parent) {
		$id_secteur = spip_fetch_array(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique=$id_parent"));
		if ($id_secteur= $id_secteur['id_secteur'])
			spip_query("UPDATE spip_breves	SET id_rubrique=$id_secteur	WHERE id_rubrique=$id_rubrique");
	} else
		$parent = '';

	if ($id_parent == $id_rubrique) $parent = ''; // au fou

	if (_request('champs_extra')) {
			include_spip('inc/extra');
			$extra = extra_recup_saisie("rubriques");
	}
	else $extra = '';

	spip_query("UPDATE spip_rubriques SET " . $parent . "titre=" . spip_abstract_quote($titre) . ", descriptif=" . spip_abstract_quote($descriptif) . ", texte=" . spip_abstract_quote($texte) . (!$extra ? '' :  ", extra = " . spip_abstract_quote($extra) . "") . " WHERE id_rubrique=$id_rubrique");


	if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_spip("inc/indexation");
			marquer_indexer('spip_rubriques', $id_rubrique);
	}
	propager_les_secteurs();
}

?>
