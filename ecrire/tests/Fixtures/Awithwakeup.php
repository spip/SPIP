<?php

declare(strict_types=1);


namespace Spip\Test\Fixtures;

class Awithwakeup extends A {

    public function __wakeup() {
        echo "oups";
    }

}
