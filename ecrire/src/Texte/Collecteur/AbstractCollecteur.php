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

abstract class AbstractCollecteur {
	protected static string $markPrefix = 'COLLECT';
	protected string $markId;

	/**
	 * Collecteur générique des occurences d'une preg dans un texte avec leurs positions et longueur
	 * @param string $texte
	 *   texte à analyser pour la collecte
	 * @param string $if_chars
	 *   caractere(s) à tester avant de tenter la preg
	 * @param string $start_with
	 *   caractere(s) par lesquels commencent l'expression recherchée (permet de démarrer la preg à la prochaine occurence de cette chaine)
	 * @param string $preg
	 *   preg utilisée pour la collecte
	 * @param int $max_items
	 *   pour limiter le nombre de preg collectée (pour la detection simple de présence par exemple)
	 * @return array
	 */
	protected static function collecteur(string $texte, string $if_chars, string $start_with, string $preg, int $max_items = 0): array {

		$collection = [];
		$pos = 0;
		while (
			(!$if_chars || str_contains($texte, $if_chars))
			&& ($next = ($start_with ? strpos($texte, $start_with, $pos) : $pos)) !== false
			&& preg_match($preg, $texte, $r, PREG_OFFSET_CAPTURE, $next)
		) {
			$found_pos = $r[0][1];
			$found_length = strlen($r[0][0]);
			$match = [
				'raw' => $r[0][0],
				'match' => array_column($r, 0),
				'pos' => $found_pos,
				'length' => $found_length
			];

			$collection[] = $match;

			if ($max_items && count($collection) === $max_items) {
				break;
			}

			$pos = $match['pos'] + $match['length'];
		}

		return $collection;
	}

	/**
	 * Sanitizer une collection d'occurences
	 */
	protected function sanitizer_collection(array $collection, string $sanitize_callback): array {
		foreach ($collection as &$c) {
			$c['raw'] = $sanitize_callback($c['raw']);
		}

		return $collection;
	}

	/**
	 * @return array
	 */
	public function collecter(string $texte, array $options = []): array {
		return [];
	}

	public function detecter($texte): bool {
		if (!empty($this->markId) && str_contains($texte, $this->markId)) {
			return true;
		}
		return !empty($this->collecter($texte, ['detecter_presence' => true]));
	}

	/**
	 * Echapper les occurences de la collecte par un texte neutre du point de vue HTML
	 *
	 * @see retablir()
	 * @param string $texte
	 * @param array $options
	 *   string $sanitize_callback
	 * @return array
	 *   texte, marqueur utilise pour echapper les modeles
	 */
	public function echapper(string $texte, array $options = []): string {
		if (!function_exists('creer_uniqid')) {
			include_spip('inc/acces');
		}

		$collection = $this->collecter($texte, $options);
		if (!empty($options['sanitize_callback']) && is_callable($options['sanitize_callback'])) {
			$collection = $this->sanitizer_collection($collection, $options['sanitize_callback']);
		}

		if ($collection !== []) {
			if (empty($this->markId)) {
				// generer un marqueur qui n'existe pas dans le texte
				do {
					$this->markId = substr(md5(uniqid(static::class, 1)), 0, 7);
					$this->markId = '@|' . static::$markPrefix . $this->markId . '|';
				} while (str_contains($texte, $this->markId));
			}

			$offset_pos = 0;
			foreach ($collection as $c) {
				$rempl = $this->markId . base64_encode($c['raw']) . '|@';
				$texte = substr_replace($texte, $rempl, $c['pos'] + $offset_pos, $c['length']);
				$offset_pos += strlen($rempl) - $c['length'];
			}
		}

		return $texte;
	}


	/**
	 * Retablir les occurences échappées précédemment
	 *
	 * @see echapper()
	 */
	function retablir(string $texte): string {

		if (!empty($this->markId)) {
			$lm = strlen($this->markId);
			$pos = 0;
			while (
				($p = strpos($texte, $this->markId, $pos)) !== false
				&& ($end = strpos($texte, '|@', $p + $lm))
			) {
				$base64 = substr($texte, $p + $lm, $end - ($p + $lm));
				if ($c = base64_decode($base64, true)) {
					$texte = substr_replace($texte, $c, $p, $end + 2 - $p);
					$pos = $p + strlen($c);
				}
				else {
					$pos = $end;
				}
			}
		}

		return $texte;
	}
}
