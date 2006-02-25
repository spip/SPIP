<?php

// iso latin 15 - Gaetan Ryckeboer <gryckeboer@virtual-net.fr>

load_charset('iso-8859-1');

$trans = $GLOBALS['CHARSET']['iso-8859-1'];
$trans[164]=8364;
$trans[166]=352;
$trans[168]=353;
$trans[180]=381;
$trans[184]=382;
$trans[188]=338;
$trans[189]=339;
$trans[190]=376;

$GLOBALS['CHARSET']['iso-8859-15'] = $trans;

?>
