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
 * Fonctions déclarées dans des pipelines (espace privé)
 *
 * @package SPIP\Core\Pipelines
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Inserer jQuery et ses plugins pour l'espace privé
 *
 * La fonction ajoute les balises scripts dans le texte qui appelent
 * les scripts jQuery ainsi que certains de ses plugins. La liste
 * des js chargée peut être complété par le pipeline 'jquery_plugins'
 *
 * Cette fonction est appelée par le pipeline header_prive
 *
 * @see f_jQuery()
 * @link https://code.spip.net/@f_jQuery
 *
 * @param string $texte Contenu qui sera inséré dans le head HTML
 * @return string          Contenu complété des scripts javascripts, dont jQuery
 **/
function f_jQuery_prive($texte) {
	$x = '';
	$jquery_plugins = pipeline(
		'jquery_plugins',
		[
			'prive/javascript/jquery.js',
			'prive/javascript/jquery.form.js',
			'prive/javascript/jquery.autosave.js',
			'prive/javascript/jquery.placeholder-label.js',
			'prive/javascript/ajaxCallback.js',
			'prive/javascript/js.cookie.js',
			'prive/javascript/jquery.cookie.js',
			'prive/javascript/spip_barre.js',
		]
	);
	foreach (array_unique($jquery_plugins) as $script) {
		if ($script = find_in_path(supprimer_timestamp($script))) {
			$script = timestamp($script);
			$x .= "\n<script src=\"$script\" type=\"text/javascript\"></script>\n";
		}
	}
	// inserer avant le premier script externe ou a la fin
	if (
		preg_match(',<script[^><]*src=,', $texte, $match)
		and $p = strpos($texte, $match[0])
	) {
		$texte = substr_replace($texte, $x, $p, 0);
	} else {
		$texte .= $x;
	}

	return $texte;
}


/**
 * Ajout automatique du title dans les pages du privé en squelette
 *
 * Appellé dans le pipeline affichage_final_prive
 *
 * @param string $texte
 * @return string
 */
function affichage_final_prive_title_auto($texte) {
	if (
		strpos($texte, '<title>') === false
		and
		(preg_match(',<h1[^>]*>(.+)</h1>,Uims', $texte, $match)
			or preg_match(',<h[23][^>]*>(.+)</h[23]>,Uims', $texte, $match))
		and $match = textebrut(trim($match[1]))
		and ($p = strpos($texte, '<head>')) !== false
	) {
		if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']['nom_site']))) {
			$nom_site_spip = _T('info_mon_site_spip');
		}

		$titre = '<title>['
			. $nom_site_spip
			. '] ' . $match
			. '</title>';

		$texte = substr_replace($texte, $titre, $p + 6, 0);
	}

	return $texte;
}


// Fonction standard pour le pipeline 'boite_infos'
// https://code.spip.net/@f_boite_infos
function f_boite_infos($flux) {
	$args = $flux['args'];
	$type = $args['type'];
	unset($args['row']);
	if (!trouver_fond($type, 'prive/objets/infos/')) {
		$type = 'objet';
	}
	$args['espace_prive'] = 1;
	$flux['data'] .= recuperer_fond("prive/objets/infos/$type", $args);

	return $flux;
}


/**
 * Utilisation du pipeline recuperer_fond dans le prive
 *
 * Branchement automatise de affiche_gauche, affiche_droite, affiche_milieu
 * pour assurer la compat avec les versions precedentes des exec en php
 * Branche de affiche_objet
 *
 * Les pipelines ne recevront plus exactement le meme contenu en entree,
 * mais la compat multi vertions pourra etre assuree
 * par une insertion au bon endroit quand le contenu de depart n'est pas vide
 *
 * @param array $flux Données du pipeline
 * @return array Données du pipeline
 */
function f_afficher_blocs_ecrire($flux) {
	static $o = [];
	if (is_string($fond = $flux['args']['fond'])) {
		$exec = isset($flux['args']['contexte']['exec']) ? $flux['args']['contexte']['exec'] : _request('exec');
		if (!isset($o[$exec])) {
			$o[$exec] = trouver_objet_exec($exec);
		}
		// cas particulier
		if ($exec == 'infos_perso') {
			$flux['args']['contexte']['id_auteur'] = $GLOBALS['visiteur_session']['id_auteur'];
		}
		$typepage = (isset($flux['args']['contexte']['type-page']) ? $flux['args']['contexte']['type-page'] : $exec);
		if ($fond == "prive/squelettes/navigation/$typepage") {
			$flux['data']['texte'] = pipeline(
				'affiche_gauche',
				['args' => $flux['args']['contexte'], 'data' => $flux['data']['texte']]
			);
		} elseif ($fond == "prive/squelettes/extra/$typepage") {
			include_spip('inc/presentation_mini');
			$flux['data']['texte'] = pipeline(
				'affiche_droite',
				['args' => $flux['args']['contexte'], 'data' => $flux['data']['texte']]
			) . liste_objets_bloques(
				$exec,
				$flux['args']['contexte']
			);
		} elseif ($fond == "prive/squelettes/hierarchie/$typepage" and $o[$exec]) {
			// id non defini sur les formulaire de nouveaux objets
			$id = isset($flux['args']['contexte'][$o[$exec]['id_table_objet']]) ? intval($flux['args']['contexte'][$o[$exec]['id_table_objet']]) : 0;
			$flux['data']['texte'] = pipeline(
				'affiche_hierarchie',
				['args' => ['objet' => $o[$exec]['type'], 'id_objet' => $id], 'data' => $flux['data']['texte']]
			);
		} elseif ($fond == "prive/squelettes/contenu/$typepage") {
			// Préparation du marqueur affiche_milieu
			// Si c'est la page d'un objet pas en édition, on l'encapsule dans un div
			$est_page_objet = !empty($o[$exec]['type']);
			$est_en_edition = (isset($o[$exec]['edition']) and $o[$exec]['edition'] === true);
			$encapsuler_milieu = ($est_page_objet and !$est_en_edition);
			$flux['data']['texte'] = afficher_blocs_ecrire_preparer_marqueur(
				$flux['data']['texte'],
				'<!--affiche_milieu-->',
				'<div id=["\']wysiwyg',
				$encapsuler_milieu ? '<div class="affiche_milieu">' : '',
				$encapsuler_milieu ? '</div>' : ''
			);
			if (
				$o[$exec]
				and $objet = $o[$exec]['type']
				and $o[$exec]['edition'] == false
				and isset($flux['args']['contexte'][$o[$exec]['id_table_objet']])
				and $id = intval($flux['args']['contexte'][$o[$exec]['id_table_objet']])
			) {
				// inserer le formulaire de traduction
				$flux['data']['texte'] = str_replace('<!--affiche_milieu-->', recuperer_fond(
					'prive/objets/editer/traductions',
					['objet' => $objet, 'id_objet' => $id, 'espace_prive' => 1]
				) . '<!--affiche_milieu-->', $flux['data']['texte']);
				$flux['data']['texte'] = pipeline('afficher_fiche_objet', [
					'args' => [
						'contexte' => $flux['args']['contexte'],
						'type' => $objet,
						'id' => $id
					],
					'data' => $flux['data']['texte']
				]);
			}
			$flux['data']['texte'] = pipeline(
				'affiche_milieu',
				['args' => $flux['args']['contexte'], 'data' => $flux['data']['texte']]
			);
		} elseif ($fond == 'prive/squelettes/inclure/pied') {
			$flux['data']['texte'] = pipeline(
				'affiche_pied',
				['args' => $flux['args']['contexte'], 'data' => $flux['data']['texte']]
			);
		} elseif (
			strncmp($fond, 'prive/objets/contenu/', 21) == 0
			and $objet = basename($fond)
			and $objet == substr($fond, 21)
			and isset($o[$objet])
			and $o[$objet]
		) {
			$id = intval($flux['args']['contexte'][$o[$exec]['id_table_objet']]);
			$flux['data']['texte'] = pipeline('afficher_contenu_objet', [
				'args' => ['type' => $objet, 'id_objet' => $id, 'contexte' => $flux['args']['contexte']],
				'data' => $flux['data']['texte']
			]);
		}
	}

	return $flux;
}

/**
 * Vérifie la présence d'un marqueur dans le HTML et l'ajoute si nécessaire.
 *
 * Fonction à appeler avant d'insérer des contenus via pipelines.
 * Le marqueur peut optionnellement être encapsulé dans des balises.
 * S'il est déjà présent et encapsulé, il ne faut PAS qu'il y ait d'espace blanc en trop.
 *
 * @param string $texte
 *     Contenu HTML
 * @param string $marqueur
 *     Marqueur cherché, tel que `<!--affiche_milieu-->`
 * @param string $inserer_avant
 *     Expression régulière indiquant avant quel élément insérer le marqueur s'il est absent
 *     Exemple : `<div id=["\']wysiwyg`
 * @param string $ouvrir
 *     Balise ouvrante si le marqueur doit être encapsulé
 *     Exemple : `<div class="affiche_milieu">`
 * @param string $fermer
 *     Balise fermante si le marqueur doit être encapsulé
 *     Exemple : `</div>`
 * @return string
 *     HTML avec le marqueur, ou inchangé si ajout impossible.
 */
function afficher_blocs_ecrire_preparer_marqueur(?string $texte, string $marqueur, string $inserer_avant, string $ouvrir = '', string $fermer = ''): ?string {

	if ($texte) {
		$encapsuler = (($ouvrir and $fermer) ? true : false);
		$marqueur_pos = strpos($texte, $marqueur);
		$full_marqueur = "$ouvrir$marqueur$fermer";

		// Le marqueur est absent : on l'ajoute avant l'élément indiqué
		if ($marqueur_pos  === false) {
			$texte = preg_replace(
				",$inserer_avant,",
				"$full_marqueur\\0",
				$texte
			);
		// Le marqueur est présent mais pas encapsulé : on ajoute les balises ouvrantes et fermantes.
		// Pour vérifier, on prend le texte précédent et on regarde si ça correspond à la balise ouvrante.
		// Il ne faut donc aucun espace blanc en trop.
		} elseif (
			$marqueur_pos !== false
			and $encapsuler
			and substr($texte, $marqueur_pos - strlen($ouvrir), strlen($ouvrir)) !== $ouvrir
		) {
			$texte = substr_replace(
				$texte,
				$full_marqueur,
				$marqueur_pos,
				strlen($marqueur)
			);
		}
	}

	return $texte;
}

/**
 * Afficher les taches en attente liees a un objet
 *
 * @pipeline affiche_milieu
 * @param string $flux
 * @return string
 */
function f_queue_affiche_milieu($flux) {
	$args = $flux['args'];
	$res = '';
	foreach ($args as $key => $arg) {
		if (preg_match(',^id_,', $key) and is_numeric($arg) and $arg = intval($arg)) {
			$objet = preg_replace(',^id_,', '', $key);
			$res .= recuperer_fond(
				'modeles/object_jobs_list',
				['id_objet' => $arg, 'objet' => $objet, 'espace_prive' => 1],
				['ajax' => true]
			);
		}
	}
	if ($res) {
		$flux['data'] = $res . $flux['data'];
	}

	return $flux;
}

/**
 * Trouver l'objet qui correspond à l'exec de l'espace privé passé en argument
 *
 * renvoie false si pas d'objet en cours, ou un tableau associatif
 * contenant les informations table_objet_sql,table,type,id_table_objet,edition
 *
 * @param string $exec
 *   nom de la page testee
 * @return array|bool
 */
function trouver_objet_exec(string $exec) {
	static $objet_exec = [];
	if (!$exec) {
		return false;
	}
	// cas particulier
	if ($exec === 'infos_perso') {
		$exec = 'auteur';
		set_request('id_auteur', $GLOBALS['visiteur_session']['id_auteur']);
	}
	if (!isset($objet_exec[$exec])) {
		$objet_exec[$exec] = false;
		$infos = lister_tables_objets_sql();
		foreach ($infos as $t => $info) {
			if ($exec === $info['url_edit'] and $info['editable']) {
				return $objet_exec[$exec] = [
					'edition' => $exec == $info['url_voir'] ? '' : true,
					'table_objet_sql' => $t,
					'table' => $info['table_objet'],
					'type' => $info['type'],
					'id_table_objet' => id_table_objet($info['type'])
				];
			}
			if ($exec === $info['url_voir']) {
				return $objet_exec[$exec] = [
					'edition' => false,
					'table_objet_sql' => $t,
					'table' => $info['table_objet'],
					'type' => $info['type'],
					'id_table_objet' => id_table_objet($info['type'])
				];
			}
		}
	}

	return $objet_exec[$exec];
}
