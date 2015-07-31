<?php

// Inserer la feuille de style selon les normes, dans le <head>
// puis les boutons
// Feuilles de style admin : d'abord la CSS officielle, puis la perso,

function affiche_boutons_admin($contenu) {
	$css = "<link rel='stylesheet' href='spip_admin.css' type='text/css' />\n";
	if ($f = find_in_path('spip_admin_perso.css'))
		$css2 = "<link rel='stylesheet' href='$f' type='text/css' />\n";

	if (preg_match('@<(/head|body)@i', $contenu, $regs)) {
		$contenu = explode($regs[0], $contenu, 2);
		$contenu = $contenu[0] . $css. $css2 . $regs[0] . $contenu[1];
	} else
		$contenu = $css . $css2 . $contenu;

	if (preg_match('@<(/body|/html)@i', $contenu, $regs)) {
		$split = explode($regs[0], $contenu, 2);
		$contenu = $split[0];
		$suite = $regs[0].$split[1];
	}

	echo $contenu;

	//
	// Regler les boutons dans la langue de l'admin (sinon tant pis)
	//
	include_local(_FILE_CONNECT);
	include_ecrire ("inc_lang.php3");
	$login = addslashes(ereg_replace('^@','',$GLOBALS['spip_admin']));
	if ($row = spip_fetch_array(spip_query("SELECT lang FROM spip_auteurs WHERE login='$login'"))) {
		$lang = $row['lang'];
	}
	lang_select($lang);

	// Afficher la balise #FORMULAIRE_ADMIN mais en float
	inclure_balise_dynamique(
		balise_formulaire_admin_dyn(
		$GLOBALS['id_article'], $GLOBALS['id_breve'],
		$GLOBALS['id_rubrique'], $GLOBALS['id_mot'],
		$GLOBALS['id_auteur'], 'div'
	));

	lang_dselect();

	return $suite;
}

?>
