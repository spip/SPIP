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


/**
 * Transforme un texte XML en tableau PHP
 *
 * @param string|object $u
 * @param bool $utiliser_namespace
 * @return array
 */
function inc_simplexml_to_array_dist($u, $utiliser_namespace = false) {
	// decoder la chaine en SimpleXML si pas deja fait
	if (is_string($u)) {
		$u = simplexml_load_string($u);
	}

	return ['root' => @xmlObjToArr($u, $utiliser_namespace)];
}


/**
 * Transforme un objet SimpleXML en tableau PHP
 * http://www.php.net/manual/pt_BR/book.simplexml.php#108688
 * xaviered at gmail dot com 17-May-2012 07:00
 *
 * @param object $obj
 * @param bool $utiliser_namespace
 * @return array
 **/
function xmlObjToArr($obj, $utiliser_namespace = false) {

	$namespace = [];
	$tableau = [];

	// Cette fonction getDocNamespaces() est longue sur de gros xml. On permet donc
	// de l'activer ou pas suivant le contenu supposé du XML
	if (is_object($obj)) {
		if (is_array($utiliser_namespace)) {
			$namespace = $utiliser_namespace;
		} else {
			if ($utiliser_namespace) {
				$namespace = $obj->getDocNamespaces(true);
			}
			$namespace[null] = null;
		}

		$name = strtolower((string)$obj->getName());
		$text = trim((string)$obj);
		if (strlen($text) <= 0) {
			$text = null;
		}

		$children = [];
		$attributes = [];

		// get info for all namespaces
		foreach (array_keys($namespace) as $ns) {
			// attributes
			$objAttributes = $obj->attributes($ns, true);
			foreach ($objAttributes as $attributeName => $attributeValue) {
				$attribName = strtolower(trim((string)$attributeName));
				$attribVal = trim((string)$attributeValue);
				if (!empty($ns)) {
					$attribName = $ns . ':' . $attribName;
				}
				$attributes[$attribName] = $attribVal;
			}

			// children
			$objChildren = $obj->children($ns, true);
			foreach ($objChildren as $childName => $child) {
				$childName = strtolower((string)$childName);
				if (!empty($ns)) {
					$childName = $ns . ':' . $childName;
				}
				$children[$childName][] = xmlObjToArr($child, $namespace);
			}
		}

		$tableau = [
			'name' => $name,
		];
		if ($text) {
			$tableau['text'] = $text;
		}
		if ($attributes) {
			$tableau['attributes'] = $attributes;
		}
		if ($children) {
			$tableau['children'] = $children;
		}
	}

	return $tableau;
}
