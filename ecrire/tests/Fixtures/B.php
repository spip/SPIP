<?php

declare(strict_types=1);


namespace Spip\Test\Fixtures;

class B extends \stdClass {
	public $publicValue = 'public';
	protected $protectedValue = 'protected';

	public static function __set_state($an_array) {
        $obj = new static();
		foreach ($an_array as $k => $v) {
			$obj->$k = $v;
		}
        return $obj;
    }
}
