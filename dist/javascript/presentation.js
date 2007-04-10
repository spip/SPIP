var bandeau_elements = false;

function decaleSousMenu() {
  var sousMenu = $("div.bandeau_sec",this).css({visibility:'hidden',display:'block'});
  if(!sousMenu.length) return;
  var left;
  if($.browser.msie) {
    var version = navigator.appVersion.match(/MSIE ([^;]+)/);
    version = parseInt(version[1]);
    left = parseInt(sousMenu[0].parentNode.offsetLeft);
    if(version>6) {
      left += parseInt($("#bandeau-principal div")[0].offsetLeft);
    }
  } else left = parseInt(sousMenu[0].offsetLeft);
  if (left > 0) {
		var demilargeur = Math.floor( sousMenu[0].offsetWidth / 2 );
		var gauche = left
			- demilargeur
			+ Math.floor(largeur_icone / 2);
		if (gauche < 0) gauche = 0;
    sousMenu.css("left",gauche+"px");
	}
	sousMenu.css({display:'',visibility:''});
}

function changestyle(id_couche, element, style) {

	// La premiere fois, regler l'emplacement des sous-menus
	if (!bandeau_elements) {
		bandeau_elements = $('#haut-page div.bandeau');
	}

	// Masquer les elements du bandeau
	var select = $(bandeau_elements).not('#'+id_couche);
	// sauf eventuellement la boite de recherche si la souris passe en-dessous
	if (id_couche=='garder-recherche') select.not('#bandeaurecherche');
		select.css({'visibility':'hidden', 'display':'none'});
	// Afficher, le cas echeant, celui qui est demande
	if (element)
		$('#'+id_couche).css({element:style});
	else
		$('#'+id_couche).css({'visibility':'visible', 'display':'block'});
}

var accepter_change_statut = false;

function selec_statut(id, type, decal, puce, script) {

	node = findObj('imgstatut'+type+id);

	if (!accepter_change_statut)
		accepter_change_statut = confirm(confirm_changer_statut);

	if (!accepter_change_statut || !node) return;

	bloc = 'statutdecal'+type+id;
	changestyle (bloc, 'marginLeft', decal+'px');
	cacher (bloc);

	$.get(script, function (c) {if (!c)node.src = puce; else {
				      r = window.open();
				      r.document.write(c);
				      r.document.close();}})
}

function prepare_selec_statut(nom, type, id, action)
{
	$('#' + nom + type + id).load(action + '&type='+type+'&id='+id,
		function(){ 
			findObj_forcer('statutdecal'+type+id).style.visibility = 'visible';
					  });
}

function changeclass(objet, myClass) {
	objet.className = myClass;
}
function changesurvol(iddiv, myClass) {
	document.getElementById(iddiv).className = myClass;
}

function setvisibility (objet, statut) {
	element = findObj(objet);
	if (element.style.visibility != statut) element.style.visibility = statut;
}

function montrer(objet) {
	setvisibility(objet, 'visible');
}
function cacher(objet) {
	setvisibility(objet, 'hidden');
}


function getHeight(obj) {
	if (obj == "window") {
		return hauteur_fenetre();
	}
	else
	{
		obj = document.getElementById(obj);
		if (obj.offsetHeight) return obj.offsetHeight;
	}
}
function hauteur_fenetre() {
	var myWidth = 0, myHeight = 0;
	if( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE
		myHeight = window.innerHeight;
	} else {
		if( document.documentElement &&
			( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
			//IE 6+ in 'standards compliant mode'
			myHeight = document.documentElement.clientHeight;
		} else {
			if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
				//IE 4 compatible
				myHeight = document.body.clientHeight;
			}
		}
	}
	return myHeight;
}


function hauteurFrame(nbCol) {
	hauteur = hauteur_fenetre() - 40;
	hauteur = hauteur - getHeight('haut-page');
	
	if (findObj('brouteur_hierarchie')) hauteur = hauteur - getHeight('brouteur_hierarchie');
		
	for (i=0; i<nbCol; i++) {
		source = document.getElementById("iframe" + i);
		source.style.height = hauteur + 'px';
	}
}

function hauteurTextarea() {
	hauteur = hauteur_fenetre() - 80;
	
	source = document.getElementById("text_area");
	source.style.height = hauteur + 'px';
}

function changeVisible(input, id, select, nonselect) {
	if (input) {
		element = findObj(id);
		if (element.style.display != select)  element.style.display = select;
	} else {
		element = findObj(id);
		if (element.style.display != nonselect)  element.style.display = nonselect;
	}
}



// livesearchlike...



// effacement titre quand new=oui
var antifocus=false;
// effacement titre des groupes de mots-cles de plus de 50 mots
var antifocus_mots = new Array();

function puce_statut(selection){
	if (selection=="publie"){
		return "puce-verte.gif";
	}
	if (selection=="prepa"){
		return "puce-blanche.gif";
	}
	if (selection=="prop"){
		return "puce-orange.gif";
	}
	if (selection=="refuse"){
		return "puce-rouge.gif";
	}
	if (selection=="poubelle"){
		return "puce-poubelle.gif";
	}
}
