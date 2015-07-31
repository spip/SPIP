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

//
// Presentation de l'interface privee, debut du HTML
//

// http://doc.spip.org/@inc_commencer_page_dist
function inc_commencer_page_dist($titre = "", $rubrique = "accueil", $sous_rubrique = "accueil", $id_rubrique = "") {

	include_spip('inc/headers');

	http_no_cache();

	return init_entete($titre, $id_rubrique)
	. init_body($rubrique, $sous_rubrique, $id_rubrique)
	. "<div id='page' align='center'>"
	. avertissement_messagerie()
	  . ((($rubrique == "messagerie") OR (_request('changer_config')!="oui"))
	     ? auteurs_recemment_connectes() : '');
}

// envoi du doctype et du <head><title>...</head> 
// http://doc.spip.org/@init_entete
function init_entete($titre='', $id_rubrique=0) {
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
		. envoi_link($nom_site_spip);

	// anciennement verifForm
	$head .= '
	<script type="text/javascript"><!--
	$(document).ready(function(){
		verifForm();
		$("#page")
		.mouseover(function(){
			changestyle("garder-recherche");
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
function init_body($rubrique='accueil', $sous_rubrique='accueil', $id_rubrique='') {
	global $couleur_foncee, $couleur_claire;
	global $connect_id_auteur, $auth_can_disconnect;
	global $options, $spip_display, $spip_ecran;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	if ($spip_ecran == "large") $largeur = 974; else $largeur = 750;

	$res = pipeline('body_prive',"<body class='$rubrique $sous_rubrique'"
			. ($GLOBALS['spip_lang_rtl'] ? " dir='rtl'" : "")
			.'>')
	. "\n<div><map name='map_layout' id='map_layout'>"
	. lien_change_var (self(), 'set_disp', 1, '1,0,18,15', _T('lien_afficher_texte_seul'), "onmouseover=\"changestyle('bandeauvide');\" onfocus=\"changestyle('bandeauvide');\" onblur=\"changestyle('bandeauvide');\"")
	. lien_change_var (self(), 'set_disp', 2, '19,0,40,15', _T('lien_afficher_texte_icones'), "onmouseover=\"changestyle('bandeauvide');\" onfocus=\"changestyle('bandeauvide');\" onblur=\"changestyle('bandeauvide');\"")
	. lien_change_var (self(), 'set_disp', 3, '41,0,59,15', _T('lien_afficher_icones_seuls'), "onmouseover=\"changestyle('bandeauvide');\" onfocus=\"changestyle('bandeauvide');\" onblur=\"changestyle('bandeauvide');\"")
	. "\n</map></div>";

	if ($spip_display == "4") {
		$res .= "<ul>"
		. "\n<li><a href='./'>"._T('icone_a_suivre')."</a></li>"
		. "\n<li><a href='" . generer_url_ecrire("naviguer") . "'>"._T('icone_edition_site')."</a></li>"
		. "\n<li><a href='" . generer_url_ecrire("forum"). "'>"._T('titre_forum')."</a></li>"
		. "\n<li><a href='" . generer_url_ecrire("auteurs") . "'>"._T('icone_auteurs')."</a></li>"
		. "\n<li><a href=\"".url_de_base()."\">"._T('icone_visiter_site')."</a></li>"
		. "</ul>";

		return $res;
	}

	$res .= bandeau_double_rangee($rubrique, $sous_rubrique, $largeur);

	if (true /*$bandeau_colore*/) {
		if ($rubrique == "administration") {
			$style = "background: url(" . _DIR_IMG_PACK . "rayures-danger.png); background-color: $couleur_foncee";
			$res .= "<style>a.icone26 { color: white; }</style>";
		} else  $style = "background-color: $couleur_claire";

		$res .= "\n<div align='center' style=\"max-height: 40px; width: 100%; border-bottom: solid 1px white;$style\">"
	. "<table cellpadding='0' style='background: none;' width='$largeur'><tr>"
		. "<td valign='middle' class='bandeau_couleur' style='text-align: $spip_lang_left;'>"
		.  installer_gadgets($id_rubrique)
		. "</td>"
		. "<td valign='middle' class='bandeau_couleur' style='text-align: $spip_lang_left;'>"

	// Redacteur connecte
	// overflow pour masquer les noms tres longs
	// (et eviter debords, notamment en ecran etroit)

		. "<div style='width: "
		. (($spip_ecran == "large") ? 300 : 110)
		. "px; height: 14px; overflow: hidden;'>"
		. "<a href='"
		. generer_url_ecrire("auteur_infos","id_auteur=$connect_id_auteur") 
		. "' class='icone26' title=\""
		. entites_html(_T('icone_informations_personnelles'))
		. '">'
		. typo($GLOBALS['auteur_session']['nom'])
		. "</a></div>"
		. "</td>"
		. "<td> &nbsp; </td>"
		. "<td class='bandeau_couleur' style='text-align: $spip_lang_right;' valign='middle'>";

		// Choix du layout
		$res .= http_img_pack("choix-layout$spip_lang_rtl".($spip_lang=='he'?'_he':'').".gif", _T('choix_interface'), "class='format_png' style='vertical-align: middle' width='59' height='15' usemap='#map_layout'")
		. http_img_pack("rien.gif", "", "width='10' height='1'");
			// grand ecran
		if ($spip_ecran == "large") {
			$i = _T('info_petit_ecran');
			$res .= "<a href='". parametre_url(self(),'set_ecran', 'etroit') ."' class='icone26' onmouseover=\"changestyle('bandeauecran');\" title=\"$i\" onfocus=\"changestyle('bandeauecran');\" onblur=\"changestyle('bandeauecran');\">" .
			  http_img_pack("set-ecran-etroit.png", $i, "width='26' height='20'") . "</a>";
			$ecran = "<div><a href='".parametre_url(self(),'set_ecran', 'etroit')."' class='lien_sous'>"._T('info_petit_ecran')."</a>/<b>"._T('info_grand_ecran')."</b></div>";
		} else {
			$i = _T('info_grand_ecran');
			$res .= "<a href='".parametre_url(self(),'set_ecran', 'large')."' class='icone26' onmouseover=\"changestyle('bandeauecran');\" title=\"$i\" onfocus=\"changestyle('bandeauecran');\" onblur=\"changestyle('bandeauecran');\">" .
			  http_img_pack("set-ecran.png", $i, "width='26' height='20'") ."</a>";
			$ecran = "<div><b>"._T('info_petit_ecran')."</b>/<a href='".parametre_url(self(),'set_ecran', 'large')."' class='lien_sous'>"._T('info_grand_ecran')."</a></div>";
		}

		// Choix de la couleur
		$res .= "</td>"
		. "<td class='bandeau_couleur' style='width: 60px; text-align:$spip_lang_left;' valign='middle'>"
		. choix_couleur()
		. "</td>";

		// choix de la langue
		if ($GLOBALS['all_langs']) {
			$res .= "<td class='bandeau_couleur' style='width: 100px; text-align: $spip_lang_right;' valign='middle'>"
			. menu_langues('var_lang_ecrire')
			. "</td>";
		}

		$res .= "<td class='bandeau_couleur' style='text-align: $spip_lang_right; width: 28px;' valign='middle'>";

		if ($auth_can_disconnect) {
			$alt=_T('icone_deconnecter');
			$res .= "<a href='".
			  generer_url_action("logout","logout=prive") .
			  "' class='icone26' onmouseover=\"changestyle('bandeaudeconnecter');\" onfocus=\"changestyle('bandeaudeconnecter');\" onblur=\"changestyle('bandeaudeconnecter');\">" .
			  http_img_pack("deconnecter-24.gif", "$alt", "") .
			  "</a>";
		}
		$res .= "</td>"
		. "</tr></table>";

	} // fin bandeau colore

	// <div> pour la barre des gadgets
	// (elements invisibles qui s'ouvrent sous la barre precedente)

	$res .= bandeau_gadgets($largeur, $options, $id_rubrique)
	. "</div>"
	. "</div>\n";

	if ($options != "avancees") $res .= "<div style='height: 18px;'>&nbsp;</div>";
	return $res;
}

// Choix dynamique de la couleur

// http://doc.spip.org/@choix_couleur
function choix_couleur() {
	global $couleurs_spip;
	$res = '';
	if ($couleurs_spip) {
		$evt = '
onmouseover="changestyle(\'bandeauinterface\');"
onfocus="changestyle(\'bandeauinterface\');"
onblur="changestyle(\'bandeauinterface\');"';

		foreach ($couleurs_spip as $key => $val) {
			$res .= "<a href=\""
			. parametre_url(self(), 'set_couleur', $key)
			. "\"$evt>"
			. http_img_pack("rien.gif",
					_T('choix_couleur_interface') . $key,
					"width='8' height='8' style='margin: 1px; background-color: "	. $val['couleur_claire'] . ";'")
			. "</a>";
		}
	}
	return $res;
}

// http://doc.spip.org/@avertissement_messagerie
function avertissement_messagerie() {
	global $couleur_foncee;
	global $connect_id_auteur;

	$result_messages = spip_query("SELECT lien.id_message FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
	$total_messages = @spip_num_rows($result_messages);
	if ($total_messages == 1) {
		$row = @spip_fetch_array($result_messages);
		$ze_message=$row['id_message'];
		return "<div class='messages'><a href='" . generer_url_ecrire("message","id_message=$ze_message") . "'><span style='color: $couleur_foncee'>"._T('info_nouveau_message')."</span></a></div>";
	} elseif ($total_messages > 1)
		return "<div class='messages'><a href='" . generer_url_ecrire("messagerie") . "'><span style='color: $couleur_foncee'>"._T('info_nouveaux_messages', array('total_messages' => $total_messages))."</span></a></div>";
	else return '';
}


// http://doc.spip.org/@auteurs_recemment_connectes
function auteurs_recemment_connectes()
{	
	global $connect_id_auteur;
	$res = '';
	$result_auteurs = spip_query("SELECT id_auteur FROM spip_auteurs WHERE id_auteur!=$connect_id_auteur AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 MINUTE) AND statut IN ('0minirezo','1comite')");

	if (spip_num_rows($result_auteurs)) {
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$res = "<b>"._T('info_en_ligne'). "&nbsp;</b>";
		while ($row = spip_fetch_array($result_auteurs)) {
			list($s, $mail, $nom, $w, $p) = $formater_auteur($row['id_auteur']);
			$res .= "$mail&nbsp;$nom, ";
		}
		$res = substr($res,0,-2);
	}

	return $res ? "<div class='messages' style='color:#666;'>$res</div>" : '';
}


// http://doc.spip.org/@lien_change_var
function lien_change_var($lien, $set, $couleur, $coords, $titre, $mouseOver="") {
	$lien = parametre_url($lien, $set, $couleur);
	return "\n<area shape='rect' href='$lien' coords='$coords' title=\"$titre\" alt=\"$titre\" $mouseOver />";
}


?>
