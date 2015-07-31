<?php

if (!defined("_ECRIRE_INC_VERSION")) return;


/**
 * Transforme un objet SimpleXML en tableau PHP
 *
 * @param object $obj
 * @return array
**/
// http://www.php.net/manual/pt_BR/book.simplexml.php#108688
// xaviered at gmail dot com 17-May-2012 07:00
function inc_simplexml_to_array_dist($obj, $utiliser_namespace='false') {

	$tableau = array();

	// Cette fonction getDocNamespaces() est longue sur de gros xml. On permet donc
	// de l'activer ou pas suivant le contenu supposé du XML
	if (is_object($obj)) {
		if ($utiliser_namespace)
			$namespace = $obj->getDocNamespaces(true);
		$namespace[NULL] = NULL;

		$name = strtolower((string)$obj->getName());
		$text = trim((string)$obj);
		if (strlen($text) <= 0) {
			$text = NULL;
		}

		$children = array();
		$attributes = array();

		// get info for all namespaces
		foreach( $namespace as $ns=>$nsUrl ) {
			// attributes
			$objAttributes = $obj->attributes($ns, true);
			foreach( $objAttributes as $attributeName => $attributeValue ) {
				$attribName = strtolower(trim((string)$attributeName));
				$attribVal = trim((string)$attributeValue);
				if (!empty($ns)) {
					$attribName = $ns . ':' . $attribName;
				}
				$attributes[$attribName] = $attribVal;
			}

			// children
			$objChildren = $obj->children($ns, true);
			foreach( $objChildren as $childName=>$child ) {
				$childName = strtolower((string)$childName);
				if( !empty($ns) ) {
					$childName = $ns.':'.$childName;
				}
				$children[$childName][] = inc_simplexml_to_array_dist($child);
			}
		}

		$tableau = array(
			'name'=>$name,
			'text'=>$text,
			'attributes'=>$attributes,
			'children'=>$children
		);
	}

	return $tableau;
}

?>
