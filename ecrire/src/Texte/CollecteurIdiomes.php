<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
 * \***************************************************************************/

namespace Spip\Texte;

use Spip\Texte\Utils\Collecteur;

/**
 * Extrait une langue des extraits idiomes (`<:module:cle_de_langue:>`)
 *
 * Retrouve les balises `<:cle_de_langue:>` d'un texte et remplace son contenu
 * par l'extrait correspondant à la langue demandée (si possible), sinon dans la
 * langue par défaut du site.
 *
 * Ne pas mettre de span@lang=fr si on est déjà en fr.
 */
class CollecteurIdiomes extends Collecteur {

	protected static string $markPrefix = 'IDIOME';

	/**
	 * La preg pour découper et collecter les modèles
	 * @var string
	 */
	protected string $preg_idiome;

	public function __construct(?string $preg = null) {

		$this->preg_idiome = ($preg ?: '@<:(?:([a-z0-9_]+):)?([a-z0-9_]+):>@isS');
	}

	/**
	 * Sanitizer une collection d'occurences d'idiomes : on ne fait rien
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
		$idiomes = $this->collecteur($texte, '', '<:', $this->preg_idiome, empty($options['detecter_presence']) ? 0 : 1);

		// si on veut seulement detecter la présence, on peut retourner tel quel
		if (empty($options['detecter_presence'])) {

			$pos_prev = 0;
			foreach ($idiomes as $k => &$idiome) {

				$idiome['module'] = $idiome['match'][1];
				$idiome['chaine'] = $idiome['match'][2];
			}
		}

		return $idiomes;
	}

	/**
	 * Traiter les idiomes d'un texte
	 *
	 * @uses inc_traduire_dist()
	 * @uses code_echappement()
	 * @uses echappe_retour()
	 *
	 * @param string $texte
	 * @param array $options
	 *   ?string $lang
	 *   ?bool echappe_span
	 * @return string
	 */
	public function traiter(string $texte, array $options) {
		static $traduire;
		if ($texte) {

			$idiomes = $this->collecter($texte);
			if (!empty($idiomes)) {
				$lang = $options['lang'] ?? $GLOBALS['spip_lang'];
				$echappe_span = $options['echappe_span'] ?? false;

				if (is_null($traduire)) {
					$traduire = charger_fonction('traduire', 'inc');
					include_spip('inc/lang');
				}

				$offset_pos = 0;
				foreach ($idiomes as $idiome) {

					$cle = ($idiome['module'] ? $idiome['module'] . ':' : '') . $idiome['chaine'];
					$desc = $traduire($cle, $lang, true);
					$l = $desc->langue;

					// si pas de traduction, on laissera l'écriture de l'idiome entier dans le texte.
					if (strlen($desc->texte ?? '')) {
						$trad = code_echappement($desc->texte, 'idiome', false);
						if ($l !== $lang) {
							$trad = str_replace("'", '"', inserer_attribut($trad, 'lang', $l));
						}
						if (lang_dir($l) !== lang_dir($lang)) {
							$trad = str_replace("'", '"', inserer_attribut($trad, 'dir', lang_dir($l)));
						}
						if (!$echappe_span) {
							$trad = echappe_retour($trad, 'idiome');
						}
						$texte = substr_replace($texte, $trad, $idiome['pos'] + $offset_pos, $idiome['length']);
						$offset_pos += strlen($trad) - $idiome['length'];
					}

				}
			}
		}

		return $texte;
	}

}
