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

	$action = generer_action_auteur('premiers_pas', $etape, generer_url_ecrire('accueil'));
	echo "<form action='$action' method='post'><div>", form_hidden($action);
	echo "<input type='hidden' name='pas' value='1' />";
	if (function_exists($f = "premiers_pas_pas_{$etape}_milieu") OR function_exists($f = $f."_dist"))
		echo $f();
		
	echo premiers_pas_boutons_bas($etape);
	
	echo "</div></form>";
	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@premiers_pas_barre_etapes
function premiers_pas_barre_etapes($etape){
	global $couleur_claire,$couleur_foncee;
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
	border:2px solid $couleur_foncee;
	margin:0 0.5em 0 0;
}
ul.etapes li.etape.on{
	background-color:$couleur_claire;
}
ul.etapes li.etape.off{
	color:#aaa;
	border:2px solid #888;
}
ul.etapes li.etape.encours{
	background-color:$couleur_foncee;
	border:2px solid $couleur_claire;
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
	echo "<div class='verdana3' style='margin-top:2em;text-align:$spip_lang_right'>";
	if ($etape!=='fin'){
		echo "<input type='submit' class='fondl' name='cancel' style='float:$spip_lang_left' value='"._L("Quitter et utiliser directement SPIP")."' />";
		echo "<input type='submit' name='submit' class='fondo' value='"._L("Etape suivante")."' />";
	}
	else
		echo "<input type='submit' name='submit' class='fondo' value='"._L("Terminer")."' />";
	echo "</div>";
	return;
}

?>