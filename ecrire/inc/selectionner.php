<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion le l'affichage du sélecteur de rubrique AJAX
 *
 * @package SPIP\Core\Rubriques
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/filtres');


/**
 * Affiche un mini-navigateur ajax positionné sur une rubrique
 *
 * @uses mini_hier()
 * @uses construire_selectionner_hierarchie()
 * @uses inc_plonger_dist()
 *
 * @see  exec_rechercher_dist()
 *
 * @param int $sel
 *     Identifiant de la rubrique
 * @param string $idom
 *     Identifiant dans le dom de l'élément
 * @param int $exclus
 * @param bool $aff_racine
 * @param bool $recur
 * @param string $do
 *     Type d'action
 * @return string
 *     Code HTML
 **/
function inc_selectionner_dist($sel, $idom = "", $exclus = 0, $aff_racine = false, $recur = true, $do = 'aff') {

	if ($recur) {
		$recur = mini_hier($sel);
	} else {
		$sel = 0;
	}

	if ($aff_racine) {
		$info = generer_url_ecrire('informer', "type=rubrique&rac=$idom&do=$do&id=");
		$idom3 = $idom . "_selection";

		$onClick = "jQuery(this).parent().addClass('on');jQuery('#choix_parent_principal .on').removeClass('on'); aff_selection(0, '$idom3', '$info', event);return false;";

		$ondbClick = strtr(str_replace("'", "&#8217;",
			str_replace('"', "&#34;",
				textebrut(_T('info_racine_site')))),
			"\n\r", "  ");

		$js_func = $do . '_selection_titre';
		$ondbClick = "$js_func('$ondbClick',0,'selection_rubrique','id_parent');";

		$aff_racine = "<div class='petit-item petite-racine item'>"
			. "<a href='#'"
			. "onclick=\""
			. $onClick
			. "\"\nondbclick=\""
			. $ondbClick
			. $onClick
			. "\">"
			. _T("info_racine_site")
			. "</a></div>";
	}

	$url_init = generer_url_ecrire('plonger', "rac=$idom&exclus=$exclus&id=0&col=1&do=$do");

	$plonger = charger_fonction('plonger', 'inc');
	$plonger_r = $plonger($sel, $idom, $recur, 1, $exclus, $do);

	// url completee par la fonction JS onkeypress_rechercher
	$url = generer_url_ecrire('rechercher', "exclus=$exclus&rac=$idom&do=$do&type=");

	return construire_selectionner_hierarchie($idom, $plonger_r, $aff_racine, $url, 'id_parent', $url_init);
}

/**
 * Construit le sélectionneur de hierarchie
 *
 * @param string $idom
 *     Identifiant dans le dom de l'élément
 * @param string $liste
 * @param int $racine
 * @param string $url
 * @param string $name
 * @param string $url_init
 * @return string
 *     Code HTML
 **/
function construire_selectionner_hierarchie($idom, $liste, $racine, $url, $name, $url_init = '') {

	$idom1 = $idom . "_champ_recherche";
	$idom2 = $idom . "_principal";
	$idom3 = $idom . "_selection";
	$idom4 = $idom . "_col_1";
	$idom5 = 'img_' . $idom4;
	$idom6 = $idom . "_fonc";

	return "<div id='$idom'>"
	. "<a id='$idom6' style='visibility: hidden;'"
	. ($url_init ? "\nhref='$url_init'" : '')
	. "></a>"
	. "<div class='recherche_rapide_parent formulaire_recherche'>"
	. http_img_pack("loader.svg", "",
		"class='loader' style='visibility: hidden;float:" . $GLOBALS['spip_lang_right'] . "' id='$idom5'")
	. ""
	. "<input style='width: 10em;float:" . $GLOBALS['spip_lang_right'] . ";' type='text' class='text search' id='$idom1' placeholder='" . _T('info_rechercher') . "'"
	// eliminer Return car il provoque la soumission (balise unique)
	// et eliminer Tab pour la navigation au clavier
	// ce serait encore mieux de ne le faire que s'il y a encore plusieurs
	// resultats retournes par la recherche
	. "\nonkeypress=\"k=event.keyCode;if (k==13 || k==3 || k==9){return false;}\""
	// lancer la recherche apres le filtrage ci-dessus sauf sur le tab (navigation au clavier)
	. "\nonkeyup=\"if(event.keyCode==9){return false;};return onkey_rechercher(this.value,"
	// la destination de la recherche
	. "'$idom4'"
#	. "this.parentNode.parentNode.parentNode.parentNode.nextSibling.firstChild.id"
	. ",'"
	// l'url effectuant la recherche
	. $url
	. "',"
	// le noeud contenant un gif anime
	// . "'idom5'"
	. "this.parentNode.previousSibling.firstChild"
	. ",'"
	// la valeur de l'attribut Name a remplir
	. $name
	. "','"
	// noeud invisible memorisant l'URL initiale (pour re-initialisation)
	. $idom6
	. "')\""
	. " />"
	. "\n</div>"
	. ($racine ? "<div>$racine</div>" : "")
	. "<div id='"
	. $idom2
	. "'><div id='$idom4'"
	. " class=''>"
	. $liste
	. "</div></div>\n<div id='$idom3'></div></div>\n";
}

/**
 * Récupère les identifiants de hierarchie d'une rubrique
 *
 * @param int $id_rubrique
 * @return array
 *     Liste de tous les id_parent de la rubrique
 **/
function mini_hier($id_rubrique) {

	$liste = $id_rubrique;
	$id_rubrique = intval($id_rubrique);
	while ($id_rubrique = sql_getfetsel("id_parent", "spip_rubriques", "id_rubrique = " . $id_rubrique)) {
		$liste = $id_rubrique . ",$liste";
	}

	return explode(',', "0,$liste");
}
