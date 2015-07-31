<?php
	@header ("Content-Type: text/css");
	@Header ("Expires: ".gmdate("D, d M Y H:i:s", time() + 3600)." GMT");
	include ("inc_version.php3");	// pour le register_globals
	if (!isset($couleur_claire))
		$couleur_claire = "#EDF3FE";
	if (!isset($couleur_foncee))
		$couleur_foncee = "#3874B0";
?>
.forml {width: 100%; padding: 2px; background-color: #E4E4E4; 
background-position: center bottom; float: none; color: #000000}
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
.verdana1 { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px; }
.verdana2 { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; }

.reliefblanc {background-image: url(img_pack/barre-blanc.gif)}
.reliefgris {background-image: url(img_pack/barre-noir.gif)}
.iconeoff {padding: 3px; margin: 1px; border: 1px dashed #aaaaaa; background-color: #f0f0f0}
.iconeon {cursor: pointer; padding: 3px; margin: 1px;  border-right: solid 1px white; border-bottom: solid 1px white; border-left: solid 1px #666666; border-top: solid 1px #666666; background-color: #eeeeee;}

a { text-decoration: none; }
a:hover { text-decoration: underline; }
a.icone { text-decoration: none; }
a.icone:hover { text-decoration: none; }


td.icone table {
}
td.icone a {
	color: black;
	text-decoration: none;
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-size: 10px;
	font-weight: bold;
}
td.icone a:hover {
	text-decoration: none;
}
td.icone a img {
	border: 0px;
}

/*
 * Cadre blanc arrondi
 */

.cadre {
	padding: 0px;
	border: 0px;
	width: 100%;
}
/* Haut-gauche, etc. */
.r-hg 	{
	width: 5px; height: 24px; background: url('img_pack/rond-hg-24.gif') no-repeat right bottom;
}
.r-h {
	height: 24px; background: url('img_pack/rond-h-24.gif') repeat-x bottom;
}
.r-hd {
	width: 5px; height: 24px; background: url('img_pack/rond-hd-24.gif') no-repeat left bottom;
}
.r-g {
	width: 5px; background: url('img_pack/rond-g.gif') repeat-y right;
}
.r-d {
	width: 5px; background: url('img_pack/rond-d.gif') repeat-y left;
}
.r-bg {
	width: 5px; height: 5px; background: url('img_pack/rond-bg.gif') no-repeat right top;
}
.r-b {
	height: 5px; background: url('img_pack/rond-b.gif') repeat-x top;
}
.r-bd {
	width: 5px; height: 5px; background: url('img_pack/rond-bd.gif') no-repeat left top;
}
.r-c {
	background: white; padding: 2px;
}


/*
 * Cadre gris enfonce
 */

/* Haut-gauche, etc. */
.e-hg {
	width: 5px; height: 24px; background: url('img_pack/cadre-hg.gif') no-repeat right bottom;
}
.e-h {
	height: 24px; background: url('img_pack/cadre-h.gif') repeat-x bottom;
}
.e-hd {
	width: 5px; height: 24px; background: url('img_pack/cadre-hd.gif') no-repeat left bottom;
}
.e-g {
	width: 5px; background: url('img_pack/cadre-g.gif') repeat-y right;
}
.e-d {
	width: 5px; background: url('img_pack/cadre-d.gif') repeat-y left;
}
.e-bg {
	width: 5px; height: 5px; background: url('img_pack/cadre-bg.gif') no-repeat right top;
}
.e-b {
	height: 5px; background: url('img_pack/cadre-b.gif') repeat-x top;
}
.e-bd {
	width: 5px; height: 5px; background: url('img_pack/cadre-bd.gif') no-repeat left top;
}
.e-c {
	background: #e0e0e0; padding: 2px;
}


/*
 * Icones horizontales
 * (on utilise deux styles distincts car IE ne gere pas le :hover)
 */

.icone-h {
	padding: 3px;
	margin: 2px;
	border: 1px dashed #aaaaaa;
	background-color: #f0f0f0;
	width: 100%;
	color: #666666;
	text-decoration: none;
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-size: 10px;
	font-weight: bold;
}
.icone-h-on {
	cursor: pointer;
	padding: 3px;
	margin: 2px;
	border-right: solid 1px white;
	border-bottom: solid 1px white;
	border-left: solid 1px #666666;
	border-top: solid 1px #666666;
	background-color: #eeeeee;
	width: 100%;
	color: #666666;
	text-decoration: none;
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-size: 10px;
	font-weight: bold;
}
.icone-h .image {
	width: 24px;
	height: 24px;
	background-repeat: no-repeat;
}
.icone-h-on .image {
	width: 24px;
	height: 24px;
	background-repeat: no-repeat;
}
.icone-h a {
	color: #666666;
	text-decoration: none;
}
.icone-h-on a {
	color: #666666;
	text-decoration: none;
}
.icone-h .image img {
	width: 24px;
	height: 24px;
	border: 0px;
}
.icone-h-on .image img {
	width: 24px;
	height: 24px;
	border: 0px;
}


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
