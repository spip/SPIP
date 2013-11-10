<?php

/**
 * Gestion de l'action actionner
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Actions
 */
 
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Action effectuant 1 action dans la liste des actions à réaliser
 * sur les plugins.
 *
 * Cette action sera relancée tant qu'il reste des actions à faire
 */
function action_actionner_dist() {
	// droits
	include_spip('inc/autoriser');
	if (!autoriser('configurer', '_plugins')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	include_spip('inc/svp_actionner');
	include_spip('inc/headers');
	$actionneur = new Actionneur();
	$actionneur->get_actions();
	if ($actionneur->one_action()) {
		// si SVP a été enlevé des actifs, on redirige sur la fin...
		// sinon cette page d'action/actionner devient introuvable.
		// dans ce cas précis, les autres actions prévues venant après la desactivation de SVP
		// ne pourront être traitees... SVP n'étant plus là !
		if ($actionneur->tester_si_svp_desactive()) {
			$url = _request('redirect');
		} else {
			$url = generer_action_auteur('actionner', '',  _request('redirect'));
		}

		// en mode pas à pas, on affiche un bilan entre chaque action
		// et on demande a l'utilistateur de cliquer pour realiser
		// l'action suivante.
		include_spip('inc/config');
		if (lire_config('svp/mode_pas_a_pas') == 'oui') {
			include_spip('inc/minipres');
			$pres = $actionneur->presenter_actions();
			$btn = "<a href='$url'>[ Action Suivante ]</a>";
			$styles = "
				<style type='text/css'>
				#minipres #actionner .fail {color:#c30000;}
				#minipres #actionner ul {margin-left: 0.5em;}
				#minipres #actionner li {list-style-type:square; margin-left: 0.5em;}
				</style>";
			echo minipres( _T('svp:installation_en_cours'), $pres . '<br /><br />' . $btn . $styles);
			die();
		}

		redirige_par_entete(str_replace('&amp;','&', $url));
	}
	
	include_spip('inc/svp_depoter_local');
	svp_actualiser_paquets_locaux();

	if (!_request('redirect'))
		$GLOBALS['redirect'] = generer_url_ecrire('admin_plugin');
	else
		$GLOBALS['redirect'] = str_replace('&amp;','&', _request('redirect'));

}

?>
