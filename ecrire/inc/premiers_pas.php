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

include_spip('inc/minipres');
include_spip('inc/meta');

// http://doc.spip.org/@premiers_pas_etapes
function premiers_pas_etapes($etape,$titre,$texte){
	global $spip_lang_left;
	if (!autoriser('administrer','spip')) {
		echo _T('avis_non_acces_page');
		echo fin_gauche(), fin_page();
		exit;
	}
	init_config();
	lire_metas();

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_premiers_pas'), "premiers_pas", "premiers_pas","",false,true);
	
	echo "<div id='minipres' style='width:750px;text-align:$spip_lang_left;'>";
	if ($etape!=='fin')
	  echo generer_action_auteur('premiers_pas', '', '', "<input type='submit' class='fondl' name='cancel' style='cursor:pointer;pointer:hand;' value='"._L("Utiliser directement SPIP")."' />");
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
	echo fin_gauche(), 
		"</div>",
		info_progression_etape($etape,'pas_','premiers_pas/'),
		fin_page();
}

// http://doc.spip.org/@premiers_pas_boutons_bas
function premiers_pas_boutons_bas($etape){
	global $spip_lang_right,$spip_lang_left;

	$res = "<div class='verdana3' style='margin-top:2em;text-align:$spip_lang_right'>";
	if ($etape!=='fin'){
		$res .= "<input type='submit' name='submit' class='fondo' value='"._L("Etape suivante")."' />";
	}
	else
		$res .= "<input type='submit' name='submit' class='fondo' value='"._L("Terminer")."' />";
	$res .= "</div>";

	return $res;
}

?>
