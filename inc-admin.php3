<?php

// Inserer la feuille de style selon les normes, dans le <head>
// puis les boutons
// Feuilles de style admin : d'abord la CSS officielle, puis la perso,

function affiche_boutons_admin(&$contenu) {
	//
	// Regler les boutons dans la langue de l'admin (sinon tant pis)
	//
	include_local(_FILE_CONNECT);
	include_ecrire ("inc_lang.php3");
	$login = addslashes(ereg_replace('^@','',$GLOBALS['spip_admin']));
	if ($row = spip_fetch_array(spip_query("SELECT lang FROM spip_auteurs WHERE login='$login'"))) {
		$lang = $row['lang'];
	}

	$css = "<link rel='stylesheet' href='spip_admin.css' type='text/css' />\n";
	if ($f = find_in_path('spip_admin_perso.css'))
		$css .= "<link rel='stylesheet' href='$f' type='text/css' />\n";

#	$n = stripos($contenu, '</head>'); #PHP5
	preg_match('@</head>@i',$contenu,$regs);
	$n = strpos($contenu, $regs[0]);
	if ($n)
	  $contenu = substr($contenu,0,$n) . $css . substr($contenu,$n);
	else 
	  // squelette pourri: on force
	  $contenu = "<html><head>$css</head>$contenu";
	$insere = synthetiser_balise_dynamique('formulaire_admin',
					       array(
		$GLOBALS['id_article'], $GLOBALS['id_breve'],
		$GLOBALS['id_rubrique'], $GLOBALS['id_mot'],
		$GLOBALS['id_auteur'], 'div'),
					       find_in_path('inc-formulaire_admin' . _EXTENSION_PHP),
					       $lang);

	preg_match('@<body[^>]*>@i',$contenu,$regs);
	$n = strpos($contenu, $regs[0]) + strlen($regs[0]);
	if ($n) 
	  $contenu = substr($contenu,0,$n) . $insere . substr($contenu,$n);
	else 
	  // squelette pourri: on force
	  $contenu .=  $insere;

	return $contenu;
}

?>
