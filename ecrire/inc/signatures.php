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

// http://doc.spip.org/@message_de_signature
function message_de_signature($row)
{
  return propre(echapper_tags($row['message']));
}

// http://doc.spip.org/@inc_signatures_dist
function inc_signatures_dist($script, $id, $debut, $where, $order, $limit='', $type='') {
	charger_generer_url();

	# filtre de duree (a remplacer par une vraie pagination)
	#$where .= ($where ? " AND " : "") . "date_time>DATE_SUB(NOW(),INTERVAL 180 DAY)";
	if ($type == 'interne')   $where = "NOT($where)";
	if ($id) { 
		$args = "id_article=$id&";
		$where .= " AND id_article=$id";
	}
	else $args = "";

	$t = sql_countsel("spip_signatures", $where);
	if ($t > ($nb_aff = floor(1.5*_TRANCHES))) {
		$res = navigation_pagination($t, $nb_aff, generer_url_ecrire($script, $args), false, 'debut');
	} else $res = '';

	$limit = (!$limit AND !$debut) ? '' : (($debut ? "$debut," : "") . $limit);

	$request = sql_select('*', 'spip_signatures', $where, '', $order, $limit);

	$res .= '<br />';

 	while($row=sql_fetch($request)){
	  $res .= '<br />' . signatures_edit($script, $id, $debut, $row, $type);
	}
	return $res;
}

// http://doc.spip.org/@signatures_edit
function signatures_edit($script, $id, $debut, $row, $type) {

	global $spip_lang_right, $spip_lang_left;
	$id_signature = $row['id_signature'];
	$id_article = $row['id_article'];
	$date_time = $row['date_time'];
	$nom_email= typo(echapper_tags($row['nom_email']));
	$ad_email = echapper_tags($row['ad_email']);
	$nom_site = typo(echapper_tags($row['nom_site']));
	$url_site = echapper_tags($row['url_site']);
	$statut = $row['statut'];
		
	$retour_s = redirige_action_auteur('editer_signatures', $id_signature, $script, "id_article=$id_article&debut=$debut&type=$type#signature$id_signature");
	$retour_a = redirige_action_auteur('editer_signatures', "-$id_signature", $script, "id_article=$id_article&debut=$debut&type=$type#signature$id_signature");

	$res = "";
		
	if ($statut=="poubelle"){
			$res .= "<table width='100%' cellpadding='2' cellspacing='0' border='0'><tr><td style='background-color: #ff0000'>";
		}
		
	$res .= "<table id='signature$id_signature' width='100%' cellpadding='3' cellspacing='0'><tr><td class='verdana2 toile_foncee' style='color: white;'><b>"
 		.  ($nom_site ? "$nom_site / " : "")
		.  $nom_email
		.  "</b></td></tr>"
		.  "<tr><td style='background-color: #ffffff' class='serif'>";
				
	if  ($statut=="poubelle"){
			$res .= icone_inline (_T('icone_valider_signature'),
				$retour_s,
				"forum-interne-24.gif", 
				"creer.gif",
				"right",
				false);
	} else {
		$res .= icone_inline (_T('icone_supprimer_signature'),
				$retour_a,
				"forum-interne-24.gif", 
				"supprimer.gif",
				"right",
				false);
		if ($statut<>"publie") {
			$res .= icone_inline (_T('icone_relancer_signataire'),
				$retour_s,
				"forum-interne-24.gif", 
				"creer.gif",
				"right",
				false);
		}
	}
	$res .= "<span class='spip_small'>".date_interface($date_time)."</span><br />";
	if ($statut=="poubelle"){
			$res .= "<span class='spip_x-small' style='color: red;'>"._T('info_message_efface')."</span><br />";
	}
	if (strlen($url_site)>6) {
			if (!$nom_site) $nom_site = _T('info_site');
			$res .= "<span class='spip_x-small'>"._T('info_site_web')."</span> <a href='$url_site'>$nom_site</a><br />";
		}
	if (strlen($ad_email)>0){
			$res .= "<span class='spip_x-small'>"._T('info_adresse_email')."</span> <a href='mailto:$ad_email'>$ad_email</a><br />";
	}

	$res .= '<br />' . message_de_signature($row);
		
	if (!$id) {
		if ($r = sql_fetsel("titre, id_rubrique, statut", "spip_articles", "id_article=$id_article")) {
			$id_rubrique = $r['id_rubrique'];
			$publie = ($r['statut'] == 'publie');
			$titre_a = $r['titre'];
			$titre_r = supprimer_numero(sql_getfetsel("titre", "spip_rubriques", "id_rubrique=$id_rubrique"));
		        $href = generer_url_ecrire('naviguer', "id_rubrique=" . $id_rubrique);
			$res .= "<a style='float: $spip_lang_right; color: black; padding-$spip_lang_left: 4px;' href='$href' title='$id_rubrique'>"
			. typo($titre_r)
			. " </a><a title='$id_article' href='"
			  .  ($publie ? 
			      generer_url_action('redirect', "id_article=$id_article") :
			      generer_url_ecrire('articles', "id_article=$id_article"))
			  . "'>"
			  . typo($titre_a)
			  . "</a>";
		}
	}
	$res .= "</td></tr></table>";
		
	if ($statut=="poubelle"){
			$res .= "</td></tr></table>";
	}

	return $res;
}
?>
