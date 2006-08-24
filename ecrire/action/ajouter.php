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

include_spip('base/abstract_sql');

// http://doc.spip.org/@action_ajouter_dist
function action_ajouter_dist() {
	
	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');
	$redirect = _request('redirect');

	if (preg_match(",^\W*(\d+)\W+(\d+)$,", $arg, $r)) {
		ajouter_auteur_et_rediriger($r[1], $r[2], $redirect);
	}
	if (preg_match(",^\W*(\d+)$,", $arg, $r)) {
		if  ($nouv_auteur = intval($_POST['nouv_auteur'])) {
		  ajouter_auteur_et_rediriger($r[1], $nouv_auteur, $redirect);
		} else if ($cherche = $_POST['cherche_auteur']) {
			$res = rechercher_auteurs($cherche);
			$n = count($res);
			if ($n == 1)
			# Bingo. Signaler le choix fait.
				ajouter_auteur_et_rediriger($r[1], $res[0], "$redirect&ids=" . $res[0] . "&cherche_auteur=" . $res[0]);
			# Trop vague. Le signaler.
			elseif ($n > 16)
				redirige_par_entete("$redirect&cherche_auteur=$cherche&ids=-1");
			elseif (!$n)
			# Recherche vide (mais faite). Le signaler 
				redirige_par_entete("$redirect&cherche_auteur=$cherche&ids=" );
			else
			# renvoyer un formulaire de choix
				redirige_par_entete("$redirect&cherche_auteur=$cherche&ids=" . join(',',$res));

		}
	} else spip_log("ajouter $arg pas compris");
}

// http://doc.spip.org/@ajouter_auteur_et_rediriger
function ajouter_auteur_et_rediriger($id_article, $id_auteur, $redirect)
{
	$res = spip_query("SELECT id_article FROM spip_auteurs_articles WHERE id_auteur=" . $id_auteur . " AND id_article=" . $id_article);
	if (!spip_num_rows($res))
		spip_abstract_insert('spip_auteurs_articles', "(id_auteur,id_article)", "($id_auteur,$id_article)");

	if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
		include_spip("inc/indexation");
		marquer_indexer('article', $id_article);
	}

	if ($redirect) redirige_par_entete($redirect);
	exit;
}

// http://doc.spip.org/@rechercher_auteurs
function rechercher_auteurs($cherche_auteur)
{
	include_spip('inc/mots');
	include_spip('inc/charsets'); // pour tranlitteration
	$result = spip_query("SELECT id_auteur, nom FROM spip_auteurs");
	$table_auteurs = array();
	$table_ids = array();
	while ($row = spip_fetch_array($result)) {
		$table_auteurs[] = $row["nom"];
		$table_ids[] = $row["id_auteur"];
	}
	return mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
}

?>
