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


// http://doc.spip.org/@action_editer_auteurs_dist
function action_editer_auteurs_dist() {
	
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	$redirect = urldecode(_request('redirect'));
	if ($script = _request('script'))
		$redirect = parametre_url($redirect,'script',$script,'&');
	if ($titre = _request('titre'))
		$redirect = parametre_url($redirect,'titre',$titre,'&');

	if (preg_match(",^\W*(\d+)\W(\w*)\W-(\d+)$,", $arg, $r)) {
		supprimer_auteur_et_rediriger($r[2], $r[1], $r[3], parametre_url($redirect,'type',$r[2],'&'));
	}
	elseif (preg_match(",^\W*(\d+)\W(\w*)\W(\d+)$,", $arg, $r)) {
		ajouter_auteur_et_rediriger($r[2], $r[1], $r[3], parametre_url($redirect,'type',$r[2],'&'));
	}
	elseif (preg_match(",^\W*(\d+)\W(\w*)$,", $arg, $r)) {
		list(,$id, $type) = $r;
		$idom = "auteur_$type" . "_$id" . '_new';
		$nouv_auteur = intval(_request($idom));
		if  ($nouv_auteur) {
			ajouter_auteur_et_rediriger($type, $id, $nouv_auteur, parametre_url($redirect,'type',$type,'&'));
		} else if ($cherche = _request('cherche_auteur')) {
			if ($p = strpos($redirect, '#')) {
				$ancre = substr($redirect,$p);
				$redirect = substr($redirect,0,$p);
			} else $ancre ='';
			$redirect = parametre_url($redirect,'type',$type,'&');
			$res = rechercher_auteurs($cherche);
			$n = count($res);

			if ($n == 1)
			# Bingo. Signaler le choix fait.
				ajouter_auteur_et_rediriger($type, $id, $res[0], "$redirect&ids=" . $res[0] . "&cherche_auteur=" . rawurlencode($cherche) . $ancre);
			# Trop vague. Le signaler.
			elseif ($n > 16)
				redirige_par_entete("$redirect&cherche_auteur=$cherche&ids=-1" . $ancre);
			elseif (!$n)
			# Recherche vide (mais faite). Le signaler 
				redirige_par_entete("$redirect&cherche_auteur=$cherche&ids="  . $ancre);
			else
			# renvoyer un formulaire de choix
				redirige_par_entete("$redirect&cherche_auteur=$cherche&ids=" . join(',',$res)  . $ancre);

		} else {
			include_spip('inc/actions');
			ajax_retour("action_editer_auteur: $arg faux");exit;
		}
	} else spip_log("action_editer_auteur: $arg pas compris");
}

// http://doc.spip.org/@supprimer_auteur_et_rediriger
function supprimer_auteur_et_rediriger($type, $id, $id_auteur, $redirect)
{
	$jointure = table_jointure('auteur', $type);
	if (preg_match(',^[a-z]*$,',$type)){
		sql_delete("spip_{$jointure}", "id_auteur=".sql_quote($id_auteur)." AND id_{$type}=".sql_quote($id));

		// Notifications, gestion des revisions, reindexation...
		pipeline('post_edition',
			array(
				'args' => array(
					'operation' => 'supprimer_auteur',
					'table' => table_objet_sql($type),
					'id_objet' => $id
				),
				'data' => null
			)
		);
	}

	if ($redirect) redirige_par_entete($redirect);
}

// http://doc.spip.org/@ajouter_auteur_et_rediriger
function ajouter_auteur_et_rediriger($type, $id, $id_auteur, $redirect)
{
	$jointure = table_jointure('auteur', $type);
	if (preg_match(',^[a-z]*$,',$type)){
		$res = sql_fetsel("id_$type", "spip_{$jointure}", "id_auteur=" . sql_quote($id_auteur) . " AND id_{$type}=" . $id);
		if (!$res) {
			sql_insertq("spip_{$jointure}", 
				    array('id_auteur' => $id_auteur,
					  "id_$type" => $id));
		}
		// Notifications, gestion des revisions, reindexation...
		pipeline('post_edition',
			array(
				'args' => array(
					'operation' => 'ajouter_auteur',
					'table' => table_objet_sql($type),
					'id_objet' => $id
				),
				'data' => null
			)
		);
	}

	if ($redirect) redirige_par_entete($redirect);
}

// http://doc.spip.org/@rechercher_auteurs
function rechercher_auteurs($cherche_auteur)
{
	include_spip('inc/mots');
	include_spip('inc/charsets'); // pour tranlitteration
	$result = sql_select("id_auteur, nom", "spip_auteurs");
	$table_auteurs = array();
	$table_ids = array();
	while ($row = sql_fetch($result)) {
		$table_auteurs[] = $row["nom"];
		$table_ids[] = $row["id_auteur"];
	}
	return mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
}

?>
