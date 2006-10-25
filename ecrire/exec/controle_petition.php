<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/signatures');

// http://doc.spip.org/@exec_controle_petition_dist
function exec_controle_petition_dist()
{
	global $connect_statut, $id_article, $debut;

	$id_article = intval($id_article);
	$debut =  intval($debut);

 	if ($connect_statut != "0minirezo") {
		$r = "<b>"._T('avis_non_acces_page')."</b>";
	} else {
		$var_f = charger_fonction('signatures', 'inc');

		$r = $var_f('controle_petition',
			$id_article,
			$debut, 
			"(statut='publie' OR statut='poubelle')",
			"date_time DESC",
			10);
	}

	if (_request('var_ajaxcharset')) ajax_retour($r);

	debut_page(_T('titre_page_controle_petition'), "forum", "suivi-petition");
	debut_gauche();

	debut_droite();
  
	gros_titre(_T('titre_suivi_petition'));

	$a = "editer_signature-" . $id_article;

	echo  "<div id='", $a, "' class='serif2'>", $r, "</div>", fin_page();
}
?>
