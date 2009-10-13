<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Presentation de l'interface privee, debut du HTML
//

// http://doc.spip.org/@inc_commencer_page_dist
function inc_commencer_page_dist($titre = "", $rubrique = "accueil", $sous_rubrique = "accueil", $id_rubrique = "",$menu=true,$minipres=false, $alertes = true) {
	global $connect_id_auteur;

	include_spip('inc/headers');

	http_no_cache();

	return init_entete($titre, $id_rubrique, $minipres)
	. init_body($rubrique, $sous_rubrique, $id_rubrique,$menu)
	. "<div id='page'>"
	. ($alertes?alertes_auteur($connect_id_auteur):'')
	. auteurs_recemment_connectes($connect_id_auteur);
}

// envoi du doctype et du <head><title>...</head>
// http://doc.spip.org/@init_entete
function init_entete($titre='', $id_rubrique=0, $minipres=false) {
	include_spip('inc/gadgets');

	if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']["nom_site"])))
		$nom_site_spip=  _T('info_mon_site_spip');

	$head = "<title>["
		. $nom_site_spip
		. "] " . textebrut(typo($titre)) . "</title>\n"
		. "<meta http-equiv='Content-Type' content='text/html"
		. (($c = $GLOBALS['meta']['charset']) ?
			"; charset=$c" : '')
		. "' />\n"
		. envoi_link($nom_site_spip,$minipres);

	$head .= "
	<script type='text/javascript'><!--
	jQuery(document).ready(function(){
	" .	repercuter_gadgets($id_rubrique) . '
	});
	// --></script>
	';

	return _DOCTYPE_ECRIRE
	. html_lang_attributes()
	. "<head>\n"
	. pipeline('header_prive', $head)
	. "</head>\n";
}

// fonction envoyant la double serie d'icones de redac
// http://doc.spip.org/@init_body
function init_body($rubrique='accueil', $sous_rubrique='accueil', $id_rubrique='',$menu=true) {
	global $connect_id_auteur, $auth_can_disconnect;

	$GLOBALS['spip_display'] = isset($GLOBALS['visiteur_session']['prefs']['display'])
		? $GLOBALS['visiteur_session']['prefs']['display']
		: 0;
	$spip_display_navigation = isset($GLOBALS['visiteur_session']['prefs']['display_navigation'])
		? $GLOBALS['visiteur_session']['prefs']['display_navigation']
		: 'navigation_avec_icones';
	$GLOBALS['spip_ecran'] = isset($_COOKIE['spip_ecran']) ? $_COOKIE['spip_ecran'] : "etroit";

	$res = pipeline('body_prive',"<body class='"
			. $GLOBALS['spip_ecran'] . " $spip_display_navigation $rubrique $sous_rubrique "._request('exec')."'"
			. ($GLOBALS['spip_lang_rtl'] ? " dir='rtl'" : "")
			.'>');

	if (!$menu) return $res;


	$bandeau = charger_fonction('bandeau', 'inc');

	return $res
	 . $bandeau($rubrique, $sous_rubrique, $largeur);
}

// http://doc.spip.org/@avertissement_messagerie
function avertissement_messagerie($id_auteur) {

	$result_messages = sql_allfetsel("L.id_message", "spip_messages AS M LEFT JOIN spip_auteurs_messages AS L ON L.id_message=M.id_message", "L.id_auteur=".intval($id_auteur)." AND vu='non' AND statut='publie' AND type='normal'");
	$total_messages = count($result_messages);
	if ($total_messages == 1) {
		$row = $result_messages[0];
		$ze_message=$row['id_message'];
		return "<a href='" . generer_url_ecrire("message","id_message=$ze_message") . "' class='ligne_foncee'>"._T('info_nouveau_message')."</a>";
	} elseif ($total_messages > 1)
		return "<a href='" . generer_url_ecrire("messagerie") . "' classe='ligne_foncee'>"._T('info_nouveaux_messages', array('total_messages' => $total_messages))."</a>";
	else return '';
}

// http://doc.spip.org/@alertes_auteur
function alertes_auteur($id_auteur) {

	$alertes = array();

	if (isset($GLOBALS['meta']['message_crash_tables'])
	AND autoriser('detruire', null, null, $id_auteur)) {
		include_spip('genie/maintenance');
		if ($msg = message_crash_tables())
			$alertes[] = $msg;
	}

	if (isset($GLOBALS['meta']['message_crash_plugins'])
	AND autoriser('configurer', 'plugins', null, $id_auteur)) {
		include_spip('inc/plugin');
		if ($msg = message_crash_plugins())
			$alertes[] = $msg;
	}


	if (isset($GLOBALS['meta']['plugin_erreur_activation'])
	AND autoriser('configurer', 'plugins', null, $id_auteur)) {
		$alertes[] = $GLOBALS['meta']['plugin_erreur_activation'];
		effacer_meta('plugin_erreur_activation'); // pas normal que ce soit ici
	}

	$alertes[] = avertissement_messagerie($id_auteur);

	if ($alertes = array_filter($alertes))
		return "<div class='wrap-messages'><div class='messages'>".
			join('<hr />', $alertes)
			."</div></div>";
}

// http://doc.spip.org/@auteurs_recemment_connectes
function auteurs_recemment_connectes($id_auteur)
{
	$result = sql_allfetsel("*", "spip_auteurs",  "id_auteur!=" .intval($id_auteur) .  " AND " . sql_date_proche('en_ligne', -15, 'MINUTE') . " AND " . sql_in('statut', array('1comite', '0minirezo')));

	if (!$result) return '';
	$formater_auteur = charger_fonction('formater_auteur', 'inc');
	$res = '';
	foreach ($result as $row) {
		$id = $row['id_auteur'];
		$mail = formater_auteur_mail($row, $id);
		$auteurs = "<a href='" . generer_url_ecrire("auteur_infos", "id_auteur=$id") . "'>" . typo($row['nom']) . "</a>";
		$res .= "$mail&nbsp;$auteurs" . ", ";
	}

	return "<div class='en_lignes' style='color:#666;'>" .
	  "<b>"._T('info_en_ligne'). "&nbsp;</b>" .
	  substr($res,0,-2) .
	  "</div>";
}


// http://doc.spip.org/@lien_change_var
function lien_change_var($lien, $set, $couleur, $coords, $titre, $mouseOver="") {
	$lien = parametre_url($lien, $set, $couleur);
	return "\n<area shape='rect' href='$lien' coords='$coords' title=\"$titre\" alt=\"$titre\" $mouseOver />";
}


?>
