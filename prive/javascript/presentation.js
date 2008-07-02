
// Un petit plugin jQuery pour ajouter une classe au survol d'un element
$.fn.hoverClass = function(c) {
	return this.each(function(){
		$(this).hover(
			function() { $(this).addClass(c); },
			function() { $(this).removeClass(c); }
		);
	});
};


var bandeau_elements = false;
var dir_page = $("html").attr("dir");

function getBiDiOffset(el) {
    var offset = el.offsetLeft;
    if(dir_page=="rtl")
      offset = (window.innerWidth || el.offsetParent.clientWidth)-(offset+el.offsetWidth);
    return offset;
}

function decaleSousMenu() {
  var sousMenu = $("div.bandeau_sec",this).css({visibility:'hidden',display:'block'});
  if(!sousMenu.length) return;
  var left;
  if($.browser.msie) {
    if(sousMenu.bgIframe) sousMenu.bgIframe();
    left = getBiDiOffset(sousMenu[0].parentNode) + getBiDiOffset($("#bandeau-principal div")[0]);
  } else left = getBiDiOffset(sousMenu[0]);
  if (left > 0) {
		var demilargeur = Math.floor( sousMenu[0].offsetWidth / 2 );
    var gauche = left - demilargeur
			+ Math.floor(largeur_icone / 2);
		if (gauche < 0) gauche = 0;
    sousMenu.css(dir_page=="rtl"?"right":"left",gauche+"px");
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

	$('#statutdecal'+type+id)
	.css('marginLeft', decal+'px')
	.removeClass('on');

	$.get(script, function(c) {
		if (!c)
			node.src = puce;
		else {
			r = window.open();
			r.document.write(c);
			r.document.close();
		}
	});
}

function prepare_selec_statut(nom, type, id, action)
{
	$('#' + nom + type + id)
	.hoverClass('on')
	.addClass('on')
	.load(action + '&type='+type+'&id='+id);
}

function changeclass(objet, myClass) {
	objet.className = myClass;
}


function hauteurFrame(nbCol) {
	hauteur = $(window).height() - 40;
	hauteur = hauteur - $('#haut-page').height();
	
	if (findObj('brouteur_hierarchie'))
		hauteur = hauteur - $('#brouteur_hierarchie').height();

	for (i=0; i<nbCol; i++) {
		$('#iframe' + i)
		.height(hauteur + 'px');
	}
}

function changeVisible(input, id, select, nonselect) {
	if (input) {
		element = findObj_forcer(id);
		if (element.style.display != select)  element.style.display = select;
	} else {
		element = findObj_forcer(id);
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
