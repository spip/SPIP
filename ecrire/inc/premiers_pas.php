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

// http://doc.spip.org/@premiers_pas_etapes
function premiers_pas_etapes($etape,$titre,$texte){
	if (!autoriser('administrer','spip')) {
		echo _T('avis_non_acces_page');
		echo fin_gauche(), fin_page();
		exit;
	}
	init_config();
	lire_metas();

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_premiers_pas'), "premiers_pas", "premiers_pas","",false);
	
	echo premiers_pas_barre_etapes($etape);
	
	echo debut_gauche();
	creer_colonne_droite();
	debut_droite();
	gros_titre($titre);
	echo $texte;
	echo "<br /><br />\n";
	echo fin_gauche();
	
	echo debut_gauche();
	if (function_exists($f = "premiers_pas_pas_{$etape}_gauche") OR function_exists($f = $f."_dist"))
		echo $f();
	creer_colonne_droite();
	debut_droite();

	if (function_exists($f = "premiers_pas_pas_{$etape}_milieu") OR function_exists($f = $f."_dist"))
		$res = $f();
	else $res = '';
		
	$res .= premiers_pas_boutons_bas($etape)
	.  "<input type='hidden' name='pas' value='1' />";	

	echo redirige_action_auteur('premiers_pas', $etape, 'accueil', '',$res);
	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@premiers_pas_barre_etapes
function premiers_pas_barre_etapes($etape){
	$liste = find_all_in_path('premiers_pas/',"pas_[^.]*[.]php");
	echo "<style type='text/css'>\n";
	echo <<<EOF
ul.etapes {
	width:100% ;
	height:3em;
	font-size:large;
	font-weight:bold;
}
ul.etapes li.etape{
	display:block;
	float:left;
	width:2em;
	height:2em;
	padding-top:0.7em;
	text-align:center;
	border:2px solid #3874b0; /* couleur foncee a remettre */
	margin:0 0.5em 0 0;
}
ul.etapes li.etape.on{
	background-color:#edf3fe; /*  couleur claire a remettre */
}
ul.etapes li.etape.off{
	color:#aaa;
	border:2px solid #888;
}
ul.etapes li.etape.encours{
	background-color:#3874b0; /* couleur foncee a remettre */
	border:2px solid #edf3fe; /*  couleur claire a remettre  */
	color:#fff;
}
EOF;
	echo "</style>\n";
	echo "<ul class='etapes'>";
	$todo = false;
	$npas = 1;
	foreach($liste as $pas=>$chemin){
		if ($todo) $class = 'off';
		else $class='on';
		if ($pas == "pas_{$etape}.php"){
			$class = 'encours';
			$todo = true;
		}
		echo "<li class='etape $class'>$npas</li>";
		$npas++;
	}
	echo "</ul>";
	
}
// http://doc.spip.org/@premiers_pas_boutons_bas
function premiers_pas_boutons_bas($etape){
	global $spip_lang_right,$spip_lang_left;

	$res = "<div class='verdana3' style='margin-top:2em;text-align:$spip_lang_right'>";
	if ($etape!=='fin'){
		$res .= "<input type='submit' class='fondl' name='cancel' style='cursor:pointer;position:absolute;left:376px;top:40px;' value='"._L("Pas de premiers pas, utiliser directement SPIP")."' />";
		$res .= "<input type='submit' name='submit' class='fondo' value='"._L("Etape suivante")."' />";
	}
	else
		$res .= "<input type='submit' name='submit' class='fondo' value='"._L("Terminer")."' />";
	$res .= "</div>";

	return $res;
}

?>
