<?php
require_once('lanceur_spip.php');

class AllTests_barre_outil_markitup extends SpipTestSuite {
    function AllTests_barre_outil_markitup() {
        $this->SpipTestSuite('Barre MarkitUp');
		$this->addDir(__FILE__);
        #$this->addFile(find_in_path('tests/barre_outil_markitup.php'));
    }
}

?>
