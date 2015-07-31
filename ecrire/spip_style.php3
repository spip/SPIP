<?php
	@header ("Content-Type: text/css");
	include ("inc_version.php3");	// pour le register_globals
	if (!isset($couleur_claire))
		$couleur_claire = "#EDF3FE";
	if (!isset($couleur_foncee))
		$couleur_foncee = "#3874B0";
?>
.forml {width: 100%; padding: 2px; background-color: #E4E4E4; background-position: center bottom; float: none; color: #000000}
.formo {width: 100%; padding: 2px; background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none;}
.fondl {background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none; color: #000000}
.fondo {background-color: <?php echo $couleur_foncee; ?>; background-position: center bottom; float: none; color: #FFFFFF}
.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
.sanscadre {padding: 4px; margin: 0px; }
.aveccadre {cursor: pointer; padding: 3px; margin: 0px; border-left: solid 1px <?php echo $couleur_claire; ?>; border-top: solid 1px <?php echo $couleur_claire; ?>; border-right: solid 1px #000000; border-bottom: solid 1px #000000;}
.iconeimpoff {padding: 3px; margin: 1px; border: 1px dashed <? echo $couleur_foncee; ?>; background-color: #e4e4e4}

.fondgris {cursor: pointer; padding: 4px; margin: 1px;}
.fondgrison {cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: #e4e4e4;}
.fondgrison2 {cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: white;}

.profondeur {border-right-color:white; border-top-color:#666666; border-left-color:#666666; border-bottom-color:white; border-style:solid}
.hauteur {border-right-color:#666666; border-top-color:white; border-left-color:white; border-bottom-color:#666666; border-style:solid}
label {cursor: pointer;}
.arial1 { font-family: Arial, Helvetica, sans-serif; font-size: 10px; }
.arial2 { font-family: Arial, Helvetica, sans-serif; font-size: 12px; }

.reliefblanc {background-image: url(img_pack/barre-blanc.gif)}
.reliefgris {background-image: url(img_pack/barre-noir.gif)}
.iconeoff {padding: 3px; margin: 1px; border: 1px dashed #aaaaaa; background-color: #f0f0f0}
.iconeon {cursor: pointer; padding: 3px; margin: 1px;  border-right: solid 1px white; border-bottom: solid 1px white; border-left: solid 1px #666666; border-top: solid 1px #666666; background-color: #eeeeee;}

a { text-decoration: none; }
a:hover { text-decoration: underline; }
a.icone { text-decoration: none; }
a.icone:hover { text-decoration: none; }

a.spip_in  {background-color:#eeeeee;}
a.spip_out {}
a.spip_note {}
.spip_recherche {padding: 2px; width : 100px; font-size: 9px;}
.spip_cadre { 
	width : 100%;
	background-color: #FFFFFF; 
	padding: 5px; 
}

.boutonlien {
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-weight: bold;
	font-size: 9px;
}
a.boutonlien:hover {color:#454545; text-decoration: none;}
a.boutonlien {color:#808080; text-decoration: none;}

h3.spip {
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-weight: bold;
	font-size: 115%;
	text-align: center;
}
.spip_documents{
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-size : 70%;
}
table.spip {
}
table.spip tr.row_first {
	background-color: #FCF4D0;
}
table.spip tr.row_odd {
	background-color: #C0C0C0;
}
table.spip tr.row_even {
	background-color: #F0F0F0;
}
table.spip td {
	padding: 1px;
	text-align: left;
	vertical-align: center;
}
