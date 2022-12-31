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
 * Extrait une langue des extraits polyglottes (`<multi>`)
 *
 * Retrouve les balises `<multi>` d'un texte et remplace son contenu
 * par l'extrait correspondant à la langue demandée.
 *
 * Si la langue demandée n'est pas trouvée dans le multi, ni une langue
 * approchante (exemple `fr` si on demande `fr_TU`), on retourne l'extrait
 * correspondant à la langue par défaut (option 'lang_defaut'), qui est
 * par défaut la langue du site. Et si l'extrait n'existe toujours pas
 * dans cette langue, ça utilisera la première langue utilisée
 * dans la balise `<multi>`.
 *
 * Ne pas mettre de span@lang=fr si on est déjà en fr.
 */
class Multis extends AbstractCollecteur {

	protected static string $markPrefix = 'MULTI';

	/**
	 * La preg pour découper et collecter les modèles
	 * @var string
	 */
	protected string $preg_multi;

	public function __construct(?string $preg = null) {

		$this->preg_multi = ($preg ?: '@<multi>(.*?)</multi>@sS');
	}

	/**
	 * Sanitizer une collection d'occurences de multi : on sanitize chaque texte de langue séparemment
	 *
	 * @param array $collection
	 * @param string $sanitize_callback
	 * @return array
	 */
	protected function sanitizer_collection(array $collection, string $sanitize_callback): array {

		foreach ($collection as &$multi) {
			$changed = false;
			foreach ($multi['trads'] as $lang => $trad) {
				$t = $sanitize_callback($trad);
				if ($t !== $trad) {
					$changed = true;
					$multi['trads'][$lang] = $t;
				}
			}
			if ($changed) {
				$texte = $this->agglomerer_trads($multi['trads']);
				$multi['raw'] = str_replace($multi['texte'], $texte, $multi['raw']);
				$multi['texte'] = $texte;
			}
		}
		return $collection;
	}


	/**
	 * Convertit le contenu d'une balise `<multi>` en un tableau
	 *
	 * Exemple de blocs.
	 * - `texte par défaut [fr] en français [en] en anglais`
	 * - `[fr] en français [en] en anglais`
	 *
	 * @param string $bloc
	 *     Le contenu intérieur d'un bloc multi
	 * @return array [code de langue => texte]
	 *     Peut retourner un code de langue vide, lorsqu'un texte par défaut est indiqué.
	 **/
	protected function extraire_trads($bloc) {
		$trads = [];

		if (strlen($bloc)) {
			$langs = $this->collecteur($bloc, ']', '[', '@[\[]([a-z]{2,3}(_[a-z]{2,3})?(_[a-z]{2,3})?)[\]]@siS');
			$lang = '';
			$pos_prev = 0;
			foreach ($langs as $l) {
				$pos = $l['pos'];
				if ($lang or $pos > $pos_prev) {
					$trads[$lang] = substr($bloc, $pos_prev, $pos - $pos_prev);
				}
				$lang = $l['match'][1];
				$pos_prev = $pos + $l['length'];
			}
			$trads[$lang] = substr($bloc, $pos_prev);
		}

		return $trads;
	}

	/**
	 * Recoller ensemble les trads pour reconstituer le texte dans la balise <multi>...</multi>
	 * @param $trads
	 * @return string
	 */
	protected function agglomerer_trads($trads) {
		$texte = '';
		foreach ($trads as $lang => $trad) {
			if ($texte or $lang) {
				$texte .= "[$lang]";
			}
			$texte .= $trad;
		}
		return $texte;
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
		$multis = $this->collecteur($texte, '', '<multi', $this->preg_multi, empty($options['detecter_presence']) ? 0 : 1);

		// si on veut seulement detecter la présence, on peut retourner tel quel
		if (empty($options['detecter_presence'])) {
			foreach ($multis as $k => &$multi) {
				$multi['texte'] = $multi['match'][1];
				// extraire les trads du texte
				$multi['trads'] = $this->extraire_trads($multi['texte']);
			}
		}

		return $multis;
	}

	/**
	 * Traiter les multis d'un texte
	 *
	 * @uses approcher_langue()
	 * @uses lang_typo()
	 * @uses code_echappement()
	 * @uses echappe_retour()
	 *
	 * @param string $texte
	 * @param array $options
	 *   ?string $lang
	 *   ?string $lang_defaut
	 *   ?bool echappe_span
	 *   ?bool appliquer_typo
	 * @return string
	 */
	public function traiter(string $texte, array $options) {
		if ($texte) {

			$multis = $this->collecter($texte);
			if (!empty($multis)) {
				$lang = $options['lang'] ?? $GLOBALS['spip_lang'];
				$lang_defaut = $options['lang_defaut'] ?? _LANGUE_PAR_DEFAUT;
				$echappe_span = $options['echappe_span'] ?? false;
				$appliquer_typo = $options['appliquer_typo'] ?? true;

				if (!function_exists('approcher_langue')) {
					include_spip('inc/lang');
				}
				if (!function_exists('code_echappement')) {
					include_spip('inc/texte_mini');
				}

				$offset_pos = 0;
				foreach ($multis as $m) {

					// chercher la version de la langue courante
					$trads = $m['trads'];
					if (empty($trads)) {
						$trad = '';
					}
					elseif ($l = approcher_langue($trads, $lang)) {
						$trad = $trads[$l];
					} else {
						if ($lang_defaut == 'aucune') {
							$trad = '';
						} else {
							// langue absente, prendre le fr ou une langue précisée (meme comportement que inc/traduire.php)
							// ou la premiere dispo
							if (!$l = approcher_langue($trads, $options['lang_defaut'])) {
								$l = array_keys($trads);
								$l = reset($l);
							}

							// mais typographier le texte selon les regles de celle-ci
							// Attention aux blocs multi sur plusieurs lignes
							if ($appliquer_typo) {
								$trad = $trads[$l];
								$typographie = charger_fonction(lang_typo($l), 'typographie');
								$trad = $typographie($trad);

								// Tester si on echappe en span ou en div
								// il ne faut pas echapper en div si propre produit un seul paragraphe
								include_spip('inc/texte');
								$trad_propre = preg_replace(',(^<p[^>]*>|</p>$),Uims', '', propre($trad));
								$mode = preg_match(',</?(' . _BALISES_BLOCS . ')[>[:space:]],iS', $trad_propre) ? 'div' : 'span';
								if ($mode === 'div') {
									$trad = rtrim($trad) . "\n\n";
								}
								$trad = code_echappement($trad, 'multi', false, $mode);
								$trad = str_replace("'", '"', inserer_attribut($trad, 'lang', $l));
								if (lang_dir($l) !== lang_dir($lang)) {
									$trad = str_replace("'", '"', inserer_attribut($trad, 'dir', lang_dir($l)));
								}
								if (!$echappe_span) {
									$trad = echappe_retour($trad, 'multi');
								}
							}
						}
					}

					$texte = substr_replace($texte, $trad, $m['pos'] + $offset_pos, $m['length']);
					$offset_pos += strlen($trad) - $m['length'];
				}
			}
		}

		return $texte;
	}

}
