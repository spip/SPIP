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

function afficher_barre($formulaire='',$texte='', $forum=false) {
	global $spip_lang, $flag_ecrire;

	if (test_barre()) {	
		$ret = afficher_script_barre();
		$champ = "document.$formulaire.$texte";
		$ret .= "<div align='left'>";
		$ret .= "<a href=\"javascript:barre_raccourci('{{','}}',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-bold.png' border='0' width='24' height='24' title='Mettre en gras'></a>";
		$ret .= "&nbsp;";
		$ret .= "<a href=\"javascript:barre_raccourci('{','}',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-italic.png' border='0' width='24' height='24' title='Mettre en italique'></a>";
		$ret .= "&nbsp;&nbsp;&nbsp;";
		$ret .= "<a href=\"javascript:barre_raccourci('\n\n{{{','}}}\n\n',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-intertitre.png' border='0' width='24' height='24' title='Cr&eacute;er un intertitre'></a>";
		$ret .= "&nbsp;";
		$ret .= "<a href=\"javascript:barre_raccourci('[[',']]',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-note.png' border='0' width='24' height='24' title='Cr&eacute;er une note de bas de page'></a>";
		$ret .= "&nbsp;&nbsp;&nbsp;";
		$ret .= "<a href=\"javascript:barre_demande('[','->',']','Veuillez indiquer l\'adresse de votre lien (vous pouvez indiquer une adresse Web sous la forme http://www.monsite/com ou simplement indiquer le num&eacute;ro d\'un article de ce site.',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-lien.png' border='0' width='24' height='24' title='Cr&eacute;er un lien hypertexte'></a>";
		
		if ($forum) {
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= "<a href=\"javascript:barre_raccourci('\n\n<quote>','</quote>\n\n',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-quote.png' border='0' width='24' height='24' title='Citer un extrait'></a>";
		}
		
		if ($spip_lang == "fr") {
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= "<a href=\"javascript:barre_raccourci('&laquo;','&raquo;',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-guillemets.png' border='0' width='24' height='24' title='Placer entre guillemets'></a>";
			if (test_claret()) {
				$ret .= "&nbsp;";
				$ret .= "<a href=\"javascript:barre_inserer('&OElig;',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-oe-maj.png' border='0' width='24' height='24' title='Ins&eacute;rer un E-dans-l-O majuscule'></a>";
				$ret .= "&nbsp;";
				$ret .= "<a href=\"javascript:barre_inserer('&oelig;',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-oe.png' border='0' width='24' height='24' title='Ins&eacute;rer un E-dans-l-O'></a>";
				$ret .= "&nbsp;";
				$ret .= "<a href=\"javascript:barre_inserer('&Agrave;',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-a-grave.png' border='0' width='24' height='24' title='Ins&eacute;rer un A accent grave'></a>";
				$ret .= "&nbsp;";
				$ret .= "<a href=\"javascript:barre_inserer('&Eacute;',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-e-aigu.png' border='0' width='24' height='24' title='Ins&eacute;rer un E accent aigu'></a>";
			}
		}
		if (test_claret()) {
			$ret .= "&nbsp;";
			$ret .= "<a href=\"javascript:barre_inserer('&euro;',$champ)\"><img src='".($flag_ecrire ? "" : "ecrire/")."img_pack/barre-euro.png' border='0' width='24' height='24' title='Ins&eacute;rer le symbole euro'></a>";
		}
		$ret .= "<div>";
	}
	return $ret;
}

function afficher_claret() {
	if (test_claret()) {
		return "onselect='storeCaret(this);' onclick='storeCaret(this);' onkeyup='storeCaret(this);' ondbclick='storeCaret(this);'";
	}
}
?>