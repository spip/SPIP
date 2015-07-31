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

include_spip('inc/filtres_boites');

/**
 * Fonctions utilises au calcul des squelette du prive.
 */

/**
 * Bloquer l'acces a une page en renvoyant vers 403
 * @param bool $ok
 */
function interdire_acces($ok=false) {
	if ($ok) return '';
	ob_end_clean(); // vider tous les tampons
	$echec = charger_fonction('403','exec');
	$echec();

	#include_spip('inc/headers');
	#redirige_formulaire(generer_url_ecrire('403','acces='._request('exec')));
	exit;
}

// http://doc.spip.org/@chercher_rubrique
function chercher_rubrique($msg,$id, $id_parent, $type, $id_secteur, $restreint,$actionable = false, $retour_sans_cadre=false){
	global $spip_lang_right;
	include_spip('inc/autoriser');
	if (intval($id) && !autoriser('modifier', $type, $id))
		return "";
	if (!sql_countsel('spip_rubriques'))
		return "";
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$form = $chercher_rubrique($id_parent, $type, $restreint, ($type=='rubrique')?$id:0);

	if ($id_parent == 0) $logo = "racine-24.png";
	elseif ($id_secteur == $id_parent) $logo = "secteur-24.png";
	else $logo = "rubrique-24.png";

	$confirm = "";
	if ($type=='rubrique') {
		// si c'est une rubrique-secteur contenant des breves, demander la
		// confirmation du deplacement
		$contient_breves = sql_countsel('spip_breves', "id_rubrique=$id");

		if ($contient_breves > 0) {
			$scb = ($contient_breves>1? 's':'');
			$scb = _T('avis_deplacement_rubrique',
				array('contient_breves' => $contient_breves,
				      'scb' => $scb));
			$confirm .= "\n<div class='confirmer_deplacement verdana2'><div class='choix'><input type='checkbox' name='confirme_deplace' value='oui' id='confirme-deplace' /><label for='confirme-deplace'>" . $scb . "</label></div></div>\n";
		} else
			$confirm .= "<input type='hidden' name='confirme_deplace' value='oui' />\n";
	}
	$form .= $confirm;
	if ($actionable){
		if (strpos($form,'<select')!==false) {
			$form .= "<div style='text-align: $spip_lang_right;'>"
				. '<input class="fondo" type="submit" value="'._T('bouton_choisir').'"/>'
				. "</div>";
		}
		$form = "<input type='hidden' name='editer_$type' value='oui' />\n" . $form;
		$form = generer_action_auteur("editer_$type", $id, self(), $form, " method='post' class='submit_plongeur'");
	}

	if ($retour_sans_cadre)
		return $form;

	include_spip('inc/presentation');
	return debut_cadre_couleur($logo, true, "", $msg) . $form .fin_cadre_couleur(true);

}


// http://doc.spip.org/@avoir_visiteurs
function avoir_visiteurs($past=false, $accepter=true) {
	if ($GLOBALS['meta']["forums_publics"] == 'abo') return true;
	if ($accepter AND $GLOBALS['meta']["accepter_visiteurs"] <> 'non') return true;
	if (sql_countsel('spip_articles', "accepter_forum='abo'"))return true;
	if (!$past) return false;
	return sql_countsel('spip_auteurs',  "statut NOT IN ('0minirezo','1comite', 'nouveau', '5poubelle')");
}

?>
