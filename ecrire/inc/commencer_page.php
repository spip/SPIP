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

//
// Presentation de l'interface privee, debut du HTML
//

// http://doc.spip.org/@inc_commencer_page_dist
function inc_commencer_page_dist($titre = "", $rubrique = "accueil", $sous_rubrique = "accueil", $id_rubrique = "",$menu=true,$minipres=false, $alertes = true) {
	global $spip_ecran;
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

	// anciennement verifForm
	// et corriger position des sous-menus principaux (quand intitules sur 2 lignes)
	$head .= '
	<script type="text/javascript"><!--
	function calculer_top_bandeau_sec() {
		
		var hauteur_max = 0;
		var hauteur_bouton = 0;
	
		$(".boutons_admin a.boutons_admin .icon_texte").each(function(){
			hauteur_bouton = parseInt($(this).height());
			if (hauteur_bouton > hauteur_max) hauteur_max = hauteur_bouton;
		});
		$(".boutons_admin a.boutons_admin .icon_texte").height(hauteur_max);
	}	

	$(document).ready(function(){
		verifForm();
		calculer_top_bandeau_sec();
		$("#page,#bandeau-principal")
		.mouseover(function(){
			if (typeof(window["changestyle"])!=="undefined") window.changestyle("garder-recherche");
		});
	'
	.
	repercuter_gadgets($id_rubrique)
	.'
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
	global $spip_display, $spip_ecran;

	if ($spip_ecran == "large") $largeur = 974; else $largeur = 750;

	$res = pipeline('body_prive',"<body class='ecrire $rubrique $sous_rubrique $spip_ecran "._request('exec')."'"
			. ($GLOBALS['spip_lang_rtl'] ? " dir='rtl'" : "")
			.'>');

	if ($spip_display == "4") {
		$res .= "<ul>"
		. "\n<li><a href='" . generer_url_ecrire() ."'>"._T('icone_a_suivre')."</a></li>"
		. "\n<li><a href='" . generer_url_ecrire("naviguer") . "'>"._T('icone_edition_site')."</a></li>"
		. "\n<li><a href='" . generer_url_ecrire("forum"). "'>"._T('titre_forum')."</a></li>"
		. "\n<li><a href='" . generer_url_ecrire("auteurs") . "'>"._T('icone_auteurs')."</a></li>"
		. "\n<li><a href=\"".url_de_base()."\">"._T('icone_visiter_site')."</a></li>"
		. "</ul>";

		return $res;
	}
	if (!$menu) return $res;


	$items = "<div class='h-list centered vcentered' style='width:{$largeur}px'><ul>"
		. "<li id='bandeau_couleur1' class='bandeau_couleur'><div class='menu-item'>"
		.  installer_gadgets($id_rubrique)
		. "</div></li>"
		. "<li id='bandeau_couleur2' class='bandeau_couleur' style='width:"

	// Redacteur connecte
	// overflow pour masquer les noms tres longs
	// (et eviter debords, notamment en ecran etroit)

		//. "<div style='width: "
		. (($spip_ecran == "large") ? 300 : 110)
		. "px;'><div class='menu-item' style='width:"
    . (($spip_ecran == "large") ? 300 : 110)
    . "px; overflow: hidden;'>"
		. "<a href='"
		. generer_url_ecrire("auteur_infos","id_auteur=$connect_id_auteur")
		. "' class='icone26' title=\""
		. entites_html(_T('icone_informations_personnelles'))
		. '">'
		. typo($GLOBALS['visiteur_session']['nom'])
		. "</a></div></li>" 
	  	. "<li id='bandeau_couleur4' class='bandeau_couleur'><div class='menu-item'>";

		// couleurs
	$couleurs = charger_fonction('couleurs', 'inc');
	$items .= "<div id='preferences_couleurs' title='" . attribut_html(_T('titre_changer_couleur_interface')) . "'>";
	$items .= $couleurs() . "</div>";

	$items .= "</div></li>";

	// choix de la langue
	if ($i = menu_langues('var_lang_ecrire')) {
			$items .= "<li id='bandeau_couleur5' class='bandeau_couleur'><div class='menu-item'>"
			. (_request('lang')?$i:"<a href='".parametre_url(self(),'lang',$GLOBALS['spip_lang'])
			."' title='".attribut_html(_T('info_langues'))."'>"
			. traduire_nom_langue($GLOBALS['spip_lang'])
			."</a>")
			. "</div></li>";
	}

	$items .= "<li id='bandeau_couleur6' class='bandeau_couleur'><div class='menu-item'>";

	if ($auth_can_disconnect) {
			$alt=_T('icone_deconnecter');
			$items .= "<a href='".
			  generer_url_action("logout","logout=prive") .
			  "' class='icone26' onmouseover=\"changestyle('bandeaudeconnecter');\" onfocus=\"changestyle('bandeaudeconnecter');\" onblur=\"changestyle('bandeaudeconnecter');\">" .
			  http_img_pack("deconnecter-24.gif", "$alt", "width='26' height='20'") .
			  "</a>";
	}
	$items .= "</div></li>"
	  . "</ul></div>";

	// <div> pour la barre des gadgets
	// (elements invisibles qui s'ouvrent sous la barre precedente)

	$items .= bandeau_gadgets($largeur, true, $id_rubrique);

	$bandeau = charger_fonction('bandeau', 'inc');

	return $res 
		. "<div class='invisible_au_chargement' style='position: absolute; height: 0px; visibility: hidden;'><a href='oo'>"
		._T("access_mode_texte")
		."</a></div>"
		. "<div id='haut-page'>\n"
		.  $bandeau($rubrique, $sous_rubrique, $largeur)
		. "\n<div id='bandeau_couleur'>"
		.  $items
	  	.  "</div></div>\n";
}

// http://doc.spip.org/@avertissement_messagerie
function avertissement_messagerie($id_auteur) {

	$result_messages = sql_allfetsel("lien.id_message", "spip_messages AS messages, spip_auteurs_messages AS lien", "lien.id_auteur=".sql_quote($id_auteur)." AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message",'','');
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

	$alertes = pipeline(
		'alertes_auteur',
			array(
			'args' => array(
				'id_auteur' => $id_auteur,
				'exec' => _request('exec'),
				),
			'data' => $alertes
			)
		);

	if ($alertes = array_filter($alertes))
		return "<div class='wrap-messages'><div class='messages'>".
			join('<hr />', $alertes)
			."</div></div>";
}

// http://doc.spip.org/@auteurs_recemment_connectes
function auteurs_recemment_connectes($id_auteur)
{
	$result = sql_allfetsel("*", "spip_auteurs",  "id_auteur!=" .intval($id_auteur) .  " AND en_ligne>DATE_SUB(".sql_quote(date('Y-m-d H:i:s')).",INTERVAL 15 MINUTE) AND " . sql_in('statut', array('1comite', '0minirezo')));

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
