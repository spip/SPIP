<?php

include ("inc.php3");


debut_page($titre_breve);
debut_gauche();


debut_boite_info();
?>
Cette page vous permet de personnaliser votre interface (page en construction).
Vous pouvez modifier, par exemple, le fond d'&eacute;cran de l'espace priv&eacute; de SPIP.

<?php
fin_boite_info();


debut_droite();


echo aide ("intermodif");

?>

<P>

<?php
	debut_cadre_relief();
?>
<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 WIDTH=100% BACKGROUND=''>
<TR>
<TD BGCOLOR='<?php echo $couleur_foncee; ?>'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>
<B>Survol :</B>
</TD></TR></TABLE>
<P>
<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>
Si l'interface de SPIP vous semble un peu lente, vous pouvez d&eacute;sactiver l'animation des boutons de la barre de navigation ci-dessus&nbsp;:



<CENTER>
<TABLE CELLPADDING=0 CELLSPACING=10 BORDER=0 BACKGROUND=''>
<TR><TD ALIGN='center'><A HREF="interface.php3?set_survol=off"><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>
<img src="IMG2/asuivre-on.gif" width="69" height="79" border="0"><BR><B>Sans animation de survol</B></A>
</FONT></TD>
<TD ALIGN='center'><A HREF="interface.php3?set_survol=on" onMouseOver="avecsurvol.src='IMG2/asuivre-on.gif'" onMouseOut="avecsurvol.src='IMG2/asuivre-off.gif'"><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>
<img src="IMG2/asuivre-off.gif" width="69" height="79" border="0" NAME='avecsurvol'><BR><B>Avec animation de survol</B></A>
</FONT></TD></TR>
</TABLE>


</CENTER>

</FONT>
<?php
	fin_cadre_relief();

?>


<P>

<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 WIDTH=100% BACKGROUND=''>
<TR>
<TD BGCOLOR='<?php echo $couleur_foncee; ?>'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>
<B>Fond d'&eacute;cran :</B>
</TD></TR></TABLE>


<CENTER>

<TABLE CELLPADDING=0 CELLSPACING=20 BORDER=0 BACKGROUND='' WIDTH=\"100%\">
<TR>

<TD ALIGN="center">
<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=1 CLASS="profondeur">
<TR><TD BACKGROUND='IMG2/rayures.gif'><A HREF="interface.php3?set_fond=1"><IMG SRC="IMG2/rayures.gif" BORDER=1 WIDTH=50 HEIGHT=50></A></TD></TR></TABLE>
</TD>
<TD>
<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=1 CLASS="profondeur">
<TR><TD BACKGROUND='IMG2/rayures.gif'><A HREF="interface.php3?set_fond=2"><IMG SRC="IMG2/blob.gif" BORDER=1 WIDTH=50 HEIGHT=50></A></TD></TR></TABLE>
</TD>
<TD>
<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=1 CLASS="profondeur">
<TR><TD BACKGROUND='IMG2/rayures.gif'><A HREF="interface.php3?set_fond=3"><IMG SRC="IMG2/carreaux.gif" BORDER=1 WIDTH=50 HEIGHT=50></A></TD></TR></TABLE>
</TD>
<TD>
<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=1 CLASS="profondeur">
<TR><TD BACKGROUND='IMG2/rayures.gif'><A HREF="interface.php3?set_fond=4"><IMG SRC="IMG2/fond-trame.gif" BORDER=1 WIDTH=50 HEIGHT=50></A></TD></TR></TABLE>
</TD>
<TD>
<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=1 CLASS="profondeur">
<TR><TD BACKGROUND='IMG2/rayures.gif'><A HREF="interface.php3?set_fond=5"><IMG SRC="IMG2/degrade.jpg" BORDER=1 WIDTH=50 HEIGHT=50></A></TD></TR></TABLE>
</TD>
</TR></TABLE>
</CENTER>



<?php

	debut_cadre_relief();

echo "<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 WIDTH=100% BACKGROUND=''>";
echo "<TR>";
echo "<TD BGCOLOR='$couleur_foncee'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo "<B>Couleurs :</B>";
echo "</TD></TR>";

echo "<TR><TD BGCOLOR='#FFFFFF'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3> <A HREF='interface.php3?set_couleur=6'>Bleu</A></TD></TR>";
echo "<TD BGCOLOR='$couleur_claire'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3> <A HREF='interface.php3?set_couleur=1'>Vert</A></TD></TR>";
echo "<TD BGCOLOR='#FFFFFF'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>  <A HREF='interface.php3?set_couleur=2'>Rouge</A></TD></TR>";
echo "<TD BGCOLOR='$couleur_claire'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>  <A HREF='interface.php3?set_couleur=3'>Jaune</A></TD></TR>";
echo "<TD BGCOLOR='#FFFFFF'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>  <A HREF='interface.php3?set_couleur=4'>Violet</A></TD></TR>";
echo "<TD BGCOLOR='$couleur_claire'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>  <A HREF='interface.php3?set_couleur=5'>Gris</A></TD></TR>";


echo "</TR></TABLE>";

	fin_cadre_relief();









fin_page();

?>
