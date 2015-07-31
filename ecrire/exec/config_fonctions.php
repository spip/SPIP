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

include_spip('inc/presentation');
include_spip('inc/meta');

// http://doc.spip.org/@exec_config_fonctions_dist
function exec_config_fonctions_dist()
{
	if (!autoriser('configurer', 'fonctions')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$config = charger_fonction('config', 'inc');
	$config();

	pipeline('exec_init',array('args'=>array('exec'=>'config_fonctions'),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_config_fonctions'), "configuration", "configuration");

	echo "<br /><br /><br />";
	gros_titre(_T('titre_config_fonctions'));
	echo barre_onglets("configuration", "fonctions");

	debut_gauche();
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'config_fonctions'),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'config_fonctions'),'data'=>''));
	debut_droite();
	lire_metas();

	$reducteur = charger_fonction('reducteur', 'configuration');
#	$indexeur = charger_fonction('indexeur', 'configuration');
	$compteur = charger_fonction('compteur', 'configuration');
	$avertisseur = charger_fonction('avertisseur', 'configuration');
	$versionneur = charger_fonction('versionneur', 'configuration');
	$previsualiseur = charger_fonction('previsualiseur', 'configuration');
	$relayeur = charger_fonction('relayeur', 'configuration');

	echo 

	  $reducteur(), // Creation automatique de vignettes

#	  $indexeur(), // Indexation pour moteur de recherche

	  $compteur(), // Gerant de statistique

	  $avertisseur(), // Notification de modification des articles

	  $versionneur(), // Gestion des revisions des articles

	  $previsualiseur(), // Previsualisation sur le site public

	  $relayeur(_request('retour_proxy')); // Proxy pour syndication & doc
//
// Choix supplementaires proposees par les plugins
//
	$res = pipeline('affiche_milieu',array('args'=>array('exec'=>'config_fonctions'),'data'=>''));
	if ($res)
		echo ajax_action_post('config_fonctions', '', 'config_fonctions', '', $res);

	echo fin_gauche(), fin_page();
}

?>
