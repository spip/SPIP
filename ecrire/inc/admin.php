<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion d'administration d'un SPIP
 *
 * @package SPIP\Core\Admin
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Teste qu'un utilisateur a des droits sur les fichiers du site et
 * exécute l'action (en base) demandée si c'est le cas.
 *
 * Demande / vérifie le droit de création de répertoire par le demandeur;
 * Mémorise dans les meta que ce script est en cours d'exécution.
 * Si elle y est déjà c'est qu'il y a eu suspension du script, on reprend.
 *
 * @uses debut_admin()
 * @uses admin_verifie_session()
 * @uses fin_admin()
 *
 * @param string $script
 *     Script d'action (en base) à exécuter si on a des droits d'accès aux fichiers
 * @param string $titre
 *     Titre de l'action demandée
 * @param string $comment
 *     Commentaire supplémentaire
 * @param bool $anonymous
 *     ?
 * @return string
 *     Code HTML de la page (pour vérifier les droits),
 *     sinon code HTML de la page après le traitement effectué.
 **/
function inc_admin_dist($script, $titre, $comment = '', $anonymous = false) {
	$reprise = true;
	if (
		!isset($GLOBALS['meta'][$script])
		or !isset($GLOBALS['meta']['admin'])
	) {
		$reprise = false;
		$res = debut_admin($script, $titre, $comment);
		if ($res) {
			return $res;
		}
		spip_log("meta: $script " . print_r($_POST, true));
		ecrire_meta($script, serialize($_POST));
	}

	$res = admin_verifie_session($script, $anonymous);
	if ($res) {
		return $res;
	}
	$base = charger_fonction($script, 'base');
	$base($titre, $reprise);
	fin_admin($script);

	return '';
}

/**
 * Gestion dans la meta "admin" du script d'administation demandé,
 * pour éviter des exécutions en parallèle, notamment après Time-Out.
 *
 * Cette meta contient le nom du script et, à un hachage près, du demandeur.
 * Le code de ecrire/index.php dévie toute demande d'exécution d'un script
 * vers le script d'administration indiqué par cette meta si elle est là.
 *
 * Au niveau de la fonction inc_admin, on controle la meta 'admin'.
 *
 * - Si la meta n'est pas là, c'est le début on la crée.
 * - Sinon, si le hachage actuel est le même que celui en base,
 *   c'est une reprise, on continue.
 * - Sinon, si le hachage diffère à cause du connect, c'est une arrivée
 *   inoppotune, on refuse sa connexion.
 * - Enfin, si hachage diffère pour une autre raison, c'est que l'operation
 *   se passe mal, on la stoppe
 *
 * @uses fichier_admin()
 *
 * @param string $script
 *     Script d'action (en base)
 * @param bool $anonymous
 *     ?
 * @return string
 *     Code HTML si message d'erreur, '' sinon;
 */
function admin_verifie_session($script, $anonymous = false) {
	include_spip('base/abstract_sql');
	$pref = sprintf('_%d_', $GLOBALS['visiteur_session']['id_auteur']);
	$signal = fichier_admin($script, "$script$pref");
	$valeur = sql_getfetsel('valeur', 'spip_meta', "nom='admin'");
	if ($valeur === null) {
		ecrire_meta('admin', $signal, 'non');
	} else {
		if (!$anonymous and ($valeur != $signal)) {
			if (
				!preg_match('/^(.*)_(\d+)_/', $GLOBALS['meta']['admin'], $l)
				or intval($l[2]) != $GLOBALS['visiteur_session']['id_auteur']
			) {
				include_spip('inc/minipres');
				spip_log("refus de lancer $script, priorite a $valeur");

				return minipres(_T('info_travaux_texte'), '', ['status' => 503]);
			}
		}
	}
	$journal = 'spip';
	if (autoriser('configurer')) {
		// c'est une action webmestre, soit par ftp soit par statut webmestre
		$journal = 'webmestre';
	}
	// on pourrait statuer automatiquement les webmestres a l'init d'une action auth par ftp ... ?

	spip_log("admin $pref" . ($valeur ? ' (reprise)' : ' (init)'), $journal);

	return '';
}

/**
 * Retourne l'emplacement du répertoire où sera testé l'accès utilisateur
 *
 * Dans le répertoire temporaire si on est admin, sinon dans le répertoire
 * de transfert des admins restreints
 *
 * @return string
 *     Chemin du répertoire.
 **/
function dir_admin() {
	if (autoriser('configurer')) {
		return _DIR_TMP;
	} else {
		return _DIR_TRANSFERT . $GLOBALS['visiteur_session']['login'] . '/';
	}
}

/**
 * Retourne le nom d'un fichier de teste d'authentification par accès
 * aux fichiers
 *
 * Le nom calculé est un hash basé sur l’heure, l’action et l’auteur.
 *
 * @param string $action
 *     Nom du script d'action (en base)
 * @param string $pref
 *     Préfixe au nom du fichier calculé
 * @return string
 *     Nom du fichier
 **/
function fichier_admin($action, $pref = 'admin_') {
	return $pref .
	substr(md5($action . (time() & ~2047) . $GLOBALS['visiteur_session']['login']), 0, 10);
}

/**
 * Demande la création d'un répertoire (pour tester l'accès de l'utilisateur)
 * et sort ou quitte sans rien faire si le répertoire est déjà là.
 *
 * Si l'on est webmestre, la plupart des actions n'ont pas besoin
 * de tester la création du répertoire (toutes sauf repair ou delete_all).
 * On considère qu'un webmestre a déjà du prouver ses droits sur les fichiers.
 * Dans ce cas, on quitte sans rien faire également.
 *
 * @uses dir_admin()
 * @uses fichier_admin()
 *
 * @param string $script
 *     Script d'action (en base) à exécuter ensuite
 * @param string $action
 *     Titre de l'action demandée
 * @param string $corps
 *     Commentaire supplémentaire
 * @return string
 *     Code HTML de la page (pour vérifier les droits),
 *     sinon chaîne vide si déjà fait.
 **/
function debut_admin($script, $action = '', $corps = '') {
	if ((!$action) || !(autoriser('webmestre') or autoriser('chargerftp'))) {
		include_spip('inc/minipres');

		return minipres();
	} else {
		$dir = dir_admin();
		$signal = fichier_admin($script);
		if (@file_exists($dir . $signal)) {
			spip_log("Action admin: $action");

			return '';
		}
		include_spip('inc/minipres');

		// Si on est un super-admin, un bouton de validation suffit
		// sauf dans les cas destroy
		if (
			(autoriser('webmestre') or $script === 'repair')
			and $script != 'delete_all'
		) {
			if (_request('validation_admin') == $signal) {
				spip_log("Action super-admin: $action");

				return '';
			}
			$corps .= '<input type="hidden" name="validation_admin" value="' . $signal . '" />';
			$suivant = _T('bouton_valider');
			$js = '';
		} else {
			// cet appel permet d'assurer un copier-coller du nom du repertoire a creer dans tmp (esj)
			// l'insertion du script a cet endroit n'est pas xhtml licite
			// mais evite de l'embarquer dans toutes les pages minipres
			$corps .= http_script('', 'spip_barre.js');

			$corps .= '<fieldset><legend>'
				. _T('info_authentification_ftp')
				. aider('ftp_auth')
				. "</legend>\n<label for='fichier'>"
				. _T('info_creer_repertoire')
				. "</label>\n"
				. "<span id='signal' class='formo'>" . $signal . '</span>'
				. "<input type='hidden' id='fichier' name='fichier' value='"
				. $signal
				. "' />"
				. _T('info_creer_repertoire_2', ['repertoire' => joli_repertoire($dir)])
				. '</fieldset>';

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
		if (tester_url_ecrire("base_$script")) {
			$script = "base_$script";
		}
		$form = copy_request($script, $corps, $suivant);
		$info_action = _T('info_action', ['action' => "$action"]);

		return minipres($info_action, $form, ['onload' => $js]);
	}
}

/**
 * Clôture la phase d'administration en supprimant le répertoire
 * testant l'accès au fichiers ainsi que les metas d'exécution
 *
 * @param string $action
 *     Nom de l'action (en base) qui a été exécutée
 **/
function fin_admin($action) {
	$signal = dir_admin() . fichier_admin($action);
	spip_unlink($signal);
	if ($action != 'delete_all') {
		effacer_meta($action);
		effacer_meta('admin');
		spip_log("efface les meta admin et $action ");
	}
}

/**
 * Génère un formulaire avec les données postées
 *
 * Chaque donnée est mise en input hidden pour
 * les soumettre avec la validation du formulaire.
 *
 * @param string $script
 *     Nom du script (pour l'espace privé) de destination
 * @param string $suite
 *     Corps du formulaire
 * @param string $submit
 *     texte du bouton de validation
 * @return string
 *     Code HTML du formulaire
 **/
function copy_request($script, $suite, $submit = '') {
	include_spip('inc/filtres');
	foreach (array_merge($_POST, $_GET) as $n => $c) {
		if (!in_array($n, ['fichier', 'exec', 'validation_admin']) and !is_array($c)) {
			$suite .= "\n<input type='hidden' name='" . spip_htmlspecialchars($n) . "' value='" .
				entites_html($c) .
				"'  />";
		}
	}

	return generer_form_ecrire($script, $suite, '', $submit);
}
