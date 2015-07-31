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
		$ret = '<script type="text/javascript" src="'.($flag_ecrire ? "" : "ecrire/").'spip_barre.js">';
		$ret .= "</script>\n";
		return $ret;	
	}
}

function bouton_barre_racc($action, $img, $help, $formulaire, $texte) {
	global $flag_ecrire;
	$champ = "document.$formulaire.$texte";
	$champhelp = "document.$formulaire.helpbox";
	return "<a href=\"".$action."\" onMouseOver=\"helpline('$help')\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/".$img."' border='0' height='16' title='$help'></a>";
}

function afficher_barre($formulaire='',$texte='', $forum=false) {
	global $spip_lang, $flag_ecrire;

	if (test_barre()) {	
		$ret = afficher_script_barre();
		$champ = "document.$formulaire.$texte";
		$ret .= "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
		$ret .= "<tr width='100%'>";
		$ret .= "<td align='left'>";
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
		
		if ($forum) {
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('\n\n<quote>','</quote>\n\n',$champ)", "barre-quote.png", "<quote>Citer un message</quote>", $formulaire, $texte);
		}
		$ret .= "</td>";
		
		$ret .= "<td align='right'>";
		if ($spip_lang == "fr") {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('&laquo;','&raquo;',$champ)", "barre-guillemets.png", "Insérer des &laquo; guillemets fran&ccedil;ais &raquo;", $formulaire, $texte);
			//$ret .= "<a href=\"javascript:barre_raccourci('&laquo;','&raquo;',$champ, 'Insérer des &laquo; guillemets fran&ccedil;ais &raquo;')\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-guillemets.png' border='0' width='24' height='24' title='Placer entre guillemets'></a>";
			if (test_claret()) {
				$ret .= "&nbsp;&nbsp;&nbsp;";
				$ret .= bouton_barre_racc ("javascript:barre_inserer('&OElig;',$champ)", "barre-oe-maj.png", "Ins&eacute;rer un E-dans-l-O majuscule", $formulaire, $texte);
				$ret .= bouton_barre_racc ("javascript:barre_inserer('&oelig;',$champ)", "barre-oe.png", "Ins&eacute;rer un E-dans-l-O", $formulaire, $texte);
				$ret .= bouton_barre_racc ("javascript:barre_inserer('&Agrave;',$champ)", "barre-a-grave.png", "Ins&eacute;rer un A accent grave", $formulaire, $texte);
				$ret .= bouton_barre_racc ("javascript:barre_inserer('&Eacute;',$champ)", "barre-e-aigu.png", "Ins&eacute;rer un E accent aigu", $formulaire, $texte);
			}
		}
		if (test_claret()) {
			$ret .= "&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&euro;',$champ)", "barre-euro.png", "Ins&eacute;rer le symbole euro", $formulaire, $texte);
		}
		$ret .= "</td>";
		$ret .= "</tr>";
		$ret .= "<tr><td colspan='2'><input type='text' name='helpbox' size='45' maxlength='100' style='width:100%; font-size:10px; background-color: #eeeeee; border: 0px solid #eeeeee;' value='Utilisez les raccourcis de SPIP pour enrichir votre mise en pages' /></td></tr>";
		$ret .= "</table>";
	}
	return $ret;
}

function afficher_claret() {
	if (test_claret()) {
		return "onselect='storeCaret(this);' onclick='storeCaret(this);' onkeyup='storeCaret(this);' ondbclick='storeCaret(this);'";
	}
}
?>