<?php
//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_BARRE")) return;
define("_INC_BARRE", "1");

//include_ecrire ("inc_layers.php3"); // (pour memoire)

function test_barre() {
	global $HTTP_UA_OS, $browser_name, $browser_version, $browser_description, $browser_rev;

	if ($browser_name == '') verif_butineur();

	if (
	(eregi("msie", $browser_name) AND $browser_version >= 5 AND $HTTP_UA_OS != 'MacOS')
	|| (eregi("mozilla", $browser_name) AND $browser_version >= 5 AND $browser_rev >= 1.3)
	)
		return true;
}


function test_claret() {
	global $HTTP_UA_OS, $browser_name, $browser_version, $browser_description, $browser_rev;

	if ( test_barre() && (eregi("msie", $browser_name)) ) return true;
}


function afficher_script_barre(){
	global $flag_ecrire, $flag_script_deja_affiche;

	if ($flag_script_deja_affiche != 1) {
		$flag_script_deja_affiche = 1;
		$ret = '<script type="text/javascript" src="'.($flag_ecrire ? "../" : "").'spip_barre.js">';
		$ret .= "</script>\n";
		return $ret;
	}
}

function bouton_barre_racc($action, $img, $help, $formulaire, $texte) {
	global $flag_ecrire;
	$champ = "document.$formulaire.$texte";
	$champhelp = "document.$formulaire.helpbox$texte";
	return "<a href=\"".$action."\" class='spip_barre' title=\"".attribut_html($help)."\" "
		."onMouseOver=\"helpline('$help',$champhelp)\" onMouseOut=\"helpline('Utilisez les raccourcis typographiques pour enrichir votre mise en page', $champhelp)\">"
		."<img src='".($flag_ecrire ? "../" : "")."IMG/icones_barre/".$img."' border='0' height='16' align='middle'></a>";
}

function afficher_barre($formulaire='',$texte='', $forum=false) {
	global $spip_lang, $flag_ecrire, $options;

	if (test_barre()) {
		$ret = afficher_script_barre();
		$champ = "document.$formulaire.$texte";
		$ret .= "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
		$ret .= "<tr width='100%'>";
		$ret .= "<td align='left' style='padding-top: 4px; padding-bottom: 2px;'>";
		$col++;

		// Italique, gras, intertitres
		$ret .= bouton_barre_racc ("javascript:barre_raccourci('{','}',$champ)", "italique.png", "Mettre en {italique}", $formulaire, $texte);
		$ret .= bouton_barre_racc ("javascript:barre_raccourci('{{','}}',$champ)", "gras.png", "Mettre en {{gras}}", $formulaire, $texte);
		if (!$forum) {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('\n\n{{{','}}}\n\n',$champ)", "intertitre.png", "Transformer en {{{intertitre}}}", $formulaire, $texte);
		}
		$ret .= "&nbsp;&nbsp;&nbsp;";

		// Lien hypertexte, notes de bas de page, citations ou guillemets
		$ret .= bouton_barre_racc ("javascript:barre_demande('[','->',']','Veuillez indiquer l\'adresse de votre lien (vous pouvez indiquer une adresse Web sous la forme http://www.monsite/com ou simplement indiquer le num&eacute;ro d\'un article de ce site.',$champ)",
			"lien.png", "Transformer en [lien hypertexte->http://...]", $formulaire, $texte);
		if (!$forum) {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('[[',']]',$champ)", "notes.png", "Transformer en [[Note de bas de page]]", $formulaire, $texte);
		}
		if ($forum) {
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('\n\n<quote>','</quote>\n\n',$champ)", "quote.png", "<quote>Citer un message</quote>", $formulaire, $texte);
		}

		if ($options == "avancees") {
			/*$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('[?',']',$champ)", "barre-wiki.png", "Entr&eacute;e du [?glossaire] (Wikipedia)", $formulaire, $texte);
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_tableau($champ)", "barre-tableau.png", "Ins&eacute;rer un tableau", $formulaire, $texte);*/
		}

		$ret .= "</td>";

		// Insertion de caracteres difficiles a taper au clavier
		$ret .= "<td align='center'>";
		$col++;
		if ($spip_lang == "fr" OR $spip_lang == "eo" OR $spip_lang == "cpf") {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('&laquo;','&raquo;',$champ)", "guillemets.png", "Entourer de &laquo; guillemets fran&ccedil;ais &raquo;", $formulaire, $texte);
		}	
		$ret .= bouton_barre_racc ("javascript:barre_raccourci('&ldquo;','&rdquo;',$champ)", "guillemets-simples.png", "Entourer de &ldquo;guillemets&rdquo;", $formulaire, $texte);
		$ret .= "&nbsp;&nbsp;&nbsp;";
		$ret .= bouton_barre_racc ("javascript:barre_inserer('~',$champ)", "espace.png", "Ins&eacute;rer une espace~ins&eacute;cable", $formulaire, $texte);
		if ($spip_lang == "fr" OR $spip_lang == "eo" OR $spip_lang == "cpf") {
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&Agrave;',$champ)", "agrave-maj.png", "Ins&eacute;rer un A accent grave majuscule", $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&Eacute;',$champ)", "eacute-maj.png", "Ins&eacute;rer un E accent aigu majuscule", $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&oelig;',$champ)", "oelig.png", "Ins&eacute;rer un E-dans-l-O", $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&OElig;',$champ)", "oelig-maj.png", "Ins&eacute;rer un E-dans-l-O majuscule", $formulaire, $texte);
		}
		//$ret .= bouton_barre_racc ("javascript:barre_inserer('&euro;',$champ)", "euro.png", "Ins&eacute;rer le symbole euro", $formulaire, $texte);
		$ret .= "</td>";

		$ret .= "<td> &nbsp; </td>";
		$col++;

		if ($flag_ecrire) {
			$ret .= "<td align='right' onMouseOver=\"helpline('En savoir plus sur les raccourcis typographiques',helpbox$texte)\" onMouseOut=\"helpline('Utilisez les raccourcis typographiques pour enrichir votre mise en page', $champhelp)\">";
			$col++;
			$ret .= aide("raccourcis");
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= "</td>";
		}
		$ret .= "</tr>";

		// Sur les forums publics, petite barre d'aide en survol des icones
		if ($forum)
			$ret .= "<tr><td colspan='$col'><input type='text' name='helpbox".$texte."' size='45' maxlength='100' style='width:100%; font-size:10px; background-color: #e4e4e4; border: 0px solid #dedede;' value='Utilisez les raccourcis typographiques pour enrichir votre mise en page' /></td></tr>";
		$ret .= "</table>";
	}
	return $ret;
}

function afficher_claret() {
	return "onselect='storeCaret(this);' onclick='storeCaret(this);' onkeyup='storeCaret(this);' ondbclick='storeCaret(this);'";
}
?>
