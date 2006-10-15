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
include_spip('inc/presentation');
include_spip('inc/acces');
include_spip('inc/action');

function exec_legender_auteur_dist()
{
  global $id_auteur, $retour, $echec, $initial,
	  $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;

	$id_auteur = intval($id_auteur);

	$auteur = spip_fetch_array(spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur"));

	if (!$auteur) {
                gros_titre(_T('info_acces_interdit'));
                exit;
        }

	if (!$echec AND $retour) {
		redirige_par_entete(rawurldecode($retour));
		exit;
	}

	$legender_auteur = charger_fonction('legender_auteur', 'inc');

	ajax_retour($legender_auteur($id_auteur, $auteur, $initial, $echec, $retour));
}
?>
