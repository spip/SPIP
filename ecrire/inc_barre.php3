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
	return "<a href=\"".$action."\" onMouseOver=\"helpline('$help',$champhelp)\"><img src='".($flag_ecrire ? "../" : "")."IMG/icones_barre/".$img."' border='0' height='16' align='middle'></a>";
}

function afficher_barre($formulaire='',$texte='', $forum=false) {
	global $spip_lang, $flag_ecrire, $options;

	if (test_barre()) {	
		$ret = afficher_script_barre();
		$champ = "document.$formulaire.$texte";
		$ret .= "<table style='background-color: #dedede; margin-top:2px; padding-left: 2px; padding-top: 2px; border: 0px solid #eeeeee; border-left: 1px solid #ffffff; border-top: 1px solid #ffffff; border-bottom: 1px solid #aaaaaa; border-right: 1px solid #aaaaaa;' cellpadding='0' cellspacing='0' border='0' width='100%'>";
		$ret .= "<tr width='100%'>";
		$ret .= "<td align='left'>";
		$col++;



		$ret .= bouton_barre_racc ("javascript:barre_raccourci('{{','}}',$champ)", "barre-bold.png", "Mettre en {{gras}}", $formulaire, $texte);
		$ret .= bouton_barre_racc ("javascript:barre_raccourci('{','}',$champ)", "barre-italic.png", "Mettre en {italique}", $formulaire, $texte);
		if (!$forum) {	
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('\n\n{{{','}}}\n\n',$champ)", "barre-intertitre.png", "{{{Ins&eacute;rer un intertitre}}}", $formulaire, $texte);
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('[[',']]',$champ)", "barre-note.png", "[[Note de bas de page]]", $formulaire, $texte);
		}
		$ret .= "&nbsp;&nbsp;&nbsp;";
		$ret .= bouton_barre_racc ("javascript:barre_demande('[','->',']','Veuillez indiquer l\'adresse de votre lien (vous pouvez indiquer une adresse Web sous la forme http://www.monsite/com ou simplement indiquer le num&eacute;ro d\'un article de ce site.',$champ)", "barre-lien.png", "Cr&eacute;er un [lien hypertexte->http://...]", $formulaire, $texte);
		if ($options == "avancees") {
				$ret .= "&nbsp;&nbsp;&nbsp;";
				$ret .= bouton_barre_racc ("javascript:barre_raccourci('[?',']',$champ)", "barre-wiki.png", "Entr&eacute;e du [?glossaire] (Wikipedia)", $formulaire, $texte);
				$ret .= "&nbsp;&nbsp;&nbsp;";
				$ret .= bouton_barre_racc ("javascript:barre_tableau($champ)", "barre-tableau.png", "Ins&eacute;rer un tableau", $formulaire, $texte);
		}
		
		if ($forum) {
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('\n\n<quote>','</quote>\n\n',$champ)", "barre-quote.png", "<quote>Citer un message</quote>", $formulaire, $texte);
		}

		/*if ($options == "avancees") {
				$ret .= "&nbsp;&nbsp;&nbsp;";
				$ret .= bouton_barre_racc ("javascript:barre_raccourci('<html>','</html>',$champ)", "barre-html.png", "<html>Ne pas appliquer de correction typographique</html>", $formulaire, $texte);
				$ret .= bouton_barre_racc ("javascript:barre_raccourci('<code>','</code>',$champ)", "barre-code.png", "Afficher du <code>code informatique</code>", $formulaire, $texte);
				if (!$forum) $ret .= bouton_barre_racc ("javascript:barre_raccourci('\n\n<cadre>','</cadre>\n\n',$champ)", "barre-cadre.png", "<cadre>Afficher un pav&eacute; de code informatique</cadre>", $formulaire, $texte);
		}*/
				

		$ret .= "</td>";
		
		$ret .= "<td align='center'>";
		$col++;
		if ($spip_lang == "fr") {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('&laquo;','&raquo;',$champ)", "barre-guillemets.png", "Insérer des &laquo; guillemets fran&ccedil;ais &raquo;", $formulaire, $texte);
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&OElig;',$champ)", "barre-oe-maj.png", "Ins&eacute;rer un E-dans-l-O majuscule", $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&oelig;',$champ)", "barre-oe.png", "Ins&eacute;rer un E-dans-l-O", $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&Agrave;',$champ)", "barre-a-grave.png", "Ins&eacute;rer un A accent grave", $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&Eacute;',$champ)", "barre-e-aigu.png", "Ins&eacute;rer un E accent aigu", $formulaire, $texte);
		}
		$ret .= "&nbsp;";
		$ret .= bouton_barre_racc ("javascript:barre_inserer('&euro;',$champ)", "barre-euro.png", "Ins&eacute;rer le symbole euro", $formulaire, $texte);
		$ret .= "</td>";
		
		$ret .= "<td> &nbsp; </td>";
		$col++;
		
		if ($flag_ecrire) {
			$ret .= "<td align='right' onMouseOver=\"helpline('En savoir plus sur les raccourcis typographiques',helpbox$texte)\">";
			$col++;
			$ret .= aide("raccourcis");
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= "</td>";
		}
		
		$ret .= "</tr>";
		$ret .= "<tr><td colspan='$col'><input type='text' name='helpbox".$texte."' size='45' maxlength='100' style='width:100%; font-size:10px; background-color: #dedede; border: 0px solid #dedede;' value='Utilisez les raccourcis de SPIP pour enrichir votre mise en pages' /></td></tr>";
		$ret .= "</table>";
	}
	return $ret;
}

function afficher_claret() {
		return "onselect='storeCaret(this);' onclick='storeCaret(this);' onkeyup='storeCaret(this);' ondbclick='storeCaret(this);'";
}
?>
