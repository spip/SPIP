<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// demande/verifie le droit de creation de repertoire par le demandeur;
// memorise dans les meta que ce script est en cours d'execution
// si elle y est deja c'est qu'il y a eu suspension du script, on reprend.

// http://doc.spip.org/@inc_admin_dist
function inc_admin_dist($script, $titre, $comment='', $anonymous=false)
{
	$reprise = true;
	if (!isset($GLOBALS['meta'][$script])
	OR  !isset($GLOBALS['meta']['admin'])) {
		$reprise = false;
		$res = debut_admin($script, $titre, $comment); 
		if ($res) return $res;
		spip_log("meta: $script " . join(',', $_POST));
		ecrire_meta($script, serialize($_POST));
	} 

	$res = admin_verifie_session($script,$anonymous);
	if ($res) return $res;
	$base = charger_fonction($script, 'base');
	$base($titre,$reprise);
	fin_admin($script);
	return '';
}

// Gestion dans la meta "admin" du script d'administation demande,
// pour eviter des executions en parallele, notamment apres Time-Out.
// Cette meta contient le nom du script et, a un hachage pres, du demandeur.
// Le code de ecrire/index.php devie toute demande d'execution d'un script
// vers le script d'administration indique par cette meta si elle est l�.
// Au niveau de la fonction inc_admin, on controle la meta 'admin'.
// Si la meta n'est pas la, 
//	c'est le debut on la cree.
// Sinon, si le hachage actuel est le meme que celui en base, 
//	c'est une reprise, on continue
// Sinon, si le hachage differe a cause du connecte,
// 	c'est une arrivee inoppotune, on refuse sa connexion.
// Enfin, si hachage differe pour une autre raison
// 	c'est que l'operation se passe mal, on la stoppe

// http://doc.spip.org/@admin_verifie_session
function admin_verifie_session($script, $anonymous=false) {

	include_spip('base/abstract_sql');
	$pref = sprintf("_%d_",$GLOBALS['visiteur_session']['id_auteur']);
	$signal = fichier_admin($script, "$script$pref");
	$valeur = sql_getfetsel('valeur', 'spip_meta', "nom='admin'");
	if ($valeur === NULL) {
		ecrire_meta('admin', $signal, 'non');
	} else {
		if (!$anonymous AND ($valeur != $signal)) {
			if (!preg_match('/^(.*)_(\d+)_/', $GLOBALS['meta']["admin"], $l)
				OR intval($l[2])!=$GLOBALS['visiteur_session']['id_auteur']) {
				include_spip('inc/minipres');
				spip_log("refus de lancer $script, priorite a $valeur");
				return minipres(_T('info_travaux_texte'));
			}
		}
	}
	$journal = "spip";
	if (autoriser('configurer')) // c'est une action webmestre, soit par ftp soit par statut webmestre
		$journal = 'webmestre';
	// on pourrait statuer automatiquement les webmestres a l'init d'une action auth par ftp ... ?

	spip_log("admin $pref" . ($valeur ? " (reprise)" : ' (init)'),$journal);
	return '';
}

// http://doc.spip.org/@dir_admin
function dir_admin()
{
	if (autoriser('configurer')) {
		return _DIR_TMP;
	} else {
		return  _DIR_TRANSFERT . $GLOBALS['visiteur_session']['login'] . '/';
	}
}

// http://doc.spip.org/@fichier_admin
function fichier_admin($action, $pref='admin_') {

	return $pref . 
	  substr(md5($action.(time() & ~2047).$GLOBALS['visiteur_session']['login']), 0, 10);
}

// demande la creation d'un repertoire et sort
// ou retourne sans rien faire si repertoire deja la.

// http://doc.spip.org/@debut_admin
function debut_admin($script, $action='', $corps='') {

	if ((!$action) || !(autoriser('webmestre') OR autoriser('chargerftp'))) {
		include_spip('inc/minipres');
		return minipres();
	} else {
		$dir = dir_admin();
		$signal = fichier_admin($script);
		if (@file_exists($dir . $signal)) {
			spip_log ("Action admin: $action");
			return '';
		}
		include_spip('inc/minipres');

	// Si on est un super-admin, un bouton de validation suffit
	// sauf dans les cas destroy
		if ((autoriser('webmestre') OR $script === 'repair')
		AND $script != 'delete_all') {
			if (_request('validation_admin') == $signal) {
				spip_log ("Action super-admin: $action");
				return '';
			}
			$corps .= '<input type="hidden" name="validation_admin" value="'.$signal.'" />';
			$suivant = _T('bouton_valider');
			$js = '';
		} else {
			// cet appel permet d'assurer un copier-coller du nom du repertoire a creer dans tmp (esj)
			// l'insertion du script a cet endroit n'est pas xhtml licite mais evite de l'embarquer dans toutes les pages minipres
			$corps .= http_script('',  "spip_barre.js");

			$corps .= "<fieldset><legend>"
			. _T('info_authentification_ftp')
			. aide("ftp_auth")
			. "</legend>\n<label for='fichier'>"
			. _T('info_creer_repertoire')
			. "</label>\n"
            . "<span id='signal' class='formo'>".$signal."</span>"
            . "<input type='hidden' id='fichier' name='fichier' value='" 
			. $signal
			. "' />"
			. _T('info_creer_repertoire_2', array('repertoire' => joli_repertoire($dir)))
			. "</fieldset>";

			$suivant = _T('bouton_recharger_page');

			// code volontairement tordu:
			// provoquer la copie dans le presse papier du nom du repertoire
			// en remettant a vide le champ pour que ca marche aussi en cas
			// de JavaScript inactif.
			$js = " onload='var range=document.createRange(); var signal = document.getElementById(\"signal\"); var userSelection = window.getSelection(); range.setStart(signal,0); range.setEnd(signal,1); userSelection.addRange(range);'";

		}

		// admin/xxx correspond
		// a exec/base_xxx de preference
		// et exec/xxx sinon (compat)
		if (tester_url_ecrire("base_$script"))
			$script = "base_$script";
		$form = copy_request($script, $corps, $suivant);
		$info_action = _T('info_action', array('action' => "$action"));
		return minipres($info_action, $form, $js);
	}
}

// http://doc.spip.org/@fin_admin
function fin_admin($action) {
	$signal = dir_admin() . fichier_admin($action);
	spip_unlink($signal);
	if ($action != 'delete_all') {
		effacer_meta($action);
		effacer_meta('admin');
		spip_log("efface les meta admin et $action ");
	}
}

// http://doc.spip.org/@copy_request
function copy_request($script, $suite, $submit='')
{
	include_spip('inc/filtres');
	foreach(array_merge($_POST,$_GET) as $n => $c) {
		if (!in_array($n,array('fichier','exec','validation_admin')) AND !is_array($c))
		$suite .= "\n<input type='hidden' name='".spip_htmlspecialchars($n)."' value='" .
			entites_html($c) .
			"'  />";
	}
	return  generer_form_ecrire($script, $suite, '', $submit);
}
?>
