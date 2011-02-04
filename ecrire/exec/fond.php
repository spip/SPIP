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

/**
 * Un exec generique qui utilise le fond homonyme de l'exec demande
 * dans l'url
 */
function exec_fond_dist(){

	// pas d'autorisation
	// c'est au fond de les gerer avec #AUTORISER, et de renvoyer un fond vide le cas echeant
	// qui declenchera un minipres acces interdit
	$exec = _request('exec');
	$fond = trim(recuperer_fond("prive/exec/$exec",$_REQUEST));
	if (!$fond) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	$titre = "exec_$exec";
	$navigation = "";
	$extra = "";

	// recuperer le titre dans le premier hn de la page
	if (preg_match(",<h[1-6][^>]*>(.+)</h[1-6]>,Uims",$fond,$match)){
		$titre = $match[1];
	}

	// recuperer la hierarchie (au-dessus du contenu)
	if (preg_match(",<!--#hierarchie-->.+<!--/#hierarchie-->,Uims",$fond,$match)){
		$hierarchie = $match[0];
		$fond = str_replace($hierarchie,"",$fond);
	}
	
	// recuperer la navigation (colonne de gauche)
	if (preg_match(",<!--#navigation-->.+<!--/#navigation-->,Uims",$fond,$match)){
		$navigation = $match[0];
		$fond = str_replace($navigation,"",$fond);
	}

	// recuperer les extras (colonne de droite)
	if (preg_match(",<!--#extra-->.+<!--/#extra-->,Uims",$fond,$match)){
		$extra = $match[0];
		$fond = str_replace($extra,"",$fond);
	}

	include_spip('inc/presentation'); // alleger les inclusions avec un inc/presentation_mini
	$commencer_page = charger_fonction('commencer_page','inc');
	echo $commencer_page($titre);
	
	if ($hierarchie){
		echo debut_grand_cadre(true);
		echo pipeline(
			'affiche_hierarchie',
			array(
				'args' => array(
					'exec' => $exec
				),
				'data' => $hierarchie
			)
		);
		echo fin_grand_cadre(true);
	}
	
	echo debut_gauche("exec_$exec",true);
	$contexte = array('exec'=>$exec);
	$objet = objet_type(preg_replace(",_edit$,","",$exec));
	$id = id_table_objet($objet);
	if (_request($id))
		$contexte[$id] = _request($id);
	
	echo $navigation;
	echo pipeline('affiche_gauche',array('args'=>$contexte,'data'=>''));

	echo creer_colonne_droite("exec_$exec",true);
	echo $extra;
	echo pipeline('affiche_droite',array('args'=>$contexte,'data'=>''));

	echo debut_droite("exec_$exec",true);
	echo $fond;
	echo pipeline('affiche_milieu',array('args'=>$contexte,'data'=>''));

	echo fin_gauche(),fin_page();
	}
}

?>
