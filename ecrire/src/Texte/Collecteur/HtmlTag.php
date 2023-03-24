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
class HtmlTag extends AbstractCollecteur {
	protected static string $markPrefix = 'HTMLTAG';

	/**
	 * La preg pour découper et collecter les modèles
	 * @var string
	 */
	protected string $preg_openingtag;
	protected string $preg_closingtag;
	protected string $tag;

	public function __construct(string $tag, ?string $preg_openingtag = null, ?string $preg_closingtag = null) {

		$tag = strtolower($tag);
		$this->tag = $tag;
		$this->preg_openingtag = ($preg_openingtag ?: "@<{$tag}\b([^>]*?)(/?)>@isS");
		$this->preg_closingtag = ($preg_closingtag ?: "@</{$tag}\b[^>]*>@isS");
	}

	/**
	 * @param string $texte
	 * @param array $options
	 *   bool $detecter_presence
	 *   bool $nb_max
	 * @return array
	 */
	public function collecter(string $texte, array $options = []): array {
		if (!$texte) {
			return [];
		}

		$upperTag = strtoupper($this->tag);
		$hasUpperCaseTags = (strpos($texte, '<' . $upperTag) !== false or strpos($texte, '</' . $upperTag) !== false);

		// collecter les balises ouvrantes
		$opening = static::collecteur($texte, '', $hasUpperCaseTags ? '<' : '<' . $this->tag, $this->preg_openingtag, empty($options['detecter_presence']) ? 0 : 1);
		if (!$opening) {
			return [];
		}

		// collecter les balises fermantes
		$closing = static::collecteur($texte, '', $hasUpperCaseTags ? '</' : '</' . $this->tag, $this->preg_closingtag);

		#var_dump($opening);
		#var_dump($closing);

		// enlever les closing qui sont avant le premier opening, car ils n'ont pas de sens
		$first_opening = reset($opening);
		while (!empty($closing)
		  and $first_closing = reset($closing)
		  and $first_closing['pos'] < $first_opening['pos']) {
			array_shift($closing);
		}

		$tags = [];
		while (!empty($opening)) {
			$first_opening = array_shift($opening);
			// self closing ?
			if (strpos($first_opening['raw'], '/>', -2) !== false) {
				$tag = $first_opening;
				$tag['opening'] = $tag['raw'];
				$tag['closing'] = '';
				$tag['innerHtml'] = '';
				$tags[] = $tag;
			}
			else {
				$need_closing = 0;
				$next_closing = reset($closing);
				$next_opening = reset($opening);
				while ($next_opening and $next_closing and $next_opening['pos'] < $next_closing['pos']) {
					while ($next_opening and $next_opening['pos'] < $next_closing['pos']) {
						// si pas self closing, il faut un closing de plus
						if (strpos($next_opening['raw'], '/>', -2) === false) {
							$need_closing++;
						}
						array_shift($opening);
						$next_opening = reset($opening);
					}
					// il faut depiler les balises fermantes autant de fois que nécessaire et tant qu'on a pas une nouvelle balise ouvrante
					while ($need_closing and $next_closing and (!$next_opening or $next_closing['pos'] < $next_opening['pos'])) {
						array_shift($closing);
						$need_closing--;
						$next_closing = reset($closing);
					}
				}
				// si pas de fermeture, c'est une autofermante mal fermée...
				if (!$next_closing or $need_closing) {
					$tag = $first_opening;
					$tag['opening'] = $tag['raw'];
					$tag['closing'] = '';
					$tag['innerHtml'] = '';
					$tags[] = $tag;
				}
				else {
					$tag = $first_opening;
					$next_closing = array_shift($closing);
					$innerHtml = substr($texte, $tag['pos'] + $tag['length'], $next_closing['pos'] - $tag['pos'] - $tag['length']);
					$tag['length'] = $next_closing['pos'] - $tag['pos'] + $next_closing['length'];
					$tag['opening'] = $tag['raw'];
					$tag['raw'] = substr($texte, $tag['pos'], $tag['length']);
					$tag['innerHtml'] = $innerHtml;
					$tag['closing'] = $next_closing['raw'];
					$tags[] = $tag;
				}
			}
			if (
				   (!empty($options['detecter_presence']) and count($tags))
				or (!empty($options['nb_max'])  and count($tags) >= $options['nb_max'])
			) {
				return $tags;
			}
		}


		return $tags;
	}

}
