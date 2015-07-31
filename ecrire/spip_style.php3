<?php
	include("inc_version.php3");

	// parano XSS
	eregi("^([#0-9a-z]*).*-([#0-9a-z]*).*-([0-9a-z]*).*-([0-9a-z]*).*", "$couleur_claire-$couleur_foncee-$left-$right", $regs);
	list (,$couleur_claire,$couleur_foncee,$left,$right) = $regs;
	$ltr = ($left == 'left');
	$rtl = ($right == 'left');
	
	if ($left == 'left') $_rtl = "";
	else $_rtl = "_rtl";
	
	// En-tetes
	$lastmodified = @filemtime("spip_style.php3");
	$headers_only = http_last_modified($lastmodified, time() + 24 * 3600);
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
	border: 0px;
	scrollbar-face-color: white; 				
	scrollbar-shadow-color: white; 				
	scrollbar-highlight-color: white;
	scrollbar-3dlight-color: <?php echo $couleur_claire; ?>;
	scrollbar-darkshadow-color: white; 		
	scrollbar-track-color: <?php echo $couleur_foncee; ?>;
	scrollbar-arrow-color: <?php echo $couleur_foncee; ?>;
}
td {
	text-align: <?php echo $left; ?>;
}
/*
 * Formulaires
 */
.forml { 
	width: 100%;
	display: block;
	padding: 3px; 
	background-color: #e4e4e4; 
	border: 1px solid <?php echo $couleur_claire; ?>; 
	background-position: center bottom; 
	float: none;
	behavior: url("win_width.htc");
 }
.formo { 
	width: 100%; 
	display: block;
	padding: 3px; 
	background-color: white; 
	border: 1px solid <?php echo $couleur_claire; ?>; 
	background-position: center bottom; float: none; 
	behavior: url("win_width.htc");
}
.fondl { 
	padding: 3px; 
	background-color: #e4e4e4; 
	border: 1px solid <?php echo $couleur_claire; ?>; 
	background-position: center bottom; 
	float: none;
}
.fondo { background-color: <?php echo $couleur_foncee; ?>; background-position: center bottom; float: none; color: #FFFFFF; }
.fondf { background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519; }
.maj-debut:first-letter { text-transform: uppercase; }


.format_png {
	behavior: url("win_png.htc");
}


/*
 * Icones et bandeaux
 */

.bandeau-principal {
	background-color: white;
	margin: 0px;
	padding: 0px;
	border-bottom: 1px solid black;
}

.bandeau-icones {
	background-color: white;
	margin: 0px;
	padding: 0px;
	padding-bottom: 2px; 
	padding-top: 4px;
}

.bandeau_sec .gauche {
	margin-top: 0px;
	padding: 2px;
	padding-top: 0px;
	background-color: white;
	border-bottom: 1px solid black;
	border-left: 1px solid black;
	border-right: 1px solid black;
	-moz-border-radius-bottomleft: 5px;
	-moz-border-radius-bottomright: 5px;
	z-index: 100;
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
.bandeau_couleur {
	padding-right: 4px;
	padding-left: 4px;
	font-family: verdana, helvetica, arial, sans;
	font-size: 11px;
	color: black;
	text-align: center;
	font-weight: bold;
}

.bandeau_couleur_sous {
	position: absolute; 
	visibility: hidden;
	top: 0px; 
	background-color: <?php echo $couleur_claire; ?>; 
	color: black;
	padding: 5px;
	padding-top: 2px;
	font-family: verdana, helvetica, arial, sans;
	font-size: 11px;
	border-bottom: 1px solid white;
	border-right; 1px solid white;
	-moz-border-radius-bottomleft: 5px;
	-moz-border-radius-bottomright: 5px;
}

a.lien_sous {
	color: #666666;
}
a.lien_sous:hover {
	color: black;
}


div.bandeau_rubriques {
	background-color: #eeeeee; 
	border: 1px solid #555555;
}
a.bandeau_rub {
	display: block;
	font-size: 10px;
	padding: 2px;
	padding-<?php echo $right; ?>: 13px;
	padding-<?php echo $left; ?>: 16px;
	color: #666666;
	text-decoration: none;
	border-bottom: 1px solid #cccccc;
	background-repeat: no-repeat;
	background-position: <?php echo $ltr ? "1%" : "99%"; ?>;
}
a.bandeau_rub:hover {
	background-color: white;
	text-decoration: none;
	color: #333333;
	background-repeat: no-repeat;
	background-position: <?php echo $ltr ? "1%" : "99%"; ?>;
}
div.bandeau_rub {
	position: absolute;
	top: 4px;
	<?php echo $left; ?>: 120px;
	background-color: #eeeeee;
	padding: 0px;
	border: 1px solid #555555;
	visibility: hidden;
	width: 170px;
}

div.messages {
	padding: 5px;
	border-bottom: 1px solid <? echo $couleur_foncee; ?>;
	font-size: 10px;
	font-weight: bold;
}


/* Icones de fonctions */

a.icone26 {
	font-family: verdana, helvetica, arial, sans;
	font-size: 11px;
	font-weight: bold;
	color: black;
	text-decoration: none;
}
a.icone26:hover {
	text-decoration: none;
}
a.icone26 img {
	padding: 1px;
	margin-right: 2px;
	vertical-align: middle;
}
a.icone26:hover img {
	background: url(img_pack/fond-gris-anim.gif);
}


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
	padding: 4px;
	background-color: #eeeeee;
	border: 2px solid <?php echo $couleur_foncee; ?>;
	-moz-border-radius: 5px;
}
.icone36 a:hover img {
	margin: 0px; 
	display: inline;
	padding: 4px;
	background-color: white;
	border: 2px solid #666666;
	-moz-border-radius: 5px;
}
.icone36-danger a img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: white;
	border: 2px solid #ff9999;
	-moz-border-radius: 5px;
}
.icone36-danger a:hover img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: white;
	border: 2px solid red;
	-moz-border-radius: 5px;
}
.icone36-danger a span {
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color: red; display: block; margin: 2px;
	width: 100%
}
.icone36 a span {
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color: <?php echo $couleur_foncee; ?>; 
	display: block; 
	margin: 2px;
	width: 100%
}
.icone36 a:hover span {
	font-family: Verdana, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color: #000000; display: block; margin: 2px;
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


.cellule48 a img {
	behavior: url("win_png.htc");
	display: inline;
	margin: 4px;
	padding: 0px;
	border: 0px;
	background-color: <?php echo $couleur_claire; ?>;
}

.cellule48 a.selection img {
	display: inline;
	margin: 4px;
	padding: 0px;
	border: 0px;
	background-color: #999999;
}
.cellule48 a:hover img {
	display: inline;
	margin: 4px;
	padding: 0px;
	border: 0px;
	background: url(img_pack/fond-gris-anim.gif);
}


.cellule36 a img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	border: 0px;
	border: 1px solid white;
	-moz-border-radius: 5px;
}
.cellule36 a.selection img{
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: white;
	border: 1px solid #aaaaaa;
	-moz-border-radius: 5px;
}
.cellule36 a:hover img {
	margin: 0px; 
	display: inline;
	padding: 3px;
	background-color: #e4e4e4;
	background: url(img_pack/fond-gris-anim.gif);
	border: 1px solid <?php echo $couleur_foncee; ?>;
	-moz-border-radius: 5px;
}
.cellule36 a span, .cellule48 a span {
	color: #666666; display: block; margin: 1px;
	width: 100%;
}
.cellule36 a:hover span, .cellule48 a:hover span {
	color: #000000; display: block; margin: 1px;
	width: 100%;
}
.cellule36 a.selection span, .cellule48 a.selection span {
	color: #000000; display: block; margin: 1px;
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
	padding: 3px; margin: 1px; 
	border: 1px solid <?php echo $couleur_foncee; ?>; 
	background-color: <?php echo $couleur_claire; ?>;
	-moz-border-radius: 5px;
	color: #000000;
}
.cellule-texte a:hover {
	padding: 3px; margin: 1px; 
	border: 1px solid <?php echo $couleur_foncee; ?>; 
	background-color: white;
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
	padding: 0px;
	border: 1px solid white;
	-moz-border-radius: 5px;
	margin: 0px;
	margin-<?php echo $right; ?>: 3px;
}
a.cellule-h:hover div.cell-i {
	padding: 0px;
	border: 1px solid <?php echo $couleur_foncee; ?>;
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

a.cellule-h img {
	width: 24px;
	height: 24px;
	border: none;
	margin: 3px;
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
	font-family: Trebuchet Sans MS, Arial, Sans, sans-serif;
	font-weight: bold;
	font-size: 11px;
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
	border: 1px solid #aaaaaa;
	background-color: #eeeeee;
	-moz-border-radius: 5px;
}
.bouton36blanc {
	padding: 6px;
	margin-top: 2px;
	border: 1px solid #999999;
	background-color: white;
	-moz-border-radius: 5px;
}
.bouton36rouge {
	padding: 6px;
	margin-top: 2px;
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

div.onglet {
	font-family: Arial, Sans, sans-serif; 
	font-size: 11px;
	font-weight: bold; 
	border: 1px solid <?php echo $couleur_foncee; ?>;
	margin-right: 3px;
	padding: 5px;
	background-color: white;
}
div.onglet a {
	color: <?php echo $couleur_foncee; ?>;
}

div.onglet_on {
	font-family: Arial, Sans, sans-serif; 
	font-size: 11px;
	font-weight: bold; 
	border: 1px solid <?php echo $couleur_foncee; ?>;
	margin-right: 3px;
	padding: 5px;
	background-color: <?php echo $couleur_claire; ?>;
}
div.onglet_on a, div.onglet_on a:hover {
	color: <?php echo $couleur_foncee; ?>;
	text-decoration: none;
}

div.onglet_off {
	font-family: Arial, Sans, sans-serif; 
	font-size: 11px;
	font-weight: bold; 
	border: 1px solid <?php echo $couleur_foncee; ?>;
	margin-right: 3px;
	padding: 5px;
	background-color: <?php echo $couleur_foncee; ?>;
	color: white;
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
.arial11 { font-family: Arial, Sans, sans-serif; font-size: 11px; }
.arial2 { font-family: Arial, Sans, sans-serif; font-size: 12px; }
.verdana1 { font-family: Verdana, Arial, Sans, sans-serif; font-size: 10px; }
.verdana2 { font-family: Verdana, Arial, Sans, sans-serif; font-size: 11px; }
.verdana3 { font-family: Verdana, Arial, Sans, sans-serif; font-size: 13px; }
.serif { font-family: Georgia, Garamond, Times New Roman, serif; }
.serif2 { font-family: Georgia, Garamond, Times New Roman, serif; font-size: 13px; }

/* Liens hypertexte */
a { text-decoration: none; }
a:hover { text-decoration: none; }
a.icone { text-decoration: none; }
a.icone:hover { text-decoration: none; }

/*
 * Correction orthographique
 */

.ortho {
	background: #ffe0e0;
	margin: 0px;
	margin-bottom: -2px;
	border-bottom: 2px dashed red;
	color: inherit;
	text-decoration: none;
}
a.ortho:hover {
	margin: -2px;
	border: 2px dashed red;
	color: inherit;
	text-decoration: none;
}
.ortho-dico {
	background: #e0f4d0;
	margin: 0px;
	margin-bottom: -2px;
	border-bottom: 2px dashed #a0b890;
	color: inherit;
	text-decoration: none;
}
a.ortho-dico:hover {
	margin: -2px;
	border: 2px dashed #a0b890;
	color: inherit;
	text-decoration: none;
}

#ortho-fixed {
	position: fixed; top: 0px; right: 0px; width: 25%; padding: 15px; margin: 0px;
}
.ortho-content {
	position: absolute; top: 0px; width: 70%; padding: 15px; margin: 0px;
}
.suggest-actif, .suggest-inactif {
	font-family: "Trebuchet Sans MS", Verdana, Arial, sans-serif;
	font-size: 95%;
	font-weight: bold;
	margin: 8px;
	z-index: 1;
}
.suggest-actif .detail, .suggest-inactif .detail {
	margin: 8px;
	margin-top: -0.5em;
	padding: 0.5em;
	padding-top: 1em;
	border: 1px solid #c8c8c8;
	background: #f3f2f3;
	font-family: Georgia, Garamond, "Times New Roman", serif;
	font-weight: normal;
	z-index: 0;
}
.suggest-actif .detail ul, .suggest-inactif .detail ul {
	list-style-image: url(img_pack/puce.gif);
	background: #f3f2f3;
	margin: 0px;
	padding: 0px;
	padding-left: 25px;
}
.suggest-actif {
	display: block;
}
.suggest-inactif {
	display: none;
}
.form-ortho select {
	background: #ffe0e0;
}


/*
 * Comparaison d'articles
 */

.diff-para-deplace {
	background: #e8e8ff;
}
.diff-para-ajoute {
	background: #d0ffc0;
	color: #000000;
}
.diff-para-supprime {
	background: #ffd0c0;
	color: #904040;
	text-decoration: line-through;
}
.diff-deplace {
	background: #e8e8ff;
}
.diff-ajoute {
	background: #d0ffc0;
}
.diff-supprime {
	background: #ffd0c0;
	color: #802020;
	text-decoration: line-through;
}
.diff-para-deplace .diff-ajoute {
	border: 1px solid #808080;
	background: #b8ffb8;
}
.diff-para-deplace .diff-supprime {
	border: 1px solid #808080;
	background: #ffb8b8;
}
.diff-para-deplace .diff-deplace {
	border: 1px solid #808080;
	background: #b8b8ff;
}

/*
 * Barre de raccourcis
 */

table.spip_barre {
	border-<? echo $right; ?>: 1px solid <? echo $couleur_claire; ?>;
}

table.spip_barre td {
	text-align: <? echo $left; ?>;
	border-top: 1px solid <? echo $couleur_claire; ?>;
	border-<? echo $left; ?>: 1px solid <? echo $couleur_claire; ?>;
}

a.spip_barre img {
	padding: 3px;
	margin: 0px;
	background-color: #eeeeee;
	border-<? echo $right; ?>: 1px solid <? echo $couleur_claire; ?>;
}
a.spip_barre:hover img {
	background-color: white;
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

a.bouton_rotation img, div.bouton_rotation img {
	padding: 1px;
	margin-bottom: 1px;
	background-color: #eeeeee;
	border: 1px solid <? echo $couleur_claire; ?>;
}

a.bouton_rotation:hover img {
	border: 1px solid <? echo $couleur_foncee; ?>;
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
* Cadre couleur foncee
*/

.cadre-padding {
	font-family: verdana, arial, helvetica, sans;
	font-size: 12px;
	padding: 6px;
}

.cadre-titre {
	font-family: verdana, arial, helvetica, sans;
	font-weight: bold;
	font-size: 12px;
	padding: 3px;
}

.cadre-fonce {
	background-color: <?php echo $couleur_foncee; ?>;
	-moz-border-radius: 8px;
}

.cadre-gris-fonce {
	background-color: #666666;
	-moz-border-radius: 8px;
}

.cadre-gris-clair {
	border: 1px solid #aaaaaa;
	background-color: #cccccc;
	-moz-border-radius: 8px;
}

.cadre-couleur {
	background-color: <? echo $couleur_claire; ?>;
	-moz-border-radius: 8px;
}


.cadre-trait-couleur {
	background-color: white;
	border: 2px solid <? echo $couleur_foncee; ?>;
	-moz-border-radius: 8px;
}
.cadre-trait-couleur div.cadre-titre {
	background: <? echo $couleur_foncee; ?>;
	border-bottom: 2px solid <? echo $couleur_foncee; ?>;
	color: white;	
}

.cadre-r {
	background-color: white;
	border: 1px solid #666666;
	-moz-border-radius: 8px;
}


.cadre-r div.cadre-titre {
	background: #aaaaaa;
	border-bottom: 1px solid #666666;
	color: black;	
}

.cadre-e {
	background-color: #dddddd;
	border-top: 1px solid #aaaaaa;
	border-left: 1px solid #aaaaaa;
	border-bottom: 1px solid white;
	border-right: 1px solid white;
	-moz-border-radius: 8px;
}

.cadre-e div.cadre-titre {
	background: <? echo $couleur_claire; ?>;
	border-bottom: 1px solid #666666;
	color: black;	
}

.cadre-e-noir {
	border: 1px solid #666666;
	-moz-border-radius: 8px;
}

.cadre-info{
	background-color: white;
	border: 2px solid <?php echo $couleur_foncee; ?>;
	padding: 5px;
	-moz-border-radius: 8px;
}


.cadre-formulaire {
/*	border: 1px solid <?php echo $couleur_foncee; ?>;
	background-color: #dddddd;*/
	color: #444444;
	font-family: verdana, arial, helvetica, sans;
	font-size: 11px;
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
 
.plan-articles {
	border-top: 1px solid #cccccc;
	border-left: 1px solid #cccccc;
	border-right: 1px solid #cccccc;
}
.plan-articles a {
	display: block;
	padding: 2px;
	padding-<?php echo $left; ?>: 18px;
	border-bottom: 1px solid #cccccc;
	 background: <?php echo $ltr ? "1%" : "99%"; ?> no-repeat;
	background-color: #e0e0e0;
	font-family: Verdana, Arial, Sans, sans-serif;
	font-size: 11px;
	text-decoration: none;
}
.plan-articles a:hover {
	background-color: white; 
	text-decoration: none;
}
.plan-articles .publie {
	background-image: url(img_pack/puce-verte.gif);
}
.plan-articles .prepa {
	background-image: url(img_pack/puce-blanche.gif);
}
.plan-articles .prop {
	background-image: url(img_pack/puce-orange.gif);
}
.plan-articles .refuse {
	background-image: url(img_pack/puce-rouge.gif);
}
.plan-articles .poubelle {
	background-image: url(img_pack/puce-poubelle.gif);
}

a.foncee, a.foncee:hover, a.claire, a.claire:hover, span.creer, span.lang_base {
	display: inline;
	float: none;
	padding: 2px;
	margin: 0px;
	margin-left: 1px;
	margin-right: 1px;
	border: 0px;
	font-family: Arial, Helvetica, Sans, sans-serif;
	font-size: 9px;
	text-decoration: none;
	z-index: 1;

}
a.foncee, a.foncee:hover {
	background-color: <?php echo $couleur_foncee; ?>;
	color: white;
	border: 1px solid <?php echo $couleur_foncee; ?>;
}
a.claire, a.claire:hover {
	background-color: <?php echo $couleur_claire; ?>;
	color: <?php echo $couleur_foncee; ?>;
	border: 1px solid <?php echo $couleur_foncee; ?>;
}
span.lang_base {
	color: #666666;
	border: 1px solid #666666;
	background-color: #eeeeee;
}
span.creer {
	color: #333333;
	border: 1px solid #333333;
	background-color: white;
}
.trad_float {
	float: <?php echo $right; ?>;
	z-index: 20;
	margin-top: 4px;
}

div.liste {
	border: 1px solid #444444;
	margin-top: 3px; 
	margin-bottom: 3px;
}

a.liste-mot {
	background: url(img_pack/petite-cle.gif) <?php echo $left; ?> center no-repeat; 
	padding-<?php echo $left; ?>: 30px;
}

.tr_liste {
	background-color: #eeeeee;
}
.tr_liste_over, .tr_liste:hover {
	background-color: white;
}

.tr_liste td, .tr_liste:hover td, .tr_liste_over td {
	border-bottom: 1px solid #cccccc;
}

.tr_liste td div.liste_clip {
	height: 12px;
	overflow: hidden;
}

.tr_liste:hover td div.liste_clip {
	overflow: visible;
	height: 100%;
}


div.brouteur_rubrique {
	display: block;
	padding: 3px;
	padding-<?php echo $right; ?>: 10px;
	border-top: 0px solid <?php echo $couleur_foncee; ?>;
	border-bottom: 1px solid <?php echo $couleur_foncee; ?>;
	border-left: 1px solid <?php echo $couleur_foncee; ?>;
	border-right: 1px solid <?php echo $couleur_foncee; ?>;
	background: url(img_pack/triangle-droite<?php echo $_rtl; ?>.gif) <?php echo $right; ?> center no-repeat;
	background-color: white;
}

div.brouteur_rubrique_on {
	display: block;
	padding: 3px;
	padding-<?php echo $right; ?>: 10px;
	border-top: 0px solid <?php echo $couleur_foncee; ?>;
	border-bottom: 1px solid <?php echo $couleur_foncee; ?>;
	border-left: 1px solid <?php echo $couleur_foncee; ?>;
	border-right: 1px solid <?php echo $couleur_foncee; ?>;
	background: url(img_pack/triangle-droite<?php echo $_rtl; ?>.gif) <?php echo $right; ?> center no-repeat;
	background-color: #e0e0e0;
}

xdiv.brouteur_rubrique:hover {
	background-color: #e0e0e0;
}

div.brouteur_rubrique div, div.brouteur_rubrique_on div  {
	padding-top: 5px; 
	padding-bottom: 5px; 
	padding-<?php echo $left; ?>: 28px; 
	background-repeat: no-repeat;
	background-position: center <?php echo $left; ?>;
	font-weight: bold;
	font-family: Arial,Sans,sans-serif;
	font-size: 12px;
}

div.brouteur_rubrique div a {
	color: <?php echo $couleur_foncee; ?>;
}

div.brouteur_rubrique_on div a {
	color: black;
}

.iframe-bouteur {
	background-color: #eeeeee; 
	border: 0px;
	z-index: 1;
}

/*
 * Styles du calendrier
 */
 
div.navigation-calendrier {
	background-color: <?php echo $couleur_foncee; ?>;
	color: white;
	font-family: verdana, arial, sans,sans-serif;
	font-size: 14px;
	padding: 2px;
	-moz-border-radius-topleft: 8px;
	-moz-border-radius-topright: 8px;
}

div.navigation-calendrier img {
	border: 0px;
	vertical-align: middle;
	margin: 1px;
}

.navigation-bouton-desactive {
	-moz-opacity: 0.3;
	filter: alpha(opacity=30);
}

a.calendrier-annee {
	background-color: #aaaaaa;
	padding: 3px;
	margin: 1px;
	font-family: verdana, arial, sans,sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: white;
	-moz-border-radius: 5px;
}
a.calendrier-annee:hover {
	color: black;
	background-color: white;
}


/*
 * Styles generes par les raccourcis de mis en page
 */

p.spip {
	line-height: 140%;
}
p.spip_note {
	margin-bottom: 3px;
	margin-top: 3px;
	margin-<?php echo $left; ?>: 17px;
	text-indent: -17px;
}


a.spip_in  {
	background-color:#eeeeee;
	padding: 2px;	
}
a.spip_note {background-color:#eeeeee;}
a.spip_out {
	background: url(img_pack/spip_out.gif) <?php echo $right; ?> center no-repeat;
	padding-<?php echo $right; ?>: 10px;
}
a.spip_url {}
a.spip_glossaire:hover {text-decoration: underline overline;}

.spip_recherche {
	padding: 3px; 
	width : 100%; 
	font-size: 10px;
	border: 1px solid white;
	background-color: <?php echo $couleur_foncee; ?>;
	color: white;
}
.spip_cadre {
	width : 100%;
	background-color: #eeeeee;
	margin-top: 10px;
	padding: 5px;
	border: 1px solid #666666;
	behavior: url("win_width.htc");
}
blockquote.spip {
	margin-<?php echo $left; ?>: 40px;
	margin-<?php echo $right; ?>: 0px;
	margin-top : 10px;
	margin-bottom : 10px;
	border : solid 1px #aaaaaa;
	background-color: #ffffff;
	padding-left: 10px;
	padding-right: 10px;
}

div.spip_poesie {
	margin-<?php echo $left; ?>: 10px;
	padding-<?php echo $left; ?>: 10px;
	border-<?php echo $left; ?>: 1px solid #999999;
}
div.spip_poesie div {
	text-indent: -60px;
	margin-<?php echo $left; ?>: 60px;
}

.spip-nbsp {
	border-bottom: 2px solid #c8c8c8;
	padding-left: 2px;
	padding-right: 2px;
	margin-left: -1px;
	margin-right: -1px;
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
	font-size: 120%;
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

