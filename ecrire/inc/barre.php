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

// construit un bouton (ancre) de raccourci avec icone et aide

// http://doc.spip.org/@bouton_barre_racc
function bouton_barre_racc($action, $img, $help, $champhelp) {

	$a = attribut_html($help);
	return "<a\nhref=\"javascript:"
		.$action
		."\" tabindex='1000'\ntitle=\""
		. $a
		."\"" 
		.(!_DIR_RESTREINT ? '' :  "\nonmouseover=\"helpline('"
		  .addslashes($a)
		  ."',$champhelp)\"\nonmouseout=\"helpline('"
		  .attribut_html(_T('barre_aide'))
		  ."', $champhelp)\"")
		."><img\nsrc='"
		._DIR_IMG_ICONES_BARRE
		.$img
		."' height='16' width='16' align='middle' alt=' '/></a>";
}

// construit un tableau de raccourcis pour un noeud de DOM 

// http://doc.spip.org/@afficher_barre
function afficher_barre($champ, $forum=false, $lang='') {
	global $spip_lang, $spip_lang_right, $spip_lang_left, $spip_lang;
	static $num_barre = 0;
	include_spip('inc/layer');
	if (!$GLOBALS['browser_barre']) return '';
	if (!$lang) $lang = $spip_lang;
	$num_barre++;
	$champhelp = "document.getElementById('barre_$num_barre')";

	$ret = ($num_barre > 1)  ? '' :
	  '<script type="text/javascript" src="' . _DIR_JAVASCRIPT . 'spip_barre.js"></script>';
	$ret .= "<table class='spip_barre' cellpadding='0' cellspacing='0' border='0'>";
	$ret .= "\n<tr>";
	$ret .= "\n<td style='text-align: $spip_lang_left;' valign='middle'>";
	$col = 1;

	// Italique, gras, intertitres
	$ret .= bouton_barre_racc ("barre_raccourci('{','}',$champ)", "italique.png", _T('barre_italic'), $champhelp);
	$ret .= bouton_barre_racc ("barre_raccourci('{{','}}',$champ)", "gras.png", _T('barre_gras'), $champhelp);
	if (!$forum) {
		$ret .= bouton_barre_racc ("barre_raccourci('\n\n{{{','}}}\n\n',$champ)", "intertitre.png", _T('barre_intertitre'), $champhelp);
	}
	$ret .= "</td>\n<td>";
	$col ++;

	// Lien hypertexte, notes de bas de page, citations
	$js = addslashes(_T('barre_lien_input'));
	$ret .= bouton_barre_racc ("barre_demande('[','->',']', '$js', $champ)",
		"lien.png", _T('barre_lien'), $champhelp);
	if (!$forum) {
		$ret .= bouton_barre_racc ("barre_raccourci('[[',']]',$champ)", "notes.png", _T('barre_note'), $champhelp);
	} else {
		$col ++;
		$ret .= "</td>\n<td>"
		  . bouton_barre_racc ("barre_raccourci('\n\n&lt;quote&gt;','&lt;/quote&gt;\n\n',$champ)", "quote.png", _T('barre_quote'), $champhelp);
	}

	$ret .= "</td>";
	$col++;

	// Insertion de caracteres difficiles a taper au clavier (guillemets, majuscules accentuees...)
	$ret .= "\n<td style='text-align:$spip_lang_left;' valign='middle'>";
	$col++;
	if ($lang == "fr" OR $lang == "eo" OR $lang == "cpf" OR $lang == "ar" OR $lang == "es") {
		$ret .= bouton_barre_racc ("barre_raccourci('&laquo;','&raquo;',$champ)", "guillemets.png", _T('barre_guillemets'), $champhelp);
		$ret .= bouton_barre_racc ("barre_raccourci('&ldquo;','&rdquo;',$champ)", "guillemets-simples.png", _T('barre_guillemets_simples'), $champhelp);
	}
	else if ($lang == "bg" OR $lang == "de" OR $lang == "pl" OR $lang == "hr" OR $lang == "src") {
		$ret .= bouton_barre_racc ("barre_raccourci('&bdquo;','&ldquo;',$champ)", "guillemets-de.png", _T('barre_guillemets'), $champhelp);
		$ret .= bouton_barre_racc ("barre_raccourci('&sbquo;','&lsquo;',$champ)", "guillemets-uniques-de.png", _T('barre_guillemets_simples'), $champhelp);
	}
	else {
		$ret .= bouton_barre_racc ("barre_raccourci('&ldquo;','&rdquo;',$champ)", "guillemets-simples.png", _T('barre_guillemets'), $champhelp);
		$ret .= bouton_barre_racc ("barre_raccourci('&lsquo;','&rsquo;',$champ)", "guillemets-uniques.png", _T('barre_guillemets_simples'), $champhelp);
	}
	if ($lang == "fr" OR $lang == "eo" OR $lang == "cpf") {
		$ret .= bouton_barre_racc ("barre_inserer('&Agrave;',$champ)", "agrave-maj.png", _T('barre_a_accent_grave'), $champhelp);
		$ret .= bouton_barre_racc ("barre_inserer('&Eacute;',$champ)", "eacute-maj.png", _T('barre_e_accent_aigu'), $champhelp);
		if ($lang == "fr") {
			$ret .= bouton_barre_racc ("barre_inserer('&oelig;',$champ)", "oelig.png", _T('barre_eo'), $champhelp);
			$ret .= bouton_barre_racc ("barre_inserer('&OElig;',$champ)", "oelig-maj.png", _T('barre_eo_maj'), $champhelp);
		}
	}
	$ret .= bouton_barre_racc ("barre_inserer('&euro;',$champ)", "euro.png", _T('barre_euro'), $champhelp);

	$ret .= "</td>";
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
		$ret .= "\n<tr>\n<td colspan='$col'><input disabled='disabled' type='text' class='barre' id='barre_$num_barre' size='45' maxlength='100'\nvalue=\"".attribut_html(_T('barre_aide'))."\" /></td></tr>";

	$ret .= "</table>";

	return $ret;
}

// pour compatibilite arriere. utiliser directement le corps a present.

// http://doc.spip.org/@afficher_claret
function afficher_claret() {
	include_spip('inc/layer');
	return $GLOBALS['browser_caret'];
}

?>