<?php

namespace Spip\Utils;

/**
 * Surcharge de \Zumba\JsonSerializer\JsonSerializer pour gérer une liste de classes autorisées à la deserialization
 */
class JsonSerializer extends \Zumba\JsonSerializer\JsonSerializer {

	protected $allowed_classes = false;

	/**
	 * Convert the serialized array into an object
	 *
	 * @param array $value
	 * @return object
	 */
	protected function unserializeObject($value) {
		$className = $value[static::CLASS_IDENTIFIER_KEY];

		if ($this->allowed_classes === false
		  or (is_array($this->allowed_classes) and !in_array($className, $this->allowed_classes))) {
			$value['__PHP_Incomplete_Class_Name'] = $className;
			$value[static::CLASS_IDENTIFIER_KEY] = 'stdClass';
		}

		return parent::unserializeObject($value);
	}

	/**
	 * @param bool|array $allowed_classes
	 * @return array|bool
	 */
	public function setAllowedClasses($allowed_classes) {
		if (is_bool($allowed_classes) or is_array($allowed_classes)) {
			$this->allowed_classes = $allowed_classes;
		}
		return $this->allowed_classes;
	}

}
