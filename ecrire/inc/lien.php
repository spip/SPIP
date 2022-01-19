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

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');

//
// Production de la balise A+href a partir des raccourcis [xxx->url] etc.
// Note : complique car c'est ici qu'on applique typo(),
// et en plus on veut pouvoir les passer en pipeline
//

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

// Regexp des raccourcis, aussi utilisee pour la fusion de sauvegarde Spip
// Laisser passer des paires de crochets pour la balise multi
// mais refuser plus d'imbrications ou de mauvaises imbrications
// sinon les crochets ne peuvent plus servir qu'a ce type de raccourci
define('_RACCOURCI_LIEN', '/\[([^][]*?([[]\w*[]][^][]*)*)->(>?)([^]]*)\]/msS');

// https://code.spip.net/@expanser_liens
function expanser_liens($t, string $connect = '', $env = []) {

	$t = pipeline('pre_liens', $t);

	// on passe a traiter_modeles la liste des liens reperes pour lui permettre
	// de remettre le texte d'origine dans les parametres du modele
	$t = traiter_modeles($t, false, false, $connect);

	return $t;
}

// Meme analyse mais pour eliminer les liens
// et ne laisser que leur titre, a expliciter si ce n'est fait
// https://code.spip.net/@nettoyer_raccourcis_typo
function nettoyer_raccourcis_typo($texte, string $connect = '') {
	return $texte;
}

// Repere dans la partie texte d'un raccourci [texte->...]
// la langue et la bulle eventuelles
// https://code.spip.net/@traiter_raccourci_lien_atts
function traiter_raccourci_lien_atts($texte) {
	$bulle = '';
	$hlang = '';

	return [trim($texte), $bulle, $hlang];
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

// https://code.spip.net/@calculer_url
function calculer_url($ref, $texte = '', $pour = 'url', string $connect = '', $echappe_typo = true) {
	$r = traiter_lien_implicite($ref, $texte, $pour, $connect);

	return $r ?: traiter_lien_explicite($ref, $texte, $pour, $connect, $echappe_typo);
}

define('_EXTRAIRE_LIEN', ',^\s*(?:' . _PROTOCOLES_STD . '):?/?/?\s*$,iS');

// https://code.spip.net/@traiter_lien_explicite
function traiter_lien_explicite($ref, $texte = '', $pour = 'url', string $connect = '', $echappe_typo = true) {
	if (preg_match(_EXTRAIRE_LIEN, $ref)) {
		return ($pour != 'tout') ? '' : ['', '', '', ''];
	}

	$lien = entites_html(trim($ref));

	// Liens explicites
	if (!$texte) {
		$texte = str_replace('"', '', $lien);
		// evite l'affichage de trops longues urls.
		$lien_court = charger_fonction('lien_court', 'inc');
		$texte = $lien_court($texte);
		if ($echappe_typo) {
			$texte = '<html>' . quote_amp($texte) . '</html>';
		}
	}

	// petites corrections d'URL
	if (preg_match('/^www\.[^@]+$/S', $lien)) {
		$lien = 'http://' . $lien;
	} else {
		if (strpos($lien, '@') && email_valide($lien)) {
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
	if (function_exists($f = 'glossaire_' . $ancre)) {
		$url = $f($texte, $id);
	} else {
		$url = glossaire_std($texte);
	}

	return $url;
}

// https://code.spip.net/@traiter_lien_implicite
function traiter_lien_implicite($ref, $texte = '', $pour = 'url', string $connect = '') {
	$url = null;
	if (!($match = typer_raccourci($ref))) {
		return false;
	}
	[$type, , $id, , $args, , $ancre] = array_pad($match, 7, null);
	// attention dans le cas des sites le lien doit pointer non pas sur
	// la page locale du site, mais directement sur le site lui-meme
	if ($f = charger_fonction("implicite_$type", 'liens', true)) {
		$url = $f($texte, $id, $type, $args, $ancre, $connect);
	}
	if (!$url) {
		$url = generer_objet_url($id, $type, $args, $ancre, $connect ?: null);
	}
	if (!$url) {
		return false;
	}
	if (is_array($url)) {
		[$type, $id] = array_pad($url, 2, null);
		$url = generer_objet_url($id, $type, $args, $ancre, $connect ?: null);
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
		$type == 'document'
		and $mime = sql_getfetsel(
			'mime_type',
			'spip_types_documents',
			'extension IN (' . sql_get_select('extension', 'spip_documents', 'id_document=' . sql_quote($id)) . ')',
			'',
			'',
			'',
			'',
			$connect
		)
	) {
		$r['mime'] = $mime;
	}

	return $r;
}

// analyse des raccourcis issus de [TITRE->RACCOURCInnn] et connexes

define('_RACCOURCI_URL', '/^\s*(\w*?)\s*(\d+)(\?(.*?))?(#([^\s]*))?\s*$/S');

// https://code.spip.net/@typer_raccourci
function typer_raccourci($lien) {
	if (!preg_match(_RACCOURCI_URL, $lien, $match)) {
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
						if ($f == 'doc' or $f == 'im' or $f == 'img' or $f == 'image' or $f == 'emb') {
							$f = 'document';
						} else {
							if (preg_match('/^br..?ve$/S', $f)) {
								$f = 'breve';
							}
						}
					}
				}
			}
		}
	} # accents :(
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
	if (!($desc and $s = $desc['titre'])) {
		return [];
	}
	$_id = $desc['key']['PRIMARY KEY'];
	$r = sql_fetsel($s, $desc['table'], "$_id=$id", '', '', '', '', $connect);
	if (!$r) {
		return [];
	}
	$r['titre'] = supprimer_numero($r['titre']);
	if (!$r['titre'] and !empty($r['surnom'])) {
		$r['titre'] = $r['surnom'];
	}
	if (!isset($r['lang'])) {
		$r['lang'] = '';
	}

	return $r;
}

// traite les modeles (dans la fonction typo), en remplacant
// le raccourci <modeleN|parametres> par la page calculee a
// partir du squelette modeles/modele.html
// Le nom du modele doit faire au moins trois caracteres (evite <h2>)
// Si $doublons==true, on repere les documents sans calculer les modeles
// mais on renvoie les params (pour l'indexation par le moteur de recherche)
// https://code.spip.net/@traiter_modeles

define(
	'_RACCOURCI_MODELE',
	'(<([a-z_-]{3,})' # <modele
	. '\s*([0-9]*)\s*' # id
	. '([|](?:<[^<>]*>|[^>])*?)?' # |arguments (y compris des tags <...>)
	. '\s*/?' . '>)' # fin du modele >
	. '\s*(<\/a>)?' # eventuel </a>
);

define('_RACCOURCI_MODELE_DEBUT', '@^' . _RACCOURCI_MODELE . '@isS');

// https://code.spip.net/@traiter_modeles
function traiter_modeles($texte, $doublons = false, $echap = '', string $connect = '', $liens = null, $env = []) {
	// preserver la compatibilite : true = recherche des documents
	if ($doublons === true) {
		$doublons = ['documents' => ['doc', 'emb', 'img']];
	}
	// detecter les modeles (rapide)
	if (
		strpos($texte, '<') !== false and
		preg_match_all('/<[a-z_-]{3,}\s*[0-9|]+/iS', $texte, $matches, PREG_SET_ORDER)
	) {
		include_spip('public/assembler');
		$wrap_embed_html = charger_fonction('wrap_embed_html', 'inc', true);
		foreach ($matches as $match) {
			// Recuperer l'appel complet (y compris un eventuel lien)

			$a = strpos($texte, (string) $match[0]);
			preg_match(
				_RACCOURCI_MODELE_DEBUT,
				substr($texte, $a),
				$regs
			);
			$regs[] = ''; // s'assurer qu'il y a toujours un 5e arg, eventuellement vide
			[, $mod, $type, $id, $params, $fin] = $regs;
			if (
				$fin and
				preg_match(
					'/<a\s[^<>]*>\s*$/i',
					substr($texte, 0, $a),
					$r
				)
			) {
				$lien = [
					'href' => extraire_attribut($r[0], 'href'),
					'class' => extraire_attribut($r[0], 'class'),
					'mime' => extraire_attribut($r[0], 'type'),
					'title' => extraire_attribut($r[0], 'title'),
					'hreflang' => extraire_attribut($r[0], 'hreflang')
				];
				$n = strlen($r[0]);
				$a -= $n;
				$cherche = $n + strlen($regs[0]);
			} else {
				$lien = false;
				$cherche = strlen($mod);
			}

			// calculer le modele
			# hack indexation
			if ($doublons) {
				$texte .= preg_replace(',[|][^|=]*,s', ' ', $params);
			} # version normale
			else {
				// si un tableau de liens a ete passe, reinjecter le contenu d'origine
				// dans les parametres, plutot que les liens echappes
				if (!is_null($liens)) {
					$params = str_replace($liens[0], $liens[1], $params);
				}
				$modele = inclure_modele($type, $id, $params, $lien, $connect, $env);
				// en cas d'echec,
				// si l'objet demande a une url,
				// creer un petit encadre vers elle
				if ($modele === false) {
					if (!$lien) {
						$lien = traiter_lien_implicite("$type$id", '', 'tout', $connect);
					}
					if ($lien) {
						$modele = '<a href="'
							. $lien['url']
							. '" class="spip_modele'
							. '">'
							. sinon($lien['titre'], _T('ecrire:info_sans_titre'))
							. '</a>';
					} else {
						$modele = '';
						if (test_espace_prive()) {
							$modele = entites_html(substr($texte, $a, $cherche));
							if (!is_null($liens)) {
								$modele = '<pre>' . str_replace($liens[0], $liens[1], $modele) . '</pre>';
							}
						}
					}
				}
				// le remplacer dans le texte
				if ($modele !== false) {
					$modele = protege_js_modeles($modele);
					if ($wrap_embed_html) {
						$modele = $wrap_embed_html($mod, $modele);
					}
					$rempl = code_echappement($modele, $echap);
					$texte = substr($texte, 0, $a)
						. $rempl
						. substr($texte, $a + $cherche);
				}
			}

			// hack pour tout l'espace prive
			if (((!_DIR_RESTREINT) or ($doublons)) and ($id)) {
				foreach ($doublons ?: ['documents' => ['doc', 'emb', 'img']] as $quoi => $modeles) {
					if (in_array($type, $modeles)) {
						$GLOBALS["doublons_{$quoi}_inclus"][] = $id;
					}
				}
			}
		}
	}

	return $texte;
}

//
// Raccourcis ancre [#ancre<-]
//
// https://code.spip.net/@traiter_raccourci_ancre
function traiter_raccourci_ancre($letexte) {
	return $letexte;
}

// https://code.spip.net/@traiter_raccourci_glossaire
function traiter_raccourci_glossaire($texte) {
	return $texte;
}

// https://code.spip.net/@glossaire_std
function glossaire_std($terme) {
	return $terme;
}
