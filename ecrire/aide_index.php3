<?php

include ("inc_version.php3");


echo "<HTML>";
echo "<HEAD>";
echo "<TITLE>"._T('info_aide_en_ligne')."</TITLE>";
echo "</HEAD>";

?>

<FRAMESET Cols="150,*" border=0 FRAMEBORDER=0 FRAMESPACING=0>
<frame src="<?php echo "aide_gauche.php3?aide=$aide&les_rub=$les_rub"; ?>" name="gauche" marginheight="0" marginwidth="0" scrolling="auto" noresize>
<frame src="<?php echo "aide_droite.php3?aide=$aide"; ?>" name="droite" marginheight="15" marginwidth="15" scrolling="auto" noresize>
</frameset>



</HTML>