<?php
if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

function exec_mes_fichiers_dist(){
	global $spip_lang_right;
	// si pas autorise : message d'erreur
	if (!autoriser('sauvegarder', 'mes_fichiers')) {
		include_spip('inc/minipres');
		echo minipres();
		die();
	}

	// pipeline d'initialisation
	pipeline('exec_init', array('args'=>array('exec'=>'mes_fichiers'),'data'=>''));

	// entetes
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('mes_fichiers:titre_page_navigateur'), "administration", "administration");
	
	// titre
	echo "<br /><br /><br />\n"; // outch que c'est vilain !
	echo gros_titre(_T('mes_fichiers:titre_page_exec'),'', false);
	
	// barre d'onglets
	echo barre_onglets("administration", "mes_fichiers");
	
	// colonne gauche
	echo debut_gauche('', true);
	echo pipeline('affiche_gauche', array('args'=>array('exec'=>'mes_fichiers'),'data'=>''));
	
	// colonne droite
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite', array('args'=>array('exec'=>'mes_fichiers'),'data'=>''));
	
	// centre
	echo debut_droite('', true);

	// contenu
	include_spip('inc/mes_fichiers_utils');
 	echo recuperer_fond('prive/contenu/mes_fichiers_sauver',  array('fichiers' => mes_fichiers_a_sauver(), 'a_sauver'=> _request('a_sauver'), 'etat' => _request('etat')));
 	echo recuperer_fond('prive/contenu/mes_fichiers_telecharger', array('fichiers' => mes_fichiers_a_telecharger(), 'etat' => _request('etat')));

	// fin contenu
	echo pipeline('affiche_milieu', array('args'=>array('exec'=>'mes_fichiers'),'data'=>''));

	echo fin_gauche(), fin_page();
}

?>
