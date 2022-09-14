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

/**
 * Detecter et collecter les modeles d'un texte dans un tableau descriptif
 * qui pourra servir a leurs traitements ou echappement selon le besoin
 * @param string $texte
 * @param bool $collecter_liens
 * @return array
 */
function modeles_collecter($texte, bool $collecter_liens = true) {

	$modeles = [];
	// detecter les modeles (rapide)
	$pos = 0;
	while (
		($next = strpos($texte, '<', $pos)) !== false
		and preg_match('@' . _PREG_MODELE . '@isS', $texte, $regs, PREG_OFFSET_CAPTURE, $next)
	) {

		$a = $regs[0][1];
		$mod = $regs[1][0];
		$type = $regs[2][0];
		$id = $regs[3][0] ?? '';
		$params = $regs[4][0] ?? '';
		$longueur = strlen($mod);
		$end = $a + $longueur;

		// il faut avoir un id ou des params commençant par un | sinon c'est une simple balise html
		if (!empty($id) or !empty($params)) {

			$lien = false;
			if ($collecter_liens
				and $fermeture_lien = stripos($texte, '</a>', $end)
				and !strlen(trim(substr($texte, $end, $fermeture_lien - $end)))) {

				$pos_lien_ouvrant = stripos($texte, '<a', $pos);
				if ($pos_lien_ouvrant !== false
					and $pos_lien_ouvrant < $a
					and preg_match('/<a\s[^<>]*>\s*$/i', substr($texte, $pos, $a - $pos), $r)
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
					$longueur = $fermeture_lien - $a + 4;
					$end = $a + $longueur;
				}
			}

			$modele = [
				'modele' => $mod,
				'pos' => $a,
				'length' => $longueur,
				'type' => $type,
				'id' => $id,
				'params' => $params,
				'lien' => $lien,
			];

			$modeles[] = $modele;
		}
		$pos = $end;
	}

	return $modeles;
}

/**
 * Echapper les raccourcis modeles pour ne pas les casser via safehtml par exemple
 *
 * @see modele_retablir_raccourcis_echappes()
 * @param string $texte
 * @param bool $collecter_liens
 * @return array
 *   texte, marqueur utilise pour echapper les modeles
 */
function modeles_echapper_raccourcis($texte, bool $collecter_liens = false) {

	$modeles = modeles_collecter($texte, $collecter_liens);
	$markid = '';
	if (!empty($modeles)) {
		// generer un marqueur qui n'est pas dans le texte
		do {
			$markid = substr(md5(creer_uniqid()), 0, 7);
			$markid = "@|MODELE$markid|";
		} while (strpos($texte, $markid) !== false);

		$offset_pos = 0;
		foreach ($modeles as $m) {
			$rempl = $markid . base64_encode($m['modele']) . '|@';
			$texte = substr_replace($texte, $rempl, $m['pos'] + $offset_pos, $m['length']);
			$offset_pos += strlen($rempl) - $m['length'];
		}
	}

	return [$texte, $markid];
}

/**
 * Retablir les modeles echappes par la fonction modeles_echapper_raccourcis()
 *
 * @see modeles_echapper_raccourcis()
 * @param string $texte
 * @param string $markid
 * @return string
 */
function modele_retablir_raccourcis_echappes(string $texte, string $markid) {

	if ($markid) {
		$pos = 0;
		while (
			($p = strpos($texte, $markid, $pos)) !== false
			and $end = strpos($texte, '|@', $p + 16)
		) {
			$base64 = substr($texte, $p + 16, $end - ($p + 16));
			if ($modele = base64_decode($base64, true)) {
				$texte = substr_replace($texte, $modele, $p, $end + 2 - $p);
				$pos = $p + strlen($modele);
			}
			else {
				$pos = $end;
			}
		}
	}

	return $texte;
}


/**
 * Traiter les modeles d'un texte
 * @param string $texte
 * @param bool|array $doublons
 * @param string $echap
 * @param string $connect
 * @param ?string $markidliens
 * @param array $env
 * @return string
 */
function traiter_modeles($texte, $doublons = false, $echap = '', string $connect = '', ?string $markidliens = null, $env = []) {
	// preserver la compatibilite : true = recherche des documents
	if ($doublons === true) {
		$doublons = ['documents' => ['doc', 'emb', 'img']];
	}

	$modeles = modeles_collecter($texte, true);
	if (!empty($modeles)) {
		include_spip('public/assembler');
		$wrap_embed_html = charger_fonction('wrap_embed_html', 'inc', true);

		$offset_pos = 0;
		foreach ($modeles as $m) {
			// calculer le modele
			# hack indexation
			if ($doublons) {
				$texte .= preg_replace(',[|][^|=]*,s', ' ', $m['params']);
			} # version normale
			else {
				// si un tableau de liens a ete passe, reinjecter le contenu d'origine
				// dans les parametres, plutot que les liens echappes
				$params = $m['params'];
				if (!is_null($markidliens)) {
					$params = liens_retablir_raccourcis_echappes($params, $markidliens);
				}

				$modele = inclure_modele($m['type'], $m['id'], $params, $m['lien'], $connect ?? '', $env);

				// en cas d'echec,
				// si l'objet demande a une url,
				// creer un petit encadre vers elle
				if ($modele === false) {
					$modele = $m['modele'];

					if (!is_null($markidliens)) {
						$modele = liens_retablir_raccourcis_echappes($modele, $markidliens);
					}

					$contexte = array_merge($env, ['id' => $m['id'], 'type' => $m['type'], 'modele' => $modele]);

					if (!empty($m['lien'])) {
						# un eventuel guillemet (") sera reechappe par #ENV
						$contexte['lien'] = str_replace('&quot;', '"', $m['lien']['href']);
						$contexte['lien_class'] = $m['lien']['class'];
						$contexte['lien_mime'] = $m['lien']['mime'];
						$contexte['lien_title'] = $m['lien']['title'];
						$contexte['lien_hreflang'] = $m['lien']['hreflang'];
					}

					$modele = recuperer_fond('modeles/dist', $contexte, [], $connect ?? '');
				}

				// le remplacer dans le texte
				if ($modele !== false) {
					$modele = protege_js_modeles($modele);

					if ($wrap_embed_html) {
						$modele = $wrap_embed_html($m['modele'], $modele);
					}

					$rempl = code_echappement($modele, $echap);
					$texte = substr_replace($texte, $rempl, $m['pos'] + $offset_pos, $m['length']);
					$offset_pos += strlen($rempl) - $m['length'];
				}
			}

			// hack pour tout l'espace prive
			if ((test_espace_prive() or ($doublons)) and !empty($m['id'])) {
				$type = strtolower($m['type']);
				foreach ($doublons ?: ['documents' => ['doc', 'emb', 'img']] as $quoi => $type_modeles) {
					if (in_array($type, $modeles)) {
						$GLOBALS["doublons_{$quoi}_inclus"][] = $m['id'];
					}
				}
			}
		}
	}

	return $texte;
}
