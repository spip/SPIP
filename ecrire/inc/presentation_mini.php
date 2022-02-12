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
 * Fonctions d'affichage pour l'espace privé (hors squelettes)
 *
 * @package SPIP\Core\Affichage
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne le code HTML d'un début de cadre pour le centre de page (haut de page)
 *
 * @return string Code HTML
 */
function debut_grand_cadre() {
 return "\n<div class = 'table_page'>\n";
}

/**
 * Retourne le code HTML d'une fin de cadre pour le centre de page (haut de page)
 *
 * @return string Code HTML
 */
function fin_grand_cadre() {
 return "\n</div>";
}

// Debut de la colonne de gauche
// div navigation fermee par creer_colonne_droite qui ouvre
// div extra lui-meme ferme par debut_droite qui ouvre
// div contenu lui-meme ferme par fin_gauche() ainsi que
// div conteneur

/**
 * Retourne le code HTML du début de la colonne gauche
 *
 * @return string Code HTML
 */
function debut_gauche() {
	return "<div id = 'conteneur' class = ''>\n<div id = 'navigation' class = 'lat' role = 'contentinfo'>\n";
}

/**
 * Retourne le code HTML de la fin de la colonne
 *
 * @return string Code HTML
 */
function fin_gauche() {
 return "</div></div><br class = 'nettoyeur' />";
}

/**
 * Retourne le code HTML du changement de colonne (passer de la gauche à la droite)
 *
 * @return string Code HTML
 */
function creer_colonne_droite() {
	static $deja_colonne_droite;
	if ($deja_colonne_droite) {
		return '';
	}
	$deja_colonne_droite = true;

	return "\n</div><div id='extra' class='lat' role='complementary'>";
}

/**
 * Retourne le code HTML de la colonne droite et du centre de page
 *
 * @return string Code HTML
 */
function debut_droite() {
	return liste_objets_bloques(_request('exec'))
	. creer_colonne_droite()
	. '</div>'
	. "\n<div id='contenu'>";
}

/**
 * Retourne la liste des objets édités récemment (si les drapeaux d'édition sont actifs)
 *
 * Si notre page est une page d'édition d'un objet, on déclare au passage l'auteur
 * comme éditant l'objet
 *
 * @uses signale_edition()
 * @uses liste_drapeau_edition()
 *
 * @param string $exec
 *     Nom de la page exec en cours
 * @param array $contexte
 *     contexte de la page
 * @param array|null $auteur
 *     Session de l'auteur. Sera prise sur l'auteur connecté si non indiquée.
 * @return string
 *     Code HTML
 **/
function liste_objets_bloques($exec, $contexte = [], $auteur = null) {
	$res = '';
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		if (is_null($auteur)) {
			$auteur = $GLOBALS['visiteur_session'];
		}
		if (
			$en_cours = trouver_objet_exec($exec)
			and $en_cours['edition']
			and $type = $en_cours['type']
			and ((isset($contexte[$en_cours['id_table_objet']]) and $id = $contexte[$en_cours['id_table_objet']])
				or $id = _request($en_cours['id_table_objet']))
		) {
			// marquer le fait que l'objet est ouvert en edition par toto
			// a telle date ; une alerte sera donnee aux autres redacteurs
			signale_edition($id, $auteur, $type);
		}

		$objets_ouverts = liste_drapeau_edition($auteur['id_auteur']);
		if (count($objets_ouverts)) {
			$res .= recuperer_fond('prive/objets/liste/objets-en-edition', [], ['ajax' => true]);
		}
	}

	return $res;
}


/**
 * Retourne le code HTML de fin de page de l'interface privée.
 *
 * Elle génère au passage un appel pour déclencher les tâches cron
 *
 * @see f_queue() Pour l'appel au cron
 *
 * @return string Code HTML
 **/
function fin_page() {
	include_spip('inc/pipelines');
	// avec &var_profile=1 on a le tableau de mesures SQL
	$debug = ((_request('exec') !== 'valider_xml')
		and ((_request('var_mode') == 'debug')
			or (isset($GLOBALS['tableau_des_temps']) and $GLOBALS['tableau_des_temps'])
			and isset($_COOKIE['spip_admin'])));
	$t = '</div><div id="pied"><div class="largeur">'
		. recuperer_fond('prive/squelettes/inclure/pied')
		. '</div>'
		. '</div></div>' // cf. div#page et div.largeur ouvertes dans conmmencer_page()
		. ($debug ? erreur_squelette() : '')
		. "</body></html>\n";

	return f_queue($t);
}

/**
 * Retourne des tests javascript à exécuter
 *
 * - Teste que javascript est actif : si non, un hit sur exec=test_ajax est généré
 * - Rejoue la session si demandé (par verifier_session() si l'ip a changé)
 *
 * @see exec_test_ajax_dist()
 * @see verifier_session()
 *
 * @return string Code HTML
 **/
function html_tests_js() {
	if (_SPIP_AJAX and !defined('_TESTER_NOSCRIPT')) {
		// pour le pied de page (deja defini si on est validation XML)
		define(
			'_TESTER_NOSCRIPT',
			"<noscript>\n<div style='display:none;'><img src='"
			. generer_url_ecrire('test_ajax', 'js=-1')
			. "' width='1' height='1' alt='' /></div></noscript>\n"
		);
	}

	$rejouer = '';
	if (defined('_SESSION_REJOUER')) {
		$rejouer = (_SESSION_REJOUER === true) ? rejouer_session() : _SESSION_REJOUER;
	}

	return $rejouer . (defined('_TESTER_NOSCRIPT') ? _TESTER_NOSCRIPT : '');
}

/**
 * Retourne la liste des mises à jour de SPIP possibles
 *
 * @return string texte présentant la liste des mises à jour existantes
 **/
function info_maj_spip() {

	$maj = $GLOBALS['meta']['info_maj_spip'] ?? null;
	if (!$maj) {
		return '';
	}

	$maj = explode('|', $maj);
	// c'est une ancienne notif, on a fait la maj depuis !
	if ($GLOBALS['spip_version_branche'] !== reset($maj)) {
		return '';
	}

	if (!autoriser('webmestre')) {
		return '';
	}

	array_shift($maj);
	$maj = implode('|', $maj);

	return "$maj<br />";
}

/**
 * Retourne les informations de copyright (version de SPIP, de l'écran de sécurité)
 * pour le pied de page de l'espace privé
 *
 * @return string Code HTML
 **/
function info_copyright() {

	$version = $GLOBALS['spip_version_affichee'];

	//
	// Mention, le cas echeant, de la revision SVN courante
	//
	if ($vcs = version_vcs_courante(_DIR_RACINE, true)) {
		if ($vcs['vcs'] === 'GIT') {
			$url = 'https://git.spip.net/spip/spip/commit/' . $vcs['commit'];
		} elseif ($vcs['vcs'] === 'SVN') {
			$url = 'https://core.spip.net/projects/spip/repository/revisions/' . $vcs['commit'];
		} else {
			$url = '';
		}
		// affichage "GIT [master: abcdef]"
		$commit = $vcs['commit_short'] ?? $vcs['commit'];
		if ($url) {
			$commit = "<a href=\"$url\" target=\"_blank\" rel=\"noopener noreferrer\">$commit</a>";
		}
		if ($vcs['branch']) {
			$commit = $vcs['branch'] . ': ' . $commit;
		}
		$version .= " {$vcs['vcs']} [$commit]";
	}

	// et la version de l'ecran de securite
	$secu = defined('_ECRAN_SECURITE')
		? '<br />' . _T('ecran_securite', ['version' => _ECRAN_SECURITE])
		: '';

	return _T(
		'info_copyright',
		[
			'spip' => "<b>SPIP $version</b> ",
			'lien_gpl' =>
				"<a href='" . generer_url_ecrire(
					'aide',
					'aide=licence&var_lang=' . $GLOBALS['spip_lang']
				) . "' class=\"aide popin\">" . _T('info_copyright_gpl') . '</a>'
		]
	)
	. $secu;
}

/**
 * Retourne un formulaire de recherche pour l'espace privé
 *
 * Préférez l'usage en squelettes via la balise `#FORMULAIRE_RECHERCHE_ECRIRE`.
 *
 * @see formulaires_recherche_ecrire_charger_dist()
 *
 * @param string $page Nom de la page exec
 * @param string $complement Code HTML supplémentaire
 * @return string             Code HTML
 **/
function formulaire_recherche($page, $complement = '') {
	$recherche = _request('recherche');
	$recherche_aff = entites_html($recherche);
	if (!strlen($recherche)) {
		$recherche_aff = _T('info_rechercher');
		$onfocus = " onfocus=\"this.value='';\"";
	} else {
		$onfocus = '';
	}

	$form = '<input type="text" size="10" value="' . $recherche_aff . '" name="recherche" class="recherche" accesskey="r"' . $onfocus . ' />';
	$form .= "<input type='image' src='" . chemin_image('rechercher-20.png') . "' name='submit' class='submit' alt='" . _T('info_rechercher') . "' />";

	return "<div class='spip_recherche'>" . generer_form_ecrire($page, $form . $complement, " method='get'") . '</div>';
}
