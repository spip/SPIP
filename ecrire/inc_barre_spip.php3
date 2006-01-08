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

// construit un bouton (ancre) de raccourci avec icone et aide

function bouton_barre_racc($action, $img, $help, $champhelp) {

	return "<a\nhref=\"javascript:"
		.$action
		."\" class='spip_barre' tabindex='1000'\ntitle=\""
		.attribut_html($help)
		."\"" 
		.(!_DIR_RESTREINT ? '' :  "\nonMouseOver=\"helpline('"
		  .addslashes(attribut_html($help))
		  ."',$champhelp)\"\nonMouseOut=\"helpline('"
		  .attribut_html(_T('barre_aide'))
		  ."', $champhelp)\"")
		."><img\nsrc='"
		._DIR_IMG_ICONES_BARRE
		.$img
		."' border='0' height='16' width='16' align='middle' /></a>";
}

// construit un tableau de raccourcis pour un noeud de DOM 

function afficher_barre_spip($champ, $forum=false) {
	static $num_barre = 0;
	include_ecrire ("inc_layer");
	if (!$GLOBALS['browser_barre']) return '';

	global $spip_lang, $spip_lang_right, $spip_lang_left;

	$ret = ($num_barre > 0)  ? '' :
	  '<script type="text/javascript" src="' . _DIR_RACINE. 'spip_barre.js"></script>';
	$num_barre++;
	$champhelp = "document.getElementById('barre_$num_barre')";

	$ret .= "<table class='spip_barre' width='100%' cellpadding='0' cellspacing='0' border='0'>";
	$ret .= "\n<tr width='100%' class='spip_barre'>";
	$ret .= "\n<td style='text-align: $spip_lang_left;' valign='middle'>";
	$col = 1;

	// Italique, gras, intertitres
	$ret .= bouton_barre_racc ("barre_raccourci('{','}',$champ)", "italique.png", _T('barre_italic'), $champhelp);
	$ret .= bouton_barre_racc ("barre_raccourci('{{','}}',$champ)", "gras.png", _T('barre_gras'), $champhelp);
	if (!$forum) {
		$ret .= bouton_barre_racc ("barre_raccourci('\n\n{{{','}}}\n\n',$champ)", "intertitre.png", _T('barre_intertitre'), $champhelp);
	}
	$ret .= "&nbsp;&nbsp;&nbsp;</td>\n<td>";
	$col ++;

	// Lien hypertexte, notes de bas de page, citations
	$ret .= bouton_barre_racc ("barre_demande('[','->',']', '".addslashes(_T('barre_lien_input'))."', $champ)",
		"lien.png", _T('barre_lien'), $champhelp);
	if (!$forum) {
		$ret .= bouton_barre_racc ("barre_raccourci('[[',']]',$champ)", "notes.png", _T('barre_note'), $champhelp);
	}
	if ($forum) {
		$ret .= "&nbsp;&nbsp;&nbsp;&nbsp;</td>\n<td>";
		$col ++;
		$ret .= bouton_barre_racc ("barre_raccourci('\n\n&lt;quote&gt;','&lt;/quote&gt;\n\n',$champ)", "quote.png", _T('barre_quote'), $champhelp);
	}

	$ret .= "&nbsp;&nbsp;&nbsp;&nbsp;</td>";
	$col++;

	// Insertion de caracteres difficiles a taper au clavier (guillemets, majuscules accentuees...)
	$ret .= "\n<td style='text-align:$spip_lang_left;' valign='middle'>";
	$col++;
	if ($spip_lang == "fr" OR $spip_lang == "eo" OR $spip_lang == "cpf" OR $spip_lang == "ar" OR $spip_lang == "es") {
		$ret .= bouton_barre_racc ("barre_raccourci('&laquo;','&raquo;',$champ)", "guillemets.png", _T('barre_guillemets'), $champhelp);
		$ret .= bouton_barre_racc ("barre_raccourci('&ldquo;','&rdquo;',$champ)", "guillemets-simples.png", _T('barre_guillemets_simples'), $champhelp);
	}
	else if ($spip_lang == "bg" OR $spip_lang == "de" OR $spip_lang == "pl" OR $spip_lang == "hr" OR $spip_lang == "src") {
		$ret .= bouton_barre_racc ("barre_raccourci('&bdquo;','&ldquo;',$champ)", "guillemets-de.png", _T('barre_guillemets'), $champhelp);
		$ret .= bouton_barre_racc ("barre_raccourci('&sbquo;','&lsquo;',$champ)", "guillemets-uniques-de.png", _T('barre_guillemets_simples'), $champhelp);
	}
	else {
		$ret .= bouton_barre_racc ("barre_raccourci('&ldquo;','&rdquo;',$champ)", "guillemets-simples.png", _T('barre_guillemets'), $champhelp);
		$ret .= bouton_barre_racc ("barre_raccourci('&lsquo;','&rsquo;',$champ)", "guillemets-uniques.png", _T('barre_guillemets_simples'), $champhelp);
	}
	if ($spip_lang == "fr" OR $spip_lang == "eo" OR $spip_lang == "cpf") {
		$ret .= bouton_barre_racc ("barre_inserer('&Agrave;',$champ)", "agrave-maj.png", _T('barre_a_accent_grave'), $champhelp);
		$ret .= bouton_barre_racc ("barre_inserer('&Eacute;',$champ)", "eacute-maj.png", _T('barre_e_accent_aigu'), $champhelp);
		if ($spip_lang == "fr") {
			$ret .= bouton_barre_racc ("barre_inserer('&oelig;',$champ)", "oelig.png", _T('barre_eo'), $champhelp);
			$ret .= bouton_barre_racc ("barre_inserer('&OElig;',$champ)", "oelig-maj.png", _T('barre_eo_maj'), $champhelp);
		}
	}
	$ret .= bouton_barre_racc ("barre_inserer('&euro;',$champ)", "euro.png", _T('barre_euro'), $champhelp);

	$ret .= "&nbsp;&nbsp;&nbsp;&nbsp;</td>";
	$col++;

	if (!_DIR_RESTREINT) {
		$ret .= "\n<td style='text-align:$spip_lang_right;' valign='middle'>";
		$col++;
	//	$ret .= "&nbsp;&nbsp;&nbsp;";
		$ret .= aide("raccourcis");
		$ret .= "&nbsp;";
		$ret .= "</td>";
	}
	$ret .= "</tr>";

	// Sur les forums publics, petite barre d'aide en survol des icones
	if (_DIR_RESTREINT)
		$ret .= "\n<tr>\n<td colspan='$col'><input disabled='disabled' type='text' id='barre_$num_barre' size='45' maxlength='100' style='width:100%; font-size:11px; color: black; background-color: #e4e4e4; border: 0px solid #dedede;'\nvalue=\"".attribut_html(_T('barre_aide'))."\" /></td></tr>";

	$ret .= "</table>";
	return $ret;
}

// pour compatibilite arriere. utiliser directement le corps a present.

function afficher_claret() {
	include_ecrire ("inc_layer");
	return $GLOBALS['browser_caret'];
}

?>
