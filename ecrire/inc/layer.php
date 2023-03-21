<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Affiche un cadre complet muni d’un bouton pour le déplier.
 *
 * @param string $icone Chemin vers l’icone que prendra le cadre
 * @param string $titre Titre du cadre
 * @param bool $deplie true ou false, défini si le cadre est déplié au chargement de la page (true) ou pas (false)
 * @param string $contenu Contenu du cadre
 * @param string $ids id que prendra la partie pliée ou dépliée
 * @param string $style_cadre classe CSS que prendra le cadre
 * @return string Code HTML du cadre dépliable
 **/
function cadre_depliable($icone, $titre, $deplie, $contenu, $ids = '', $style_cadre = 'r') {
	$bouton = bouton_block_depliable($titre, $deplie, $ids);

	return
		debut_cadre($style_cadre, $icone, '', $bouton, '', '', false)
		. debut_block_depliable($deplie, $ids)
		. "<div class='cadre_padding'>\n"
		. $contenu
		. "</div>\n"
		. fin_block()
		. fin_cadre();
}

function block_parfois_visible($nom, $invite, $masque, $style = '', $visible = false) {
	return "\n"
	. bouton_block_depliable($invite, $visible, $nom)
	. debut_block_depliable($visible, $nom)
	. $masque
	. fin_block();
}

function debut_block_depliable($deplie, $id = '') {
	$class = ' blocdeplie';
	// si on n'accepte pas js, ne pas fermer
	if (!$deplie) {
		$class = ' blocreplie';
	}

	return '<div ' . ($id ? "id='$id' " : '') . "class='bloc_depliable$class'>";
}

function fin_block() {
	return "<div class='nettoyeur'></div>\n</div>";
}

// $texte : texte du bouton
// $deplie : true (deplie) ou false (plie) ou -1 (inactif) ou 'incertain' pour que le bouton s'auto init au chargement de la page
// $ids : id des div lies au bouton (facultatif, par defaut c'est le div.bloc_depliable qui suit)
function bouton_block_depliable($texte, $deplie, $ids = '') {
	$bouton_id = 'b' . substr(md5($texte . microtime()), 0, 8);

	$class = ($deplie === true) ? ' deplie' : (($deplie == -1) ? ' impliable' : ' replie');
	if (strlen((string) $ids)) {
		$cible = explode(',', (string) $ids);
		$cible = '#' . implode(',#', $cible);
	} else {
		$cible = "#$bouton_id + div.bloc_depliable";
	}

	$b = (str_contains((string) $texte, '<h') ? 'div' : 'h3');

	return "<$b "
	. ($bouton_id ? "id='$bouton_id' " : '')
	. "class='titrem$class'"
	. (($deplie === -1)
		? ''
		: " onmouseover=\"jQuery(this).depliant('$cible');\""
	)
	. '>'
	// une ancre pour rendre accessible au clavier le depliage du sous bloc
	. "<a href='#' onclick=\"return jQuery(this).depliant_clicancre('$cible');\" class='titremancre'></a>"
	. "$texte</$b>"
	. http_script(($deplie === 'incertain')
		? "jQuery(function($){if ($('$cible').is(':visible')) { $('#$bouton_id').addClass('deplie').removeClass('replie'); }});"
		: '');
}
