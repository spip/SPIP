<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');
include_spip('inc/modeles');
include_spip('inc/liens');

/**
 * Production de la balise a+href à partir des raccourcis `[xxx->url]` etc.
 *
 * @note
 *     Compliqué car c'est ici qu'on applique typo(),
 *     et en plus, on veut pouvoir les passer en pipeline
 *
 * @see typo()
 * @param string $lien
 * @param string $texte
 * @param string $class
 * @param string $title
 * @param string $hlang
 * @param string $rel
 * @param string $connect
 * @param array $env
 * @return string
 */
function inc_lien_dist(
	$lien,
	$texte = '',
	$class = '',
	$title = '',
	$hlang = '',
	$rel = '',
	string $connect = '',
	$env = []
) {
	return $lien;
}

function expanser_liens($t, string $connect = '', $env = []) {

	$t = pipeline('pre_liens', $t);

	// on passe a traiter_modeles la liste des liens reperes pour lui permettre
	// de remettre le texte d'origine dans les parametres du modele
	$t = traiter_modeles($t, false, false, $connect);

	return $t;
}

// Meme analyse mais pour eliminer les liens
// et ne laisser que leur titre, a expliciter si ce n'est fait
function nettoyer_raccourcis_typo($texte, string $connect = '') {
	return $texte;
}

// Repere dans la partie texte d'un raccourci [texte->...]
// la langue et la bulle eventuelles
function traiter_raccourci_lien_atts($texte) {
	$bulle = '';
	$hlang = '';

	return [trim((string) $texte), $bulle, $hlang];
}

define('_RACCOURCI_CHAPO', '/^(\W*)(\W*)(\w*\d+([?#].*)?)$/');
/**
 * Retourne la valeur d'un champ de redirection (articles virtuels)
 *
 * @note
 *     Pas d'action dans le noyau SPIP directement.
 *     Se réferer inc/lien du plugin Textwheel.
 *
 * @param string $virtuel
 * @param bool $url
 * @return string
 */
function virtuel_redirige($virtuel, $url = false) {
	return $virtuel;
}

// Cherche un lien du type [->raccourci 123]
// associe a une fonction generer_url_raccourci() definie explicitement
// ou implicitement par le jeu de type_urls courant.
//
// Valeur retournee selon le parametre $pour:
// 'tout' : tableau d'index url,class,titre,lang (vise <a href="U" class='C' hreflang='L'>T</a>)
// 'titre': seulement T ci-dessus (i.e. le TITRE ci-dessus ou dans table SQL)
// 'url':   seulement U  (i.e. generer_url_RACCOURCI)

function calculer_url($ref, $texte = '', $pour = 'url', string $connect = '', $echappe_typo = true) {
	$r = traiter_lien_implicite($ref, $texte, $pour, $connect);

	return $r ?: traiter_lien_explicite($ref, $texte, $pour, $connect, $echappe_typo);
}

define('_EXTRAIRE_LIEN', ',^\s*(?:' . _PROTOCOLES_STD . '):?/?/?\s*$,iS');

function traiter_lien_explicite($ref, $texte = '', $pour = 'url', string $connect = '', $echappe_typo = true) {
	if (preg_match(_EXTRAIRE_LIEN, (string) $ref)) {
		return ($pour != 'tout') ? '' : ['', '', '', ''];
	}

	$lien = entites_html(trim((string) $ref));

	// Liens explicites
	if (!$texte) {
		$texte = str_replace('"', '', (string) $lien);
		// evite l'affichage de trops longues urls.
		$lien_court = charger_fonction('lien_court', 'inc');
		$texte = $lien_court($texte);
		if ($echappe_typo) {
			$texte = '<html>' . quote_amp($texte) . '</html>';
		}
	}

	// petites corrections d'URL
	if (preg_match('/^www\.[^@]+$/S', (string) $lien)) {
		$lien = 'http://' . $lien;
	} else {
		if (strpos((string) $lien, '@') && email_valide($lien)) {
			if (!$texte) {
				$texte = $lien;
			}
			$lien = 'mailto:' . $lien;
		}
	}

	if ($pour == 'url') {
		return $lien;
	}

	if ($pour == 'titre') {
		return $texte;
	}

	return ['url' => $lien, 'titre' => $texte];
}

function liens_implicite_glose_dist($texte, $id, $type, $args, $ancre, string $connect = '') {
	return function_exists($f = 'glossaire_' . $ancre) ? $f($texte, $id) : glossaire_std($texte);
}

/**
 * Transformer un lien raccourci art23 en son URL
 * Par defaut la fonction produit une url prive si on est dans le prive
 * ou publique si on est dans le public.
 * La globale lien_implicite_cible_public permet de forcer un cas ou l'autre :
 * $GLOBALS['lien_implicite_cible_public'] = true;
 *  => tous les liens raccourcis pointent vers le public
 * $GLOBALS['lien_implicite_cible_public'] = false;
 *  => tous les liens raccourcis pointent vers le prive
 * unset($GLOBALS['lien_implicite_cible_public']);
 *  => retablit le comportement automatique
 *
 * @param string $ref
 * @param string $texte
 * @param string $pour
 * @param string $connect
 * @return array|bool|string
 */
function traiter_lien_implicite($ref, $texte = '', $pour = 'url', $connect = '') {
	$cible = $GLOBALS['lien_implicite_cible_public'] ?? null;
	if (!($match = typer_raccourci($ref))) {
		return false;
	}

	[$type, , $id, , $args, , $ancre] = array_pad($match, 7, null);

	# attention dans le cas des sites le lien doit pointer non pas sur
	# la page locale du site, mais directement sur le site lui-meme
	$url = '';
	if ($f = charger_fonction("implicite_$type", 'liens', true)) {
		$url = $f($texte, $id, $type, $args, $ancre, $connect);
	}

	if (!$url) {
		$url = generer_objet_url($id, $type, $args ?? '', $ancre ?? '', $cible, '', $connect ?? '');
	}

	if (!$url) {
		return false;
	}

	if (is_array($url)) {
		[$type, $id] = array_pad($url, 2, null);
		$url = generer_objet_url($id, $type, $args ?? '', $ancre ?? '', $cible, '', $connect ?? '');
	}

	if ($pour === 'url') {
		return $url;
	}

	$r = traiter_raccourci_titre($id, $type, $connect);
	if ($r) {
		$r['class'] = ($type == 'site') ? 'spip_out' : 'spip_in';
	}

	if ($texte = trim($texte)) {
		$r['titre'] = $texte;
	}

	if (!@$r['titre']) {
		$r['titre'] = _T($type) . " $id";
	}

	if ($pour == 'titre') {
		return $r['titre'];
	}

	$r['url'] = $url;

	// dans le cas d'un lien vers un doc, ajouter le type='mime/type'
	if (
		$type == 'document' && ($mime = sql_getfetsel(
			'mime_type',
			'spip_types_documents',
			'extension IN (' . sql_get_select('extension', 'spip_documents', 'id_document=' . sql_quote($id)) . ')',
			'',
			'',
			'',
			'',
			$connect
		))
	) {
		$r['mime'] = $mime;
	}

	return $r;
}

// analyse des raccourcis issus de [TITRE->RACCOURCInnn] et connexes

define('_RACCOURCI_URL', '/^\s*(\w*?)\s*(\d+)(\?(.*?))?(#([^\s]*))?\s*$/S');

function typer_raccourci($lien) {
	if (!preg_match(_RACCOURCI_URL, (string) $lien, $match)) {
		return [];
	}

	$f = $match[1];
	// valeur par defaut et alias historiques
	if (!$f) {
		$f = 'article';
	} else {
		if ($f == 'art') {
			$f = 'article';
		} else {
			if ($f == 'br') {
				$f = 'breve';
			} else {
				if ($f == 'rub') {
					$f = 'rubrique';
				} else {
					if ($f == 'aut') {
						$f = 'auteur';
					} else {
						if ($f == 'doc' || $f == 'im' || $f == 'img' || $f == 'image' || $f == 'emb') {
							$f = 'document';
						} else {
							if (preg_match('/^br..?ve$/S', $f)) {
								$f = 'breve'; # accents :(
							}
						}
					}
				}
			}
		}
	}

	$match[0] = $f;

	return $match;
}

/**
 * Retourne le titre et la langue d'un objet éditorial
 *
 * @param int $id Identifiant de l'objet
 * @param string $type Type d'objet
 * @param string|null $connect Connecteur SQL utilisé
 * @return array {
 * @var string $titre Titre si présent, sinon ''
 * @var string $lang Langue si présente, sinon ''
 * }
 **/
function traiter_raccourci_titre($id, $type, $connect = null) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table(table_objet($type));

	if (!($desc && ($s = $desc['titre']))) {
		return [];
	}

	$_id = $desc['key']['PRIMARY KEY'];
	$r = sql_fetsel($s, $desc['table'], "$_id=$id", '', '', '', '', $connect);

	if (!$r) {
		return [];
	}

	$r['titre'] = supprimer_numero($r['titre']);

	if (!$r['titre'] && !empty($r['surnom'])) {
		$r['titre'] = $r['surnom'];
	}

	if (!isset($r['lang'])) {
		$r['lang'] = '';
	}

	return $r;
}

//
// Raccourcis ancre [#ancre<-]
//
function traiter_raccourci_ancre($letexte) {
	return $letexte;
}

function traiter_raccourci_glossaire($texte) {
	return $texte;
}

function glossaire_std($terme) {
	return $terme;
}
