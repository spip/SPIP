<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


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

/**
 * #BOITE_OUVRIR{titre[,type]}
 * Racourci pour ouvrir une boite (info, simple, pour noisette ...)
 *
 * @param <type> $p
 * @return <type>
 */
function balise_BOITE_OUVRIR_dist($p) {
	$_titre = interprete_argument_balise(1,$p);
	$_class = interprete_argument_balise(2,$p);
	$_head_class = interprete_argument_balise(3,$p);
	$_titre = ($_titre?$_titre:"''");
	$_class = ($_class?", $_class":", 'simple'");
	$_head_class = ($_head_class?", $_head_class":"");

	$f = chercher_filtre('boite_ouvrir');
	$p->code = "$f($_titre$_class$_head_class)";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * #BOITE_PIED{class}
 * Racourci pour passer au pied de la boite, avant sa fermeture
 *
 * @param <type> $p
 * @return <type>
 */
function balise_BOITE_PIED_dist($p) {
	$_class = interprete_argument_balise(1,$p);
	$_class = ($_class?"$_class":"");

	$f = chercher_filtre('boite_pied');
	$p->code = "$f($_class)";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * #BOITE_FERMER
 * Racourci pour fermer une boite ouverte
 *
 * @param <type> $p
 * @return <type>
 */
function balise_BOITE_FERMER_dist($p) {
	$f = chercher_filtre('boite_fermer');
	$p->code = "$f()";
	$p->interdire_scripts = false;
	return $p;
}

/**
 * Ouvrir une boite
 *
 * @param string $titre
 * @param string $class
 * @return <type>
 */
function boite_ouvrir($titre, $class='', $head_class=''){
	$class = "box $class";
	$head_class = "hd $head_class";
	if (strlen($titre) AND strpos($titre,'<h')===false)
		$titre = "<h3>$titre</h3>";
	return '<div class="'.$class.'">'
	.'<b class="top"><b class="tl"></b><b class="tr"></b></b>'
	.'<div class="inner">'
	.($titre?'<div class="'.$head_class.'">'.$titre.'</div>':'')
	.'<div class="bd">';
}

/**
 * Passer au pied d'une boite
 * @param <type> $class
 * @return <type>
 */
function boite_pied($class='act'){
	$class = "ft $class";
	return 	'</div>'
	.'<div class="'.$class.'">';
}

/**
 * Fermer une boite
 * @return <type>
 */
function boite_fermer(){
	return '</div></div>'
	.'<b class="bottom"><b class="bl"></b><b class="br"></b></b>'
	.'</div>';
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