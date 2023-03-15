<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 * \***************************************************************************/

namespace Spip\Texte\Collecteur;

/**
 *    traite les modeles (dans la fonction typo), en remplacant
 *    le raccourci <modeleN|parametres> par la page calculee a
 *    partir du squelette modeles/modele.html
 *    Le nom du modele doit faire au moins trois caracteres (evite <h2>)
 *    Si $doublons==true, on repere les documents sans calculer les modeles
 *    mais on renvoie les params (pour l'indexation par le moteur de recherche)
 */
class Modeles extends AbstractCollecteur {
	protected static string $markPrefix = 'MODELE';

	/**
	 * La preg pour découper et collecter les modèles
	 * @var string
	 */
	protected string $preg_modele;

	public function __construct(?string $preg = null) {

		$this->preg_modele = ($preg ?:
			'@<([a-z_-]{3,})' # <modele
			. '\s*([0-9]*)\s*' # id
			. '([|](?:<[^<>]*>|[^>])*?)?' # |arguments (y compris des tags <...>)
			. '\s*/?' . '>@isS' # fin du modele >
		);
	}

	/**
	 * Sanitizer une collection d'occurences de modèle : on ne fait rien
	 *
	 * @param array $collection
	 * @param string $sanitize_callback
	 * @return array
	 */
	protected function sanitizer_collection(array $collection, string $sanitize_callback): array {

		return $collection;
	}

	/**
	 * @param string $texte
	 * @param array $options
	 *   bool $collecter_liens
	 * @return array
	 */
	public function collecter(string $texte, array $options = []): array {
		if (!$texte) {
			return [];
		}

		// collecter les matchs de la preg
		$modeles = static::collecteur($texte, '', '<', $this->preg_modele);

		$pos_prev = 0;
		foreach ($modeles as $k => &$modele) {
			$pos = $modele['pos'];
			$modele['type'] = $modele['match'][1];
			$modele['id'] = $modele['match'][2] ?? '';
			$modele['params'] = $modele['match'][3] ?? '';

			$longueur = $modele['length'];
			$end = $pos + $longueur;

			// il faut avoir un id ou des params commençant par un | sinon c'est une simple balise html
			if (empty($modele['id']) && empty($modele['params'])) {
				unset($modeles[$k]);
				continue;
			}

			// si on veut seulement detecter la présence, on peut retourner tel quel
			if (!empty($options['detecter_presence'])) {
				break;
			}

			$modele['lien'] = false;
			if (
				!empty($options['collecter_liens'])
				&& ($pos_fermeture_lien = stripos($texte, '</a>', $end))
				&& !strlen(trim(substr($texte, $end, $pos_fermeture_lien - $end)))
			) {
				$pos_lien_ouvrant = stripos($texte, '<a', $pos_prev);
				if (
					$pos_lien_ouvrant !== false
					&& $pos_lien_ouvrant < $pos
					&& preg_match('/<a\s[^<>]*>\s*$/i', substr($texte, $pos_prev, $pos - $pos_prev), $r)
				) {
					$modele['lien'] = [
						'href' => extraire_attribut($r[0], 'href'),
						'class' => extraire_attribut($r[0], 'class'),
						'mime' => extraire_attribut($r[0], 'type'),
						'title' => extraire_attribut($r[0], 'title'),
						'hreflang' => extraire_attribut($r[0], 'hreflang')
					];
					$n = strlen($r[0]);
					$pos -= $n;
					$longueur = $pos_fermeture_lien - $pos + 4;
					$end = $pos + $longueur;
				}
			}


			$modele['pos'] = $pos;
			$modele['length'] = $longueur;
			$pos_prev = $end;
		}

		return $modeles;
	}

	/**
	 * Traiter les modeles d'un texte
	 * @param string $texte
	 * @param array $options
	 *   bool|array $doublons
	 *   string $echap
	 *   ?Spip\Texte\CollecteurLiens $collecteurLiens
	 *   ?array $env
	 *   ?string $connect
	 * @return string
	 */
	public function traiter(string $texte, array $options) {
		if ($texte) {
			$doublons = $options['doublons'] ?? false;
			$echap = $options['echap'] ?? '';
			$collecteurLiens = $options['collecteurLiens'] ?? null;
			$env = $options['env'] ?? [];
			$connect = $options['connect'] ?? '';

			// preserver la compatibilite : true = recherche des documents
			if ($doublons === true) {
				$doublons = ['documents' => ['doc', 'emb', 'img']];
			}

			$modeles = $this->collecter($texte, ['collecter_liens' => true]);
			if ($modeles !== []) {
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
						if (!is_null($collecteurLiens)) {
							$params = $collecteurLiens->retablir($params);
						}

						$modele = inclure_modele($m['type'], $m['id'], $params, $m['lien'], $connect ?? '', $env);

						// en cas d'echec,
						// si l'objet demande a une url,
						// creer un petit encadre vers elle
						if ($modele === false) {
							$modele = $m['raw'];

							if (!is_null($collecteurLiens)) {
								$modele = $collecteurLiens->retablir($modele);
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
								$modele = $wrap_embed_html($m['raw'], $modele);
							}

							$rempl = code_echappement($modele, $echap);
							$texte = substr_replace($texte, $rempl, $m['pos'] + $offset_pos, $m['length']);
							$offset_pos += strlen($rempl) - $m['length'];
						}
					}

					// hack pour tout l'espace prive
					if ((test_espace_prive() || $doublons) && !empty($m['id'])) {
						$type = strtolower($m['type']);
						foreach ($doublons ?: ['documents' => ['doc', 'emb', 'img']] as $quoi => $type_modeles) {
							if (in_array($type, $type_modeles)) {
								$GLOBALS["doublons_{$quoi}_inclus"][] = $m['id'];
							}
						}
					}
				}
			}
		}

		return $texte;
	}
}
