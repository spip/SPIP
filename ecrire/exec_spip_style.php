<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// En-tetes

#if (http_last_modified(@filemtime("spip_style" . _EXTENSION_PHP), time() + 24 * 3600)) 
#	exit;

function spip_style_dist() {

  global $couleur_claire,$couleur_foncee,$left;

// mettre absolument le charset :
// Apache-AdvancedExtranetServer & FireFox s'entendent mal sinon
	Header ("Content-Type: text/css; charset=iso-8859-1");

	// parano XSS
	eregi("^([#0-9a-z]*).*-([#0-9a-z]*).*-([0-9a-z]*).*", "$couleur_claire-$couleur_foncee-$left", $regs);
	list (,$couleur_claire,$couleur_foncee,$left) = $regs;

	// Sommes-nous en rtl ou ltr ?
	$ltr = ($left == 'left');
	if ($ltr) {
		$left = 'left';
		$right = 'right';
		$_rtl = '';
	} else {
		$left = 'right';
		$right = 'left';
		$_rtl = '_rtl';
	}

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
 	font-size: 12px;
	font-family: Verdana,Arial,Sans,sans-serif; 
}
.formo { 
	width: 100%; 
	display: block;
	padding: 3px; 
	background-color: white; 
	border: 1px solid <?php echo $couleur_claire; ?>; 
	background-position: center bottom; float: none; 
	behavior: url("win_width.htc");
 	font-size: 12px;
	font-family: Verdana,Arial,Sans,sans-serif; 
}
.fondl { 
	padding: 3px; 
	background-color: #e4e4e4; 
	border: 1px solid <?php echo $couleur_claire; ?>; 
	background-position: center bottom; 
	float: none;
 	font-size: 11px;
	font-family: Verdana,Arial,Sans,sans-serif; 
}
.fondo { background-color: <?php echo $couleur_foncee; ?>; 
	background-position: center bottom; float: none; color: #FFFFFF;
 	font-size: 11px;
	font-family: Verdana,Arial,Sans,sans-serif; 
	font-weight: bold;
}
.fondf { background-color: #FFFFFF; border-style: solid ; border-width: 1px; border-color: #E86519; color: #E86519; 
}


select.fondl {
	padding: 0px;
}
.maj-debut:first-letter { text-transform: uppercase; }


.format_png {
	behavior: url("<?php echo _DIR_RACINE; ?>win_png.htc");
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
	vertical-align: middle;
	height: 100%;
	width: 11px;
	padding: 0px;
	margin: 0px;
	background: url(<?php echo _DIR_IMG_PACK . 'tirets-separation.gif' ?>);
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
	height: 22px;
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
	border-right: 1px solid white;
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
	background-position: <?php echo $ltr ? "1%" : "99%"; ?> center;
	background-image: url(<?php echo _DIR_IMG_PACK . 'rubrique-12.gif' ?>);
}
a.bandeau_rub:hover {
	background-color: white;
	text-decoration: none;
	color: #333333;
	background-repeat: no-repeat;
	background-position: <?php echo $ltr ? "1%" : "99%"; ?> center;
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

div.brt {
	background: url(<?php echo _DIR_IMG_PACK . 'triangle-droite' . $_rtl .'.gif', ')', $right; ?> center no-repeat;
}
div.pos_r {
	position: relative;
}

option.selec_rub {
	background-position: <?php echo $left; ?> center;
	background-image: url(<?php echo _DIR_IMG_PACK . 'rubrique-12.gif' ?>);
	background-repeat: no-repeat;
	padding-<?php echo $left; ?>: 16px;
}


div.messages {
	padding: 5px;
	border-bottom: 1px solid <?php echo $couleur_foncee; ?>;
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
	padding: 1px; 
	margin-<?php echo $right; ?>: 2px;
}
a.icone26:hover {
	text-decoration: none;
}
a.icone26 img {
	vertical-align: middle;
	behavior: url("<?php echo _DIR_RACINE; ?>win_png.htc");
	background-color: <?php echo $couleur_foncee; ?>;
}
a.icone26:hover img {
	background: url(<?php echo _DIR_IMG_PACK . 'fond-gris-anim.gif' ?>);
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
	behavior: url("<?php echo _DIR_RACINE; ?>win_png.htc");
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
	background: url(<?php echo _DIR_IMG_PACK . 'fond-gris-anim.gif' ?>);
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
	background: url(<?php echo _DIR_IMG_PACK . 'fond-gris-anim.gif' ?>);
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
	background: url(<?php echo _DIR_IMG_PACK . 'fond-gris-anim.gif' ?>);
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
	background: url(<?php echo _DIR_IMG_PACK . 'rayures-sup.gif' ?>);
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
.fondgrison {
	cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: #e4e4e4; 
}
.fondgrison2 {
	cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: white;
}
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



.reliefblanc {
	 background-image: url(<?php echo _DIR_IMG_PACK . 'barre-blanc.gif' ?>);
}
.reliefgris { 
	 background-image: url(<?php echo _DIR_IMG_PACK . 'barre-noir.gif' ?>);
}
.iconeoff {
	padding: 3px; margin: 1px; border: 1px dashed #aaaaaa; background-color: #f0f0f0;
}
.iconeon {
	cursor: pointer; padding: 3px; margin: 1px;  border-right: solid 1px white; border-bottom: solid 1px white; border-left: solid 1px #666666; border-top: solid 1px #666666; background-color: #eeeeee;
}
.iconedanger { padding: 3px; margin: 1px; border: 1px dashed black;
	background: url(<?php echo _DIR_IMG_PACK . 'rayures-sup.gif' ?>);
}

/* Raccourcis pour les polices (utile pour les tableaux) */
.arial0 { font-family: Arial, Sans, sans-serif; font-size: 9px; }
.arial1 { font-family: Arial, Sans, sans-serif; font-size: 10px; }
.arial11 { font-family: Arial, Sans, sans-serif; font-size: 11px; }
.arial2 { font-family: Arial, Sans, sans-serif; font-size: 12px; }
.verdana1 { font-family: Verdana, Arial, Sans, sans-serif; font-size: 10px; }
.verdana2 { font-family: Verdana, Arial, Sans, sans-serif; font-size: 11px; }
.verdana3 { font-family: Verdana, Arial, Sans, sans-serif; font-size: 13px; }
.serif { font-family: Georgia, Garamond, Times New Roman, serif; }
.serif1 { font-family: Georgia, Garamond, Times New Roman, serif; font-size: 11px; }
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
	position: fixed; top: 0px; <?php echo $right; ?>: 0px; width: 25%; padding: 15px; margin: 0px;
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
	 list-style-image: url(<?php echo _DIR_IMG_PACK . 'puce.gif' ?>);
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
	border-<?php echo $right; ?>: 1px solid <?php echo $couleur_claire; ?>;
}

table.spip_barre td {
	text-align: <?php echo $left; ?>;
	border-top: 1px solid <?php echo $couleur_claire; ?>;
	border-<?php echo $left; ?>: 1px solid <?php echo $couleur_claire; ?>;
}

a.spip_barre img {
	padding: 3px;
	margin: 0px;
	background-color: #eeeeee;
	border-<?php echo $right; ?>: 1px solid <?php echo $couleur_claire; ?>;
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
	border: 1px solid <?php echo $couleur_claire; ?>;
}

a.bouton_rotation:hover img {
	border: 1px solid <?php echo $couleur_foncee; ?>;
}


/*
* Cadre couleur foncee
*/

.cadre-padding {
	font-family: verdana, arial, helvetica, sans;
	font-size: 12px;
	padding: 6px;
	position: relative;
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
	background-color: <?php echo $couleur_claire; ?>;
	-moz-border-radius: 8px;
}
.cadre-couleur div.cadre-titre {
	-moz-border-radius-topleft: 8px;
	-moz-border-radius-topright: 8px;
	background: <?php echo $couleur_foncee; ?>;
	border-bottom: 2px solid <?php echo $couleur_foncee; ?>;
	color: white;	
}

.cadre-couleur-foncee {
	background-color: <?php echo $couleur_foncee; ?>;
	-moz-border-radius: 8px;
}
.cadre-couleur-foncee div.cadre-titre {
	color: white;	
}



.cadre-trait-couleur {
	background-color: white;
	border: 2px solid <?php echo $couleur_foncee; ?>;
	-moz-border-radius: 8px;
}
.cadre-trait-couleur div.cadre-titre {
	background: <?php echo $couleur_foncee; ?>;
	border-bottom: 2px solid <?php echo $couleur_foncee; ?>;
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
	background: <?php echo $couleur_claire; ?>;
	border-bottom: 1px solid #666666;
	color: black;	
}

.cadre-e-noir {
	border: 1px solid #666666;
	-moz-border-radius: 8px;
}

.cadre-forum {
	background-color: white;
	border: 1px solid #aaaaaa;
	-moz-border-radius-top<?php echo $left; ?>: 8px;
}
.cadre-forum div.cadre-titre {
	background: <?php echo $couleur_claire; ?>;
	border-bottom: 1px solid #aaaaaa;
	color: black;	
}

.cadre-sous_rub {
	background-color: white;
	border: 1px solid #666666;
	-moz-border-radius-bottomleft: 8px;
	-moz-border-radius-bottomright: 8px;
	-moz-border-radius-top<?php echo $left; ?>: 8px;
}


.cadre-thread-forum {
	background-color: #eeeeee;
	border: 1px solid #cccccc;
	border-top: 0px;
}
.cadre-thread-forum div.cadre-titre {
	background: #cccccc;
	color: black;	
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
	 background-image: url(<?php echo _DIR_IMG_PACK . 'puce-verte.gif' ?>);
}
.plan-articles .prepa {
	 background-image: url(<?php echo _DIR_IMG_PACK . 'puce-blanche.gif' ?>);
}
.plan-articles .prop {
	 background-image: url(<?php echo _DIR_IMG_PACK . 'puce-orange.gif' ?>);
}
.plan-articles .refuse {
	 background-image: url(<?php echo _DIR_IMG_PACK . 'puce-rouge.gif' ?>);
}
.plan-articles .poubelle {
	 background-image: url(<?php echo _DIR_IMG_PACK . 'puce-poubelle.gif' ?>);
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
	background: url(<?php echo _DIR_IMG_PACK . 'petite-cle.gif', ')', $left; ?> center no-repeat; 
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

div.puce_article {
	position: relative; 
	height: 11px; 
	width: 11px;
}

div.puce_breve {
	position: relative; 
	height: 9px; 
	width: 9px;
}
div.puce_article_fixe, div.puce_breve_fixe {
	position: absolute;
}

div.puce_article_popup, div.puce_breve_popup {
	position: absolute;
	visibility: hidden;
	margin-top: -1px; top: 0px; 
	border: 1px solid #666666; 
	background-color: 
	#cccccc; z-index: 10; 
	-moz-border-radius: 3px;
}
div.puce_article_popup img, div.puce_breve_popup img {
	padding: 1px;
	border: 0px;
}

div.puce_article_popup {
	width: 55px; 
}
div.puce_breve_popup {
	width: 27px; 
}



div.brouteur_rubrique {
	display: block;
	padding: 3px;
	padding-<?php echo $right; ?>: 10px;
	border-top: 0px solid <?php echo $couleur_foncee; ?>;
	border-bottom: 1px solid <?php echo $couleur_foncee; ?>;
	border-left: 1px solid <?php echo $couleur_foncee; ?>;
	border-right: 1px solid <?php echo $couleur_foncee; ?>;
	background: url(<?php echo _DIR_IMG_PACK . 'triangle-droite'. $_rtl . '.gif', ')', $right; ?> center no-repeat;
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
	background: url(<?php echo _DIR_IMG_PACK . 'triangle-droite' . $_rtl . '.gif', ')', $right; ?> center no-repeat;
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


a.spip_in {
	border-bottom: 1px dashed;
}
a.spip_out {
	background: url(<?php echo _DIR_IMG_PACK . 'spip_out.gif', ') ', $right; ?> center no-repeat;
	padding-<?php echo $right; ?>: 10px;
	border-bottom: 1px solid;
}
a.spip_url {
}

.spip_code {
	font-family: monospace;
}

a.spip_note {
	background-color:#eeeeee;
}
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
a.boutonlien:hover {
	color:#454545; text-decoration: none;
}
a.boutonlien {
	color:#808080; text-decoration: none;
}

a.triangle_block {
	margin-top: -3px;
	margin-bottom: -3px;
	margin-<?php echo $right; ?>: -3px;
}
a.triangle_block:hover {
	margin-<?php echo $left; ?>: 1px;
	margin-<?php echo $right; ?>: -4px;
}

h3.spip {
	margin-top : 40px;
	margin-bottom : 40px;
	font-family: Verdana,Arial,Sans,sans-serif;
	font-weight: bold;
	font-size: 120%;
	text-align: center;
	clear: both;
}
.spip_documents{
	font-family: Verdana,Arial,Sans,sans-serif;
	font-size : 70%;
}
.spip_documents_center{
	margin: auto; 
	text-align: center;
	width: 80%
}

.spip_documents_left{
	margin: 0px; 
	margin-right: 20px;
	margin-bottom: 5px;
	text-align: center;
}

.spip_documents_right{
	margin: 0px; 
	margin-left: 20px;
	margin-bottom: 5px;
	text-align: center;
}

table.spip {
	margin-left: auto;
	margin-right: auto;
	border: 1px solid black;
	border-collapse:collapse;
}

table.spip caption{
	caption-side: top; /* bottom pas pris en compte par IE */
	text-align: center;
	margin-left: auto;                                                       
	margin-right: auto;
	padding: 3px;
	font-weight: bold;
	font-family: Verdana,Arial,Sans,sans-serif;
	font-size : 70%;
}

table.spip tr.row_first {
	background-color: <?php echo $couleur_claire; ?>;
}
table.spip tr.row_odd {
	background-color: #d0d0d0;
}
table.spip tr.row_even {
	background-color: #F0F0F0;
}
table.spip td {
	padding: 3px;
	text-align: left;
	vertical-align: middle;
	margin: 1px;
}

.rss-button {
	border: 1px solid;
	border-color: #FC9 #630 #330 #F96;
	padding: 0 3px;
	font: bold 10px verdana,sans-serif;
	color: #FFF;
	background: #F60;
	text-decoration: none;
	margin: 0;
}

.fond-agenda {
	background: url<?php echo '(', _DIR_IMG_PACK . 'fond-agenda.gif', ') ', $right; ?> center no-repeat;
	float: <?php echo $left; ?>; 
	margin-<?php echo $right; ?>: 3px;
	padding-<?php echo $right; ?>: 4px;
	line-height: 12px;
	color: #666666; 
 }

 div.highlight {
  	color: black;
 	background-color: <?php echo $couleur_claire; ?>;
 }
  div.highlight, div.pashighlight {
  	color: #666666;
  	padding: 2px;
  }
  div.highlight:hover, div.pashighlight:hover {
  	color: black;
  	cursor: pointer;
  }

div.petite-rubrique {
	background: <?php echo $ltr ? "1%" : "99%"; ?> no-repeat;
	background-image : url(<?php echo _DIR_IMG_PACK . 'rubrique-12.gif'; ?>);
	padding-<?php echo $left; ?>: 15px;
}
div.rub-ouverte {
	padding-<?php echo $right; ?>: 10px;
	background: url(<?php echo _DIR_IMG_PACK . 'triangle-droite'. $_rtl . '.gif', ')', $right; ?> center no-repeat;
 }
	<?php }
?>
