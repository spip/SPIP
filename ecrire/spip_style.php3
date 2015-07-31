<?php
	include ("inc_version.php3");	// pour le register_globals
	@Header ("Content-Type: text/css");
	@Header ("Expires: ".gmdate("D, d M Y H:i:s", time() + 7 * 24 * 3600)." GMT");
	@Header ("Last-Modified: ".gmdate("D, d M Y H:i:s", @filemtime("spip_style.php3"))." GMT");
	if (!isset($couleur_claire))
		$couleur_claire = "#EDF3FE";
	if (!isset($couleur_foncee))
		$couleur_foncee = "#3874B0";
?>

/*
 * Police par defaut (bof...)
 */
body { font-family: Verdana,Arial,Helvetica,sans-serif; }

/*
 * Formulaires
 */
.forml { width: 100%; padding: 2px; background-color: #E4E4E4; background-position: center bottom; float: none; color: #000000 }
.formo { width: 100%; padding: 2px; background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none; }
.fondl { background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none; color: #000000 }
.fondo { background-color: <?php echo $couleur_foncee; ?>; background-position: center bottom; float: none; color: #FFFFFF }
.fondf { background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519 }


/*
 * Icones et bandeaux
 */

.bandeau-principal {
	display: block;
	margin: 0px;
	padding: 0px;
	padding-top: 0px;
	background: url(img_pack/rayures-fines.gif);
	border-bottom: 1px solid #333333;
}
.bandeau-secondaire {
	display: block;
	margin: 0px;
	padding: 0px;
	background-color: #f1f1f1;
	border-bottom: 1px solid black;
	border-top: 1px solid #aaaaaa;
}
.bandeau-icones {
	display: block;
	margin: auto;
	padding: 2px;
}
.bandeau-icones .gauche {
	float: <?php echo $left; ?>;
}
.bandeau-icones .droite {
	float: <?php echo $right; ?>;
}
.bandeau-icones .milieu {
	text-align: center;
}
.bandeau-icones .fin {
	clear: both;
}
.bandeau-icones .separateur {
	vertical-align: center;
	height: 100%;
	width: 11px;
	padding: 0px;
	margin: 0px;
	background: url(img_pack/tirets-separation.gif);
	background-position: 5px 0px;
}

/* Icones 48 * 48 et 24 * 24 */

.cellule36, .cellule48 {
	border: none;
	padding: 0px;
	margin: 0px;
	text-align: center;
	vertical-align: top;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
	text-align: center;
	text-decoration: none;
}
.cellule36 {
	font-size: 10px;
}
.cellule48 {
	font-size: 12px;
}
.cellule36 a, .cellule36 a:hover, .cellule48 a, .cellule48 a:hover {
	text-decoration: none;
}
.cellule36 a, .cellule48 a {
	display: block; text-align: center; background: url(img_pack/rien.gif) no-repeat top center;
}
.cellule36 a.selection {
	display: block; text-align: center; background: url(img_pack/pave-blanc-36.png) no-repeat top center;
}
.cellule48 a.selection {
	display: block; text-align: center; background: url(img_pack/pave-blanc-48.png) no-repeat top center;
}
/*.cellule36 a:hover {
	background: url(img_pack/pave-blanc-36.png) no-repeat top center;
}
.cellule48 a:hover {
	background: url(img_pack/pave-blanc-48.png) no-repeat top center;
}*/
.cellule36 a img {
	border: 0px; margin: 6px; display: inline;
	-moz-opacity: 0.5;
	filter: alpha(opacity=50);
}
.cellule36 a.selection img, .cellule36 a:hover img {
	border: 0px; margin: 6px; display: inline;
	-moz-opacity: 1;
	filter: alpha(opacity=100);
}
.cellule48 a img {
	border: 0px; margin: 3px; display: inline;
	-moz-opacity: 0.5;
	filter: alpha(opacity=50);
}
.cellule48 a.selection img, .cellule48 a:hover img {
	border: 0px; margin: 3px; display: inline;
	-moz-opacity: 1;
	filter: alpha(opacity=100);
}
.cellule36 a span, .cellule48 a span {
	color: #666666; display: block; margin: 2px;
	filter: DropShadow(Color=white, OffX=1, OffY=1, Positive=1) DropShadow(Color=#cccccc, OffX=-1, OffY=-1, Positive=1);
	width: 100%
}
.cellule36 a:hover span, .cellule48 a:hover span {
	color: #000000; display: block; margin: 2px;
	filter: DropShadow(Color=white, OffX=1, OffY=1, Positive=1) DropShadow(Color=#cccccc, OffX=-1, OffY=-1, Positive=1);
	width: 100%
}
.cellule36 a.selection span, .cellule48 a.selection span {
	color: #000000; display: block; margin: 2px;
	filter: DropShadow(Color=white, OffX=-1, OffY=-1, Positive=1) DropShadow(Color=#cccccc, OffX=1, OffY=1, Positive=1);
	width: 100%
}

.cellule36 a.aide, .cellule36 a.aide:hover {
	display: inline;
	background: none;
	margin: 0px;
	padding: 0px;
}
.cellule36 a.aide img {
	margin: 0px;
	padding: 0px;
}

/* Navigation texte */

.cellule-texte {
	border: none;
	padding: 0px;
	margin: 0px;
	text-align: center;
	vertical-align: top;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
	text-align: center;
	text-decoration: none;
	font-size: 10px;
}
.cellule-texte a, .cellule-texte a:hover {
	text-decoration: none;
	display: block;
}
.cellule-texte a {
	padding: 4px; margin: 1px; border: 0px;
	color: #606060;
}
.cellule-texte a.selection {
	padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: #e4e4e4;
	-moz-border-radius: 5px;
	color: #000000;
}
.cellule-texte a:hover {
	padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: white;
	-moz-border-radius: 5px;
	color: #333333;
}
.cellule-texte a.aide, .cellule-texte a.aide:hover {
	border: none;
	background: none;
	display: inline;
}
.cellule-texte a.aide img {
	margin: 0px;
}

/*
 * Icones horizontales
 */

a.cellule-h {
	text-align: <?php echo $left; ?>;
	display: block;
	margin-top: 1px;
	margin-bottom: 1px;
}
a.cellule-h {
	text-decoration: none; 
}
a.cellule-h:hover {
	text-decoration: none; 
}
a.cellule-h img {
	-moz-opacity: 0.5;
	filter: alpha(opacity=50);
}
a.cellule-h:hover img {
	-moz-opacity: 1;
	filter: alpha(opacity=100);
}
.danger a.cellule-h {
	text-decoration: none; background: url(img_pack/pave-gris-24.png) no-repeat center <?php echo $left; ?>;
}
.danger a.cellule-h:hover {
	text-decoration: none; background: url(img_pack/pave-rouge-24.png) no-repeat center <?php echo $left; ?>;
}
a.cellule-h table {
	border: none;
	padding: 0px;
	margin: 0px;
	filter: DropShadow(Color=white, OffX=1, OffY=1, Positive=1) DropShadow(Color=#cccccc, OffX=-1, OffY=-1, Positive=1);
}
a.cellule-h td {
	text-align: <?php echo $left; ?>;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color: #666666;
}
a.cellule-h:hover td {
	text-align: <?php echo $left; ?>;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color: #000000;
}
a.cellule-h img {
	width: 24px;
	height: 24px;
	border: none;
	margin: 1px;
	margin-<?php echo $right; ?>: 6px;
	background-repeat: no-repeat;
	background-position: center center;
}
a.cellule-h a.aide img {
	width: 12px; height: 12px;
}


a.cellule-h-texte {
	display: block;
	clear: both;
	text-align: <?php echo $left; ?>;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 11px;
	color: #606060;
	padding: 4px;
	margin: 3px;
	border: 1px dashed #aaaaaa;
	background-color: #f0f0f0;
	width: 92%;
}
.danger a.cellule-h-texte {
	border: 1px dashed black;
	background: url(img_pack/rayures-sup.gif);
}
a.cellule-h-texte:hover {
	text-decoration: none;
	border-right: solid 1px white;
	border-bottom: solid 1px white;
	border-left: solid 1px #666666;
	border-top: solid 1px #666666;
	background-color: #eeeeee;
}



/*
 * Style des icones
 */

.fondgris { cursor: pointer; padding: 4px; margin: 1px; }
.fondgrison { cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: #e4e4e4; }
.fondgrison2 { cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: white; }
.bouton36gris {
	background:url(img_pack/pave-gris-36.png);
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
}
.bouton36blanc {
	background:url(img_pack/pave-blanc-36.png);
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
}
.bouton36rouge {
	background:url(img_pack/pave-rouge-36.png);
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
}
.bouton36off {
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
}


.reliefblanc { background-image: url(img_pack/barre-blanc.gif) }
.reliefgris { background-image: url(img_pack/barre-noir.gif) }
.iconeoff {
	padding: 3px; margin: 1px; border: 1px dashed #aaaaaa; background-color: #f0f0f0
}
.iconeon { cursor: pointer; padding: 3px; margin: 1px;  border-right: solid 1px white; border-bottom: solid 1px white; border-left: solid 1px #666666; border-top: solid 1px #666666; background-color: #eeeeee; }
.iconedanger { padding: 3px; margin: 1px; border: 1px dashed black; background: url(img_pack/rayures-sup.gif)}

/* Raccourcis pour les polices (utile pour les tableaux) */
.arial1 { font-family: Arial, Helvetica, sans-serif; font-size: 10px; }
.arial2 { font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
.verdana1 { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px; }
.verdana2 { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; }

/* Liens hypertexte */
a { text-decoration: none; }
a:hover { text-decoration: underline; }
a.icone { text-decoration: none; }
a.icone:hover { text-decoration: none; }

/*
 * Barre de raccourcis
 */

a.spip_barre {
	border: 0px solid #666666;
	padding: 4px;
	margin-right: 1px;
	margin-left: 1px;
	background: url(img_pack/pave-gris-16.png);
}
a.spip_barre:hover {
	border: 0px solid #666666;
	padding: 4px;
	margin-right: 1px;
	margin-left: 1px;
	background: url(img_pack/pave-blanc-16.png);
}

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
	margin: 0px;
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
 * Styles generes par les raccourcis de mis en page
 */

a.spip_in  {background-color:#eeeeee;}
a.spip_note {background-color:#eeeeee;}
a.spip_out {}
a.spip_url {}
a.spip_glossaire:hover {text-decoration: underline overline;}

.spip_recherche {padding: 2px; width : 100px; font-size: 10px;}
.spip_cadre {
	width : 100%;
	background-color: #FFFFFF;
	padding: 5px;
}
blockquote.spip {
	margin-<?php echo $left; ?>: 40px;
	margin-<?php echo $right; ?>: 0px;
	margin-top : 10px;
	margin-bottom : 10px;
	border : solid 1px #aaaaaa;
	background-color: #ffffff;
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
	margin-top : 40px;
	margin-bottom : 40px;
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
