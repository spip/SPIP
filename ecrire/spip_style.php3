<?php
	include("inc_version.php3");

	// parano XSS
	eregi("^([#0-9a-z]*).*-([#0-9a-z]*).*-([0-9a-z]*).*-([0-9a-z]*).*", "$couleur_claire-$couleur_foncee-$left-$right", $regs);
	list (,$couleur_claire,$couleur_foncee,$left,$right) = $regs;
	$ltr = ($left == 'left');
	$rtl = ($right == 'left');

	// En-tetes
	$lastmodified = @filemtime("spip_style.php3");
	$gmoddate = gmdate("D, d M Y H:i:s", $lastmodified);
	$if_modified_since = ereg_replace(';.*$', '', $HTTP_IF_MODIFIED_SINCE);
	$if_modified_since = trim(str_replace('GMT', '', $if_modified_since));
	if ($if_modified_since == $gmoddate) {
		http_status(304);
		$headers_only = true;
	}
	@Header ("Last-Modified: ".$gmoddate." GMT");
	@Header ("Expires: ".gmdate("D, d M Y H:i:s", $lastmodified + 7 * 24 * 3600)." GMT");
	@Header ("Content-Type: text/css");

	if ($headers_only) exit;
	
	// Envoyer la feuille de style
	if (!isset($couleur_claire))
		$couleur_claire = "#EDF3FE";
	if (!isset($couleur_foncee))
		$couleur_foncee = "#3874B0";
?>

/*
 * Police par defaut (bof...)
 */
body { 
	font-family: Verdana,Arial,Sans,sans-serif; 
}
td {
	text-align: <? echo $left; ?>;
}
/*
 * Formulaires
 */
.forml { width: 100%; padding: 2px; background-color: #E4E4E4; background-position: center bottom; float: none; color: #000000; }
.formo { width: 100%; padding: 2px; background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none; }
.fondl { background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none; color: #000000; }
.fondo { background-color: <?php echo $couleur_foncee; ?>; background-position: center bottom; float: none; color: #FFFFFF; }
.fondf { background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519; }
.maj-debut:first-letter { text-transform: uppercase; }

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


/* Icones de fonctions */

.icone36, icone36-danger {
	border: none;
	padding: 0px;
	margin: 0px;
	text-align: center;
	vertical-align: top;
	text-align: center;
	text-decoration: none;
}
.icone36 a, .icone36 a:hover, icone36-danger a, .icone36-danger a:hover {
	text-decoration: none;
}
.icone36 a img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: #eeeeee;
	border: 1px solid #cccccc;
	filter: alpha(opacity=100);
	-moz-border-radius: 5px;
}
.icone36 a:hover img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: white;
	border: 1px solid #666666;
	filter: alpha(opacity=100);
	-moz-border-radius: 5px;
}
.icone36-danger a img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: white;
	border: 2px solid #ff9999;
	filter: alpha(opacity=100);
	-moz-border-radius: 5px;
}
.icone36-danger a:hover img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: white;
	border: 2px solid red;
	filter: alpha(opacity=100);
	-moz-border-radius: 5px;
}
.icone36-danger a span {
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color: red; display: block; margin: 2px;
	filter: DropShadow(Color=white, OffX=1, OffY=1, Positive=1) DropShadow(Color=#cccccc, OffX=-1, OffY=-1, Positive=1);
	width: 100%
}
.icone36 a span {
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color: #666666; display: block; margin: 2px;
	filter: DropShadow(Color=white, OffX=1, OffY=1, Positive=1) DropShadow(Color=#cccccc, OffX=-1, OffY=-1, Positive=1);
	width: 100%
}
.icone36 a:hover span {
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color: #000000; display: block; margin: 2px;
	filter: DropShadow(Color=white, OffX=1, OffY=1, Positive=1) DropShadow(Color=#cccccc, OffX=-1, OffY=-1, Positive=1);
	width: 100%;
}


/* Icones 48 * 48 et 24 * 24 */

.cellule36, .cellule48 {
	border: none;
	padding: 0px;
	text-align: center;
	vertical-align: top;
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	text-align: center;
	text-decoration: none;
}
.cellule36 {
	margin: 0px;
	font-size: 10px;
}
.cellule48 {
	margin: 2px;
	font-size: 12px;
}
.cellule36 a, .cellule36 a:hover, .cellule48 a, .cellule48 a:hover {
	text-decoration: none;
}
.cellule36 a, .cellule48 a {
	display: block; text-align: center;
}
.cellule36 a img, .cellule48 a img {
	margin: 0px; 
	display: inline;
	padding: 4px;
	border: 0px;
	filter: alpha(opacity=70);
}
.cellule36 a.selection img, .cellule48 a.selection img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: white;
	border: 1px solid #aaaaaa;
	filter: alpha(opacity=100);
	-moz-border-radius: 5px;
}
.cellule36 a:hover img, .cellule48 a:hover img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: #dddddd;
	border: 1px solid #999999;
	filter: alpha(opacity=100);
	-moz-border-radius: 5px;
}
.cellule36 a span, .cellule48 a span {
	color: #666666; display: block; margin: 1px;
	filter: DropShadow(Color=white, OffX=1, OffY=1, Positive=1) DropShadow(Color=#cccccc, OffX=-1, OffY=-1, Positive=1);
	width: 100%;
}
.cellule36 a:hover span, .cellule48 a:hover span {
	color: #000000; display: block; margin: 1px;
	filter: DropShadow(Color=white, OffX=1, OffY=1, Positive=1) DropShadow(Color=#cccccc, OffX=-1, OffY=-1, Positive=1);
	width: 100%;
}
.cellule36 a.selection span, .cellule48 a.selection span {
	color: #000000; display: block; margin: 1px;
	filter: DropShadow(Color=white, OffX=-1, OffY=-1, Positive=1) DropShadow(Color=#cccccc, OffX=1, OffY=1, Positive=1);
	width: 100%;
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
	font-family: Verdana, Arial, Sans, sans-serif;
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
	display: block;
}
a.cellule-h {
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 10px;
	text-align: <?php echo $left; ?>;
	text-decoration: none; 
	color: #666666;
}
a.cellule-h:hover, a.cellule-h:hover a.cellule-h, a.cellule-h a.cellule-h:hover {
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 10px;
	text-align: <?php echo $left; ?>;
	text-decoration: none; 
	color: #000000;
}
a.cellule-h div.cell-i {
	padding: 1px;
	border: 0px;
	margin: 0px;
	margin-<?php echo $right; ?>: 3px;
	filter: alpha(opacity=50);
}
a.cellule-h:hover div.cell-i {
	padding: 0px;
	border: 1px solid #999999;
	background-color: white;
	-moz-border-radius: 5px;
	margin: 0px;
	margin-<?php echo $right; ?>: 3px;
}

a.cellule-h table {
	border: none;
	padding: 0px;
	margin: 0px;
}

a.cellule-h td.cellule-h-lien {
	filter: DropShadow(Color=white, OffX=1, OffY=1, Positive=1) DropShadow(Color=#cccccc, OffX=-1, OffY=-1, Positive=1);
}
a.cellule-h img {
	width: 24px;
	height: 24px;
	border: none;
	margin: 3px;
	background-repeat: no-repeat;
	background-position: center center;
}
a.cellule-h img {
	filter: alpha(opacity=40);
}
a.cellule-h:hover img {
	filter: alpha(opacity=100);
}

a.cellule-h a.aide img {
	width: 12px; height: 12px;
}


a.cellule-h-texte {
	display: block;
	clear: both;
	text-align: <?php echo $left; ?>;
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 9px;
	color: #606060;
	padding: 4px;
	margin: 3px;
	border: 1px solid #dddddd;
	-moz-border-radius: 5px;
	background-color: #f0f0f0;
	width: 92%;
}
.danger a.cellule-h-texte {
	border: 1px dashed black;
	background: url(img_pack/rayures-sup.gif);
}
a.cellule-h-texte:hover {
	text-decoration: none;
	color: black;
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
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
	border: 1px solid #aaaaaa;
	background-color: #e4e4e4;
	-moz-border-radius: 5px;
}
.bouton36blanc {
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
	border: 1px solid #999999;
	background-color: white;
	-moz-border-radius: 5px;
}
.bouton36rouge {
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
	border: 1px solid red;
	background-color: white;
	-moz-border-radius: 5px;
}
.bouton36off {
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
}


.reliefblanc { background-image: url(img_pack/barre-blanc.gif); }
.reliefgris { background-image: url(img_pack/barre-noir.gif); }
.iconeoff {
	padding: 3px; margin: 1px; border: 1px dashed #aaaaaa; background-color: #f0f0f0;
}
.iconeon { cursor: pointer; padding: 3px; margin: 1px;  border-right: solid 1px white; border-bottom: solid 1px white; border-left: solid 1px #666666; border-top: solid 1px #666666; background-color: #eeeeee; }
.iconedanger { padding: 3px; margin: 1px; border: 1px dashed black; background: url(img_pack/rayures-sup.gif);}

/* Raccourcis pour les polices (utile pour les tableaux) */
.arial0 { font-family: Arial, Sans, sans-serif; font-size: 9px; }
.arial1 { font-family: Arial, Sans, sans-serif; font-size: 10px; }
.arial2 { font-family: Arial, Sans, sans-serif; font-size: 12px; }
.verdana1 { font-family: Verdana, Arial, Sans, sans-serif; font-size: 10px; }
.verdana2 { font-family: Verdana, Arial, Sans, sans-serif; font-size: 11px; }
.verdana3 { font-family: Verdana, Arial, Sans, sans-serif; font-size: 13px; }
.serif { font-family: Georgia, Garamond, Times New Roman, serif; }
.serif2 { font-family: Georgia, Garamond, Times New Roman, serif; font-size: 13px; }

/* Liens hypertexte */
a { text-decoration: none; }
a:hover { text-decoration: underline; }
a.icone { text-decoration: none; }
a.icone:hover { text-decoration: none; }

/*
 * Barre de raccourcis
 */

a.spip_barre img {
	padding: 4px;
	margin: 1px;
	border: 1px solid #aaaaaa;
	background-color: #e4e4e4;
	-moz-border-radius: 5px;
}
a.spip_barre:hover img {
	padding: 4px;
	margin: 1px;
	border: 1px solid #999999;
	background-color: white;
	-moz-border-radius: 5px;
}

td.icone table {
}
td.icone a {
	color: black;
	text-decoration: none;
	font-family: Verdana,Arial,Sans,sans-serif;
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
 * Dessus-dessous calendrier
 */
 
.dessous {
	z-index : 1;
	-moz-opacity: 0.6; filter: alpha(opacity=60);
}
.dessus, .dessous.hover {
	z-index : 2; 
	-moz-opacity: 1; filter: alpha(opacity=100);
	cursor: pointer;
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
	text-align: <?php echo $left; ?>;
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
	text-align: <?php echo $left; ?>;
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
	text-align: <?php echo $left; ?>;
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
	text-align: <?php echo $left; ?>;
}


/*
 * Styles pour "Tout le site"
 */

.plan-rubrique {
	margin-<?php echo $left; ?>: 12px;
	padding-<?php echo $left; ?>: 10px;
	border-<?php echo $left; ?>: 1px dotted #888888;
}
.plan-secteur {
	margin-<?php echo $left; ?>: 12px;
	padding-<?php echo $left; ?>: 10px;
	border-<?php echo $left; ?>: 1px dotted #404040;
}
 
.plan-articles a {
	display: block;
	padding: 2px;
	margin-<?php echo $left; ?>: 2px;
	padding-<?php echo $left; ?>: 18px;
	background: <?php echo $ltr ? "1%" : "99%"; ?> no-repeat;
	background-color: #e0e0e0;
	border-top: 1px solid white;
	border-left: 1px solid white;
	border-right: 1px solid #aaaaaa;
	border-bottom: 1px solid #aaaaaa;
	font-family: Verdana, Arial, Sans, sans-serif;
	font-size: 11px;
	text-decoration: none;
}
.plan-articles a:hover {
	background-color: white; 
	border-bottom: 1px solid white; 
	border-right: 1px solid white; 
	border-left: 1px solid #aaaaaa; 
	border-top: 1px solid #aaaaaa;
	text-decoration: none;
}
.plan-articles .publie {
	background-image: url(img_pack/puce-verte-breve.gif);
}
.plan-articles .prepa {
	background-image: url(img_pack/puce-blanche-breve.gif);
}
.plan-articles .prop {
	background-image: url(img_pack/puce-orange-breve.gif);
}
.plan-articles .refuse {
	background-image: url(img_pack/puce-rouge-breve.gif);
}
.plan-articles .poubelle {
	background-image: url(img_pack/puce-rouge-breve.gif);
}

a.foncee, a.foncee:hover, a.claire, a.claire:hover, a.creer, a.creer:hover {
	display: inline;
	float: none;
	padding: 2px;
	margin: 0px;
	margin-left: 1px;
	margin-right: 1px;
	border: 0px;
	font-family: Verdana, Arial, Sans, sans-serif;
	font-size: 9px;
	text-decoration: none;

}

a.foncee, a.foncee:hover {
	background-color: <? echo $couleur_foncee; ?>;
	color: white;
}
a.claire, a.claire:hover {
	background-color: <? echo $couleur_claire; ?>;
	color: <? echo $couleur_foncee; ?>;
}
a.creer, a.creer:hover {
	background-color: white;
	color: black;
	border: 1px solid black;
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
	font-family: Verdana,Arial,Sans,sans-serif;
	font-weight: bold;
	font-size: 9px;
}
a.boutonlien:hover {color:#454545; text-decoration: none;}
a.boutonlien {color:#808080; text-decoration: none;}

h3.spip {
	margin-top : 40px;
	margin-bottom : 40px;
	font-family: Verdana,Arial,Sans,sans-serif;
	font-weight: bold;
	font-size: 115%;
	text-align: center;
}
.spip_documents{
	font-family: Verdana,Arial,Sans,sans-serif;
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
