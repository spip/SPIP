<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion du sélecteur de rubrique pour les objets éditoriaux s'insérant
 * dans une hiérarchie de rubriques
 *
 * @package SPIP\Core\Rubriques
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_SPIP_SELECT_RUBRIQUES')) {
	/**
	 * @var int Nombre de rubriques maximum du sélecteur de rubriques.
	 * Au delà, on bascule sur un sélecteur ajax.
	 * mettre 100000 pour desactiver ajax
	 */
	define('_SPIP_SELECT_RUBRIQUES', 20);
}

/**
 * Sélecteur de rubriques pour l'espace privé
 *
 * @uses selecteur_rubrique_html()
 * @uses selecteur_rubrique_ajax()
 *
 * @param int $id_rubrique
 *     Identifiant de rubrique courante (0 si NEW)
 * @param string $type
 *     Type de l'objet à placer.
 *
 *     Une rubrique peut aller à la racine mais pas dans elle-même,
 *     les articles et sites peuvent aller n'importe où (défaut),
 *     et les brèves dans les secteurs.
 * @param bool $restreint
 *     True pour indiquer qu'il faut limiter les rubriques affichées
 *     aux rubriques éditables par l'admin restreint
 * @param int $idem
 *     En mode rubrique, identifiant de soi-même
 * @param string $do
 *     Type d'action
 * @return string
 *     Code HTML du sélecteur
 **/
function inc_chercher_rubrique_dist($id_rubrique, $type, $restreint, $idem = 0, $do = 'aff') {
	if (sql_countsel('spip_rubriques') < 1) {
		return '';
	}

	// Mode sans Ajax :
	// - soit parce que le cookie ajax n'est pas la
	// - soit parce qu'il y a peu de rubriques
	if (
		_SPIP_AJAX < 1
		|| $type == 'breve'
		|| sql_countsel('spip_rubriques') < _SPIP_SELECT_RUBRIQUES
	) {
		return selecteur_rubrique_html($id_rubrique, $type, $restreint, $idem);
	} else {
		return selecteur_rubrique_ajax($id_rubrique, $type, $restreint, $idem, $do);
	}
}

// compatibilite pour extensions qui utilisaient l'ancien nom
$GLOBALS['selecteur_rubrique'] = 'inc_chercher_rubrique_dist';

/**
 * Styles appliqués sur le texte d'une rubrique pour créer visuellement
 * une indentation en fonction de sa profondeur dans le sélecteur
 *
 * @param int $i
 *     Profondeur de la rubrique
 * @return array
 *     Liste (classe CSS, styles en ligne, Espaces insécables)
 **/
function style_menu_rubriques($i) {

	$espace = '';
	$style = '';
	for ($count = 1; $count <= $i; $count++) {
		$espace .= '&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	if ($i == 1) {
		$espace = '';
	}
	$class = "niveau_$i";

	return [$class, $style, $espace];
}

/**
 * Sélecteur de sous rubriques pour l'espace privé
 *
 * @uses style_menu_rubriques()
 *
 * @param int $id_rubrique
 *     Identifiant de parente
 * @param int $root
 * @param int $niv
 * @param array $data
 * @param array $enfants
 * @param int $exclus
 * @param bool $restreint
 *     True pour indiquer qu'il faut limiter les rubriques affichées
 *     aux rubriques éditables par l'admin restreint
 * @param string $type
 *     Type de l'objet à placer.
 * @return string
 *     Code HTML du sélecteur
 **/
function sous_menu_rubriques($id_rubrique, $root, $niv, &$data, &$enfants, $exclus, $restreint, $type) {
	static $decalage_secteur;

	// Si on a demande l'exclusion ne pas descendre dans la rubrique courante
	if ($exclus > 0 && $root == $exclus) {
		return '';
	}

	// en fonction du niveau faire un affichage plus ou moins kikoo

	// selected ?
	$selected = ($root == $id_rubrique) ? ' selected="selected"' : '';

	// le style en fonction de la profondeur
	[$class, $style, $espace] = style_menu_rubriques($niv);

	$class .= ' selec_rub';

	// creer l'<option> pour la rubrique $root

	if (isset($data[$root])) { # pas de racine sauf pour les rubriques
	$r = "<option$selected value='$root' class='$class' style='$style'>$espace"
			. $data[$root]
			. '</option>' . "\n";
	} else {
		$r = '';
	}

	// et le sous-menu pour ses enfants
	$sous = '';
	if (isset($enfants[$root])) {
		foreach ($enfants[$root] as $sousrub) {
			$sous .= sous_menu_rubriques(
				$id_rubrique,
				$sousrub,
				$niv + 1,
				$data,
				$enfants,
				$exclus,
				$restreint,
				$type
			);
		}
	}

	// si l'objet a deplacer est publie, verifier qu'on a acces aux rubriques
	if ($restreint && $root != $id_rubrique && !autoriser('publierdans', 'rubrique', $root)) {
		return $sous;
	}

	// et voila le travail
	return $r . $sous;
}

/**
 * Sélecteur de rubriques pour l'espace privé en mode classique (menu)
 *
 * @uses sous_menu_rubriques()
 *
 * @param int $id_rubrique
 *     Identifiant de rubrique courante (0 si NEW)
 * @param string $type
 *     Type de l'objet à placer.
 * @param bool $restreint
 *     True pour indiquer qu'il faut limiter les rubriques affichées
 *     aux rubriques éditables par l'admin restreint
 * @param int $idem
 *     En mode rubrique, identifiant de soi-même
 * @return string
 *     Code HTML du sélecteur
 **/
function selecteur_rubrique_html($id_rubrique, $type, $restreint, $idem = 0) {
	$enfants = [];
	$data = [];
	if ($type == 'rubrique' && autoriser('publierdans', 'rubrique', 0)) {
		$data[0] = _T('info_racine_site');
	}
	# premier choix = neant
	# si auteur (rubriques restreintes)
	# ou si creation avec id_rubrique=0
	elseif ($type == 'auteur' || !$id_rubrique) {
		$data[0] = '&nbsp;';
	}

	//
	// creer une structure contenant toute l'arborescence
	//

	include_spip('base/abstract_sql');
	$q = sql_select(
		'id_rubrique, id_parent, titre, statut, lang, langue_choisie',
		'spip_rubriques',
		($type == 'breve' ? ' id_parent=0 ' : ''),
		'',
		'0+titre,titre'
	);
	while ($r = sql_fetch($q)) {
		if (autoriser('voir', 'rubrique', $r['id_rubrique'])) {
			// titre largeur maxi a 50
			$titre = couper(supprimer_tags(typo($r['titre'])) . ' ', 50);
			if (
				$GLOBALS['meta']['multi_rubriques'] == 'oui'
				&& ($r['langue_choisie'] == 'oui' || $r['id_parent'] == 0)
			) {
				$titre .= ' [' . traduire_nom_langue($r['lang']) . ']';
			}
			$data[$r['id_rubrique']] = $titre;
			$enfants[$r['id_parent']][] = $r['id_rubrique'];
			if ($id_rubrique == $r['id_rubrique']) {
				$id_parent = $r['id_parent'];
			}
		}
	}

	// si une seule rubrique comme choix possible,
	// inutile de mettre le selecteur sur un choix vide par defaut
	// sauf si le selecteur s'adresse a une rubrique puisque on peut la mettre a la racine dans ce cas
	if (
		count($data) == 2
		&& isset($data[0])
		&& !in_array($type, ['auteur', 'rubrique'])
		&& !$id_rubrique
	) {
		unset($data[0]);
	}


	$opt = sous_menu_rubriques($id_rubrique, 0, 0, $data, $enfants, $idem, $restreint, $type);
	$att = " id='id_parent' name='id_parent'\nclass='selecteur_parent verdana1'";

	if (preg_match(',^<option[^<>]*value=.(\d*).[^<>]*>([^<]*)</option>$,', $opt, $r)) {
		$r = "<input$att type='hidden' value='" . $r[1] . "'>" . $r[2];
	} else {
		$r = '<select' . $att . " size='1'>\n$opt</select>\n";
	}

	# message pour neuneus (a supprimer ?)
#	if ($type != 'auteur' AND $type != 'breve')
#		$r .= "\n<br />"._T('texte_rappel_selection_champs');

	return $r;
}

/**
 * Sélecteur de rubrique pour l'espace privé, en mode AJAX
 *
 * @note
 *   `$restreint` indique qu'il faut limiter les rubriques affichées
 *   aux rubriques éditables par l'admin restreint... or, ca ne marche pas.
 *   Pour la version HTML c'est bon (cf. ci-dessus), mais pour l'ajax...
 *   je laisse ça aux spécialistes de l'ajax & des admins restreints
 *
 *   Toutefois c'est juste un pb d'interface, car question securite
 *   la vérification est faite à l'arrivée des données (Fil)
 *
 * @uses construire_selecteur()
 * @see  exec_selectionner_dist() Pour l'obtention du contenu AJAX ensuite
 *
 * @param int $id_rubrique
 *     Identifiant de rubrique courante (0 si NEW)
 * @param string $type
 *     Type de l'objet à placer.
 * @param bool $restreint
 *     True pour indiquer qu'il faut limiter les rubriques affichées
 *     aux rubriques éditables par l'admin restreint. Ne fonctionne actuellement pas ici.
 * @param int $idem
 *     En mode rubrique, identifiant de soi-même
 * @param string $do
 *     Type d'action
 * @return string
 *     Code HTML du sélecteur
 */
function selecteur_rubrique_ajax($id_rubrique, $type, $restreint, $idem = 0, $do = 'aff') {

	if ($id_rubrique) {
		$titre = sql_getfetsel('titre', 'spip_rubriques', 'id_rubrique=' . (int) $id_rubrique);
	} else {
		$titre = $type == 'auteur' ? '&nbsp;' : _T('info_racine_site');
	}

	$titre = str_replace('&amp;', '&', (string) entites_html(textebrut(typo($titre))));
	$init = " disabled='disabled' type='text' value=\"" . $titre . "\"\nstyle='width:300px;'";

	$url = generer_url_ecrire('selectionner', "id=$id_rubrique&type=$type&do=$do"
		. ($idem ? "&exclus=$idem" : '')
		. ($restreint ? '' : '&racine=oui')
		. (isset($GLOBALS['var_profile']) ? '&var_profile=1' : ''));


	return construire_selecteur($url, '', 'selection_rubrique', 'id_parent', $init, $id_rubrique);
}

/**
 * Construit un bloc permettant d'activer le sélecteur de rubrique AJAX
 *
 * Construit un bloc comportant une icone clicable avec image animée à côté
 * pour charger en Ajax du code à mettre sous cette icone.
 *
 * @note
 *   Attention: changer le onclick si on change le code Html.
 *   (la fonction JS charger_node ignore l'attribut id qui ne sert en fait pas;
 *   getElement en mode Ajax est trop couteux).
 *
 * @param string $url
 *     URL qui retournera le contenu du sélecteur en AJAX
 * @param string $js
 *     Code javascript ajouté sur onclick
 * @param string $idom
 *     Identifiant donné à l'image activant l'ajax et au block recevant son contenu
 * @param string $name
 *     Nom du champ à envoyer par le formulaire
 * @param string $init
 *     Code HTML à l'intérieur de l'input titreparent
 * @param int $id
 *     Valeur actuelle du champ
 * @return string
 *     Code HTML du sélecteur de rubrique AJAX
 **/
function construire_selecteur($url, $js, $idom, $name, $init = '', $id = 0) {
	$icone = (str_contains($idom, 'auteur')) ? 'auteur-24.png' : 'rechercher-20.png';
	// si icone de recherche on embed le svg
	$balise = ($icone === 'rechercher-20.png' ? chercher_filtre('balise_svg') : chercher_filtre('balise_img'));
	$img_icone = $balise(chemin_image($icone), _T('titre_image_selecteur'));

	return
		"<div class='rubrique_actuelle'><a href='#' class='rubrique-search' role='button' style='display:inline-flex;vertical-align:middle;' onclick=\""
		. $js
		. " jQuery(this).toggleClass('toggled'); "
		. "return charger_node_url_si_vide('"
		. $url
		. "', this.parentNode.nextSibling, this.nextSibling,'',event)\" title='" . attribut_html(_T('titre_image_selecteur')) . "'>"
		. $img_icone
		. "</a><img src='"
		. chemin_image('loader.svg')
		. "' class='loader' id='img_"
		. $idom
		. "'\nstyle='visibility: hidden;' alt='*'>"
		. "<input id='titreparent' name='titreparent' class='text'"
		. $init
		. '>'
		. "<input type='hidden' id='$name' name='$name' value='"
		. $id
		. "'><div class='nettoyeur'></div></div><div id='"
		. $idom
		. "'\nstyle='display: none;'></div>";
}
