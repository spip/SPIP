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

// traite les modeles (dans la fonction typo), en remplacant
// le raccourci <modeleN|parametres> par la page calculee a
// partir du squelette modeles/modele.html
// Le nom du modele doit faire au moins trois caracteres (evite <h2>)
// Si $doublons==true, on repere les documents sans calculer les modeles
// mais on renvoie les params (pour l'indexation par le moteur de recherche)

define(
	'_PREG_MODELE',
	'(<([a-z_-]{3,})' # <modele
	. '\s*([0-9]*)\s*' # id
	. '([|](?:<[^<>]*>|[^>])*?)?' # |arguments (y compris des tags <...>)
	. '\s*/?' . '>)' # fin du modele >
);

define(
	'_RACCOURCI_MODELE',
	_PREG_MODELE
	. '\s*(<\/a>)?' # eventuel </a>
);

define('_RACCOURCI_MODELE_DEBUT', '@^' . _RACCOURCI_MODELE . '@isS');

function traiter_modeles($texte, $doublons = false, $echap = '', string $connect = '', $liens = null, $env = []) {
	// preserver la compatibilite : true = recherche des documents
	if ($doublons === true) {
		$doublons = ['documents' => ['doc', 'emb', 'img']];
	}

	// detecter les modeles (rapide)
	if (
		strpos($texte, '<') !== false
		and preg_match_all('/<[a-z_-]{3,}\s*[0-9|]+/iS', $texte, $matches, PREG_SET_ORDER)
	) {
		include_spip('public/assembler');
		$wrap_embed_html = charger_fonction('wrap_embed_html', 'inc', true);

		// Recuperer l'appel complet (y compris un eventuel lien)
		foreach ($matches as $match) {
			$a = strpos($texte, (string) $match[0]);
			preg_match(_RACCOURCI_MODELE_DEBUT, substr($texte, $a), $regs);

			// s'assurer qu'il y a toujours un 5e arg, eventuellement vide
			while (count($regs) < 6) {
				$regs[] = '';
			}

			[, $mod, $type, $id, $params, $fin] = $regs;

			if (
				$fin
				and preg_match('/<a\s[^<>]*>\s*$/i', substr($texte, 0, $a), $r)
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

				$modele = inclure_modele($type, $id, $params, $lien, $connect ?? '', $env);

				// en cas d'echec,
				// si l'objet demande a une url,
				// creer un petit encadre vers elle
				if ($modele === false) {
					$modele = substr($texte, $a, $cherche);

					if (!is_null($liens)) {
						$modele = str_replace($liens[0], $liens[1], $modele);
					}

					$contexte = array_merge($env, ['id' => $id, 'type' => $type, 'modele' => $modele]);

					if ($lien) {
						# un eventuel guillemet (") sera reechappe par #ENV
						$contexte['lien'] = str_replace('&quot;', '"', $lien['href']);
						$contexte['lien_class'] = $lien['class'];
						$contexte['lien_mime'] = $lien['mime'];
						$contexte['lien_title'] = $lien['title'];
						$contexte['lien_hreflang'] = $lien['hreflang'];
						}

					$modele = recuperer_fond('modeles/dist', $contexte, [], $connect ?? '');
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
					if (in_array(strtolower($type), $modeles)) {
						$GLOBALS["doublons_{$quoi}_inclus"][] = $id;
					}
				}
			}
		}
	}

	return $texte;
}
