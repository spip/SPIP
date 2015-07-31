<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@action_editer_signatures_dist
function action_editer_signatures_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!preg_match(",^(-?\d+)(A?)$,", $arg, $r)) {
		 spip_log("action_editer_signature_dist $arg pas compris");
	} else action_editer_signatures_post($r);
}

// mettre un signature a la poubelle
// ou l'en sortir
// ou relancer le signataire.

// http://doc.spip.org/@action_editer_signatures_post
function action_editer_signatures_post($r)
{
	$id = $id_article = intval($r[1]);
	if ($r[2] == 'A')
	  $id = 0;
	else $id_article = 0;

	if ($id < 0){
		$id = 0 - $id;
		sql_updateq("spip_signatures", array("statut" => 'poubelle'), "id_signature=$id");

	} elseif ($id > 0){
		$row = sql_fetsel('*', 'spip_signatures', "id_signature=$id"); 
		if ($row['statut']=='poubelle')
			sql_updateq("spip_signatures", array("statut" => 'publie'), "id_signature=$id");
		else {
			include_spip('formulaires/signature');
			include_spip('inc/texte');
			
			$id_article = $row['id_article'];
			
			$url = generer_url_entite_absolue($id_article, 'article','','',true);
			if (signature_a_confirmer($id_article, $url, $row['nom_email'], $row['ad_email'], $row['nom_site'], $row['url_site'], $row['message'], $row['lang'], $row['statut']))
				sql_updateq("spip_signatures", array("date_time" => date('Y-m-d H:i:s')), "id_signature=$id");
			$id = 0;
		}
	} elseif ($id_article) {
		action_editer_signatures_relance($id_article);
	}

	// Invalider les pages ayant trait aux petitions
	if (!$id_article AND $id) {
		$id_article = sql_getfetsel("id_article", "spip_signatures", "id_signature=$id");
		if ($id_article) {
			include_spip('inc/invalideur');
			suivre_invalideur("id='varia/pet$id_article'");
		}
	}

	# cette requete devrait figurer dans l'optimisation
	sql_delete("spip_signatures", "NOT (statut='publie' OR statut='poubelle') AND date_time<DATE_SUB(NOW(),INTERVAL 10 DAY)");
}

// Relance toutes les signatures en attente

function action_editer_signatures_relance($id_article)
{
	include_spip('formulaires/signature');
	include_spip('inc/texte');
	$url = generer_url_entite_absolue($id_article, 'article','','',true);
	list($titre, $url) = signature_langue($id_article, $url);

	$envoyer_mail = charger_fonction('envoyer_mail','inc');

	$query = sql_select('*', 'spip_signatures', "id_article=$id_article AND NOT (statut='publie' OR statut='poubelle') AND date_time <  DATE_SUB(NOW(), INTERVAL 1 DAY)", 'ad_email');
	$n = 0;
	while ($r = sql_fetch($query)) {
	  list($sujet, $corps) = signature_demande_confirmation($id_article, $url, $r['nom_email'], $r['nom_site'], $r['url_site'], $r['message'], $titre, $r['statut']);
	  if ($envoyer_mail($mail, $sujet, $corps)) $n++;
	}
	spip_log("$n signatures relancees pour $titre");
}
?>
