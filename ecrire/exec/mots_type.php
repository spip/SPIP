<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/presentation');

// http://doc.spip.org/@exec_mots_type_dist
function exec_mots_type_dist()
{
	$id_groupe= intval(_request('id_groupe'));

	if (!$id_groupe) {
	  $type = $titre = filtrer_entites(_T('titre_nouveau_groupe'));
	  $row = array();
	} else {
		$row = sql_fetsel("id_groupe,titre", "spip_groupes_mots", "id_groupe=$id_groupe");
		if ($row) {
			$id_groupe = $row['id_groupe'];
			$type = $row['titre'];
			$titre = typo($type);
		}
	}

	if (($id_groupe AND !$row) OR
	    !autoriser($id_groupe?'modifier' : 'creer', 'groupemots', $id_groupe)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	pipeline('exec_init',array('args'=>array('exec'=>'mots_type','id_groupe'=>$id_groupe),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page("&laquo; $titre &raquo;", "naviguer", "mots");
	
	echo debut_gauche('', true);

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'mots_type','id_groupe'=>$id_groupe),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'mots_type','id_groupe'=>$id_groupe),'data'=>''));
	echo debut_droite('', true);
	
	$contexte = array(
		'icone_retour'=>icone_inline(_T('icone_retour'), generer_url_ecrire("mots_tous") . "#mots_tous-$id_groupe", "groupe-mot-24.gif", "rien.gif",$GLOBALS['spip_lang_left']),
		'titre'=>$type,
		'redirect'=>generer_url_ecrire("mots_tous",""),
		'new'=>_request('new') == "oui"?"oui":$id_groupe,
		'config_fonc'=>'groupes_mots_edit_config',
	);

	echo recuperer_fond("prive/editer/groupe_mot", $contexte);

	echo pipeline('affiche_milieu',
		array('args' => array(
			'exec' => 'mots_type',
			'id_groupe' => $id_groupe
		),
		'data'=>'')
	),
	fin_gauche(),
	fin_page();
	}
}
?>
