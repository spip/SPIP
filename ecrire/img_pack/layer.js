var memo_obj = new Array();
var url_chargee = new Array();
var xmlhttp = new Array();
var image_search = new Array();


function findObj_test_forcer(n, forcer) { 
	var p,i,x;

	// Voir si on n'a pas deja memoriser cet element
	if (memo_obj[n] && !forcer) {
		return memo_obj[n];
	}

	var d = document; 
	if((p = n.indexOf("?"))>0 && parent.frames.length) {
		d = parent.frames[n.substring(p+1)].document; 
		n = n.substring(0,p);
	}
	if(!(x = d[n]) && d.all) {
		x = d.all[n]; 
	}
	for (i = 0; !x && i<d.forms.length; i++) {
		x = d.forms[i][n];
	}
	for(i=0; !x && d.layers && i<d.layers.length; i++) x = findObj(n,d.layers[i].document);
	if(!x && document.getElementById) x = document.getElementById(n); 

	// Memoriser l'element
	if (!forcer) memo_obj[n] = x;
	return x;
}

function findObj(n) { 
	return findObj_test_forcer(n, false);
}
// findObj sans memorisation de l'objet - avec Ajax, les elements se deplacent dans DOM
function findObj_forcer(n) { 
	return findObj_test_forcer(n, true);
}

function hide_obj(obj) {
	var element;
	if (element = findObj(obj)){
		if (element.style.visibility != "hidden") element.style.visibility = "hidden";
	}
}

function swap_couche(couche, rtl, dir, no_swap) {
	var layer;
	var triangle = findObj('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (layer.style.display == "none"){
		if (!no_swap && triangle) triangle.src = dir + 'deplierbas.gif';
		layer.style.display = 'block';
	} else {
		if (!no_swap && triangle) triangle.src = dir + 'deplierhaut' + rtl + '.gif';
		layer.style.display = 'none';
	}
}
function ouvrir_couche(couche, rtl,dir) {
	var layer;
	var triangle = findObj('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (triangle) triangle.src = dir + 'deplierbas.gif';
	layer.style.display = 'block';
}
function fermer_couche(couche, rtl, dir) {
	var layer;
	var triangle = findObj('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (triangle) triangle.src = dir + 'deplierhaut' + rtl + '.gif';
	layer.style.display = 'none';
}
function manipuler_couches(action,rtl,first,last, dir) {
	if (action=='ouvrir') {
		for (j=first; j<=last; j+=1) {
			ouvrir_couche(j,rtl, dir);
		}
	} else {
		for (j=first; j<=last; j+=1) {
			fermer_couche(j,rtl, dir);
		}
	}
}

//
// Fonctions pour mini_nav
//

function slide_horizontal (couche, slide, align, depart, etape ) {
	var obj = findObj_forcer(couche);
	if (!obj) return;
	if (!etape) {
		if (align == 'left') depart = obj.scrollLeft;
		else depart = obj.firstChild.offsetWidth - obj.scrollLeft;
		etape = 0;
	}
	etape = Math.round(etape) + 1;
	pos = Math.round(depart) + Math.round(((slide - depart) / 10) * etape);

	if (align == 'left') obj.scrollLeft = pos;
	else obj.scrollLeft = obj.firstChild.offsetWidth - pos;
	if (etape < 10) setTimeout("slide_horizontal('"+couche+"', '"+slide+"', '"+align+"', '"+depart+"', '"+etape+"')", 60);
	//else obj.scrollLeft = slide;
}

function changerhighlight (couche) {
	var kids = couche.parentNode.childNodes;
	for (var i = 0; i < kids.length; i++) {
		kids[i].className = "pashighlight";
	}
	couche.className = "highlight";
}

function aff_selection (type, rac, id) {
//	alert (type + " - " + rac + " - " + id);
	
	findObj_forcer(rac+"_selection").style.display = "none";
	
	charger_id_url("./?exec=informer&var_ajax=1&type="+type+"&id="+id+"&rac="+rac, rac+"_selection");
}

//
// Cette fonction charge du contenu - dynamiquement - dans un 
// Ajax

function createXmlHttp() {
	if(window.XMLHttpRequest)
		return new XMLHttpRequest(); 
	else if(window.ActiveXObject)
		return new ActiveXObject("Microsoft.XMLHTTP");
}

function ajah(method, url, flux, rappel)
{
	var xhr = createXmlHttp();
	if (!xhr) return false;
        xhr.onreadystatechange = function () {ajahReady(xhr, rappel);}
        xhr.open(method, url, true);
	// Necessaire au mode POST
	// Il manque la specification du charset
	if (flux) {
		xhr.setRequestHeader("Content-Type",
		       "application/x-www-form-urlencoded; ");
	}
	xhr.send(flux);
	return true;
}

function ajahReady(xhr, f) {
	if (xhr.readyState == 4) {
		if (xhr.status > 200) // Opera dit toujours 0 !
                      {f('Erreur HTTP :  ' +  xhr.status);}
                else  { f(xhr.responseText); }
        }
}

// Si Ajax est disponible, cette fonction envoie la requete en Ajax.
// Si le premier argument n'est pas une url, ce doit etre un formulaire.
// Le deuxieme argument doit etre l'ID du noeud a affecter avec la reponse.
// En cas de formulaire, la fonction retourne False pour empecher son envoi
// Le cas True ne devrait pas se produire car le cookie spip_accepte_ajax
// a du anticiper la situation.
// Toutefois il y toujours un coup de retard dans la pose d'un cookie:
// eviter de se loger avec redirection vers un telle page

function AjaxSqueeze(trig, id)
{
	var i, s, g;
	var u = '';
	
	// pere du demandeur dans le DOM (le donner direct serait mieux)
	var noeud = document.getElementById(id);
	if (!noeud) return true;

	// vivement jquery !
	if (typeof ajax_image_searching != 'undefined') {
		g = document.createElement('div');
		g.innerHTML = ajax_image_searching;
		noeud.insertBefore(g, noeud.firstChild);
	}

	if (typeof(trig) == 'string') {
		return ajah('GET', trig, null, function(r) { noeud.innerHTML = r;});
	}

	for (i=0;i < trig.elements.length;i++) {
		n = trig.elements[i];
		s = (n.type != 'checkbox') ? n.name : n.checked;
		if (s) {
			u += n.name+"="+ encodeURIComponent(n.value) + '&';
		}
	}

	s = trig.getAttribute('action');
	return !ajah('POST', // ou 'GET'
		     s ,     // s + '?'+ u,
		     u,      // null,
		     function(r) { noeud.innerHTML = r;} );
}


function charger_id_url(myUrl, myField, jjscript) 
{
	var Field = findObj_forcer(myField); // selects the given element
	if (!Field) return;

	if (xmlhttp[myField]) xmlhttp[myField].abort();

	if (url_chargee['mem_'+myUrl]) {
		Field.innerHTML = url_chargee['mem_'+myUrl];
		Field.style.visibility = "visible";
		Field.style.display = "block";
		if(jjscript) eval(jjscript);
	} else {
		image_search[myField] = findObj_forcer('img_'+myField);
		if (image_search[myField]) image_search[myField].style.visibility = "visible";


		if (!(xmlhttp[myField] = createXmlHttp())) return false;
		xmlhttp[myField].open("GET", myUrl, true);
		// traiter la reponse du serveur
		xmlhttp[myField].onreadystatechange = function() {
			if (xmlhttp[myField].readyState == 4) { 
				// si elle est non vide, l'afficher
				if (xmlhttp[myField].responseText != '') {
					Field.innerHTML = xmlhttp[myField].responseText;
					url_chargee['mem_'+myUrl] = Field.innerHTML;
				
					Field.style.visibility = "visible";
					Field.style.display = "block";
					if (image_search[myField]) {
						image_search[myField].style.visibility = "hidden";
					}
					if(jjscript) eval(jjscript);
				} else {
					charger_id_url(myUrl, myField, jjscript);
				}
			}
		}
		xmlhttp[myField].send(null); 
	}
}

// ne sert que pour selecteur_rubrique_ajax() dans inc/chercher_rubrique.php
function charger_id_url_si_vide (myUrl, myField, jjscript) {
	var Field = findObj_forcer(myField); // selects the given element
	if (!Field) return;

	if (Field.innerHTML == "") {
		charger_id_url(myUrl, myField, jjscript) 
	}
	else {
		Field.style.visibility = "visible";
		Field.style.display = "block";
	}
}

