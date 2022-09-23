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

namespace Spip\Texte\Collecteur;

/**
 *    Collecte les raccourcis liens [titre->url] de SPIP
 */
class Liens extends AbstractCollecteur {

	protected static string $markPrefix = 'LIEN';

	/**
	 * La preg pour découper et collecter les modèles
	 * @var string
	 */
	protected string $preg_lien;

	public function __construct(?string $preg = null) {

		// Regexp des raccourcis, aussi utilisee pour la fusion de sauvegarde Spip
		// Laisser passer des paires de crochets pour la balise multi
		// mais refuser plus d'imbrications ou de mauvaises imbrications
		// sinon les crochets ne peuvent plus servir qu'a ce type de raccourci
		$this->preg_lien = ($preg ?: '/\[([^][]*?([[][^]>-]*[]][^][]*)*)->(>?)([^]]*)\]/msS');
	}


	/**
	 * Sanitizer une collection d'occurences de liens : il faut sanitizer le href et le texte uniquement
	 *
	 * @param array $collection
	 * @param string $sanitize_callback
	 * @return array
	 */
	protected function sanitizer_collection(array $collection, string $sanitize_callback): array {
		foreach ($collection as &$lien) {
			$t = $sanitize_callback($lien['texte']);
			if ($t !== $lien['texte']) {
				$lien['raw'] = str_replace($lien['texte'], $t, $lien['raw']);
				$lien['texte'] = $t;
			}
			$href = $sanitize_callback($lien['href']);
			if ($href !== $lien['href']) {
				$lien['raw'] = str_replace($lien['href'], $href, $lien['raw']);
				$lien['href'] = $href;
			}
		}

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

		$liens = [];
		if (strpos($texte, '->') !== false) {

			$desechappe_crochets = false;
			// si il y a un crochet ouvrant échappé ou un crochet fermant échappé, les substituer pour les ignorer
			if (strpos($texte, '\[') !== false or strpos($texte, '\]') !== false) {
				$texte = str_replace(['\[', '\]'], ["\x1\x5", "\x1\x6"], $texte);
				$desechappe_crochets = true;
			}

			// collecter les matchs de la preg
			$liens = $this->collecteur($texte, '->', '[', $this->preg_lien, empty($options['detecter_presence']) ? 0 : 1);

			// si on veut seulement detecter la présence, on peut retourner tel quel
			if (empty($options['detecter_presence'])) {

				foreach ($liens as $k => &$lien) {

					$lien['href'] = end($lien['match']);
					$lien['texte'] = $lien['match'][1];
					$lien['ouvrant'] = $lien['match'][3] ?? '';

					// la mise en lien automatique est passee par la a tort !
					// corrigeons pour eviter d'avoir un <a...> dans un href...
					if (strncmp($lien['href'], '<a', 2) == 0) {
						$href = extraire_attribut($lien['href'], 'href');
						// remplacons dans la source qui peut etre reinjectee dans les arguments
						// d'un modele
						$lien['raw'] = str_replace($lien['href'], $href, $lien['raw']);
						// et prenons le href comme la vraie url a linker
						$lien['href'] = $href;
					}

					if ($desechappe_crochets and strpos($lien['raw'], "\x1") !== false) {
						$lien['raw'] = str_replace(["\x1\x5", "\x1\x6"], ['[', ']'], $lien['raw']);
						$lien['texte'] = str_replace(["\x1\x5", "\x1\x6"], ['[', ']'], $lien['texte']);
						$lien['href'] = str_replace(["\x1\x5", "\x1\x6"], ['[', ']'], $lien['href']);
					}

				}
			}
		}

		return $liens;
	}

}
