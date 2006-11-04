var memo_obj = new Array();
var url_chargee = new Array();
var xhr_actifs = new Array();

function findObj_test_forcer(n, forcer) { 
	var p,i,x;

	// Voir si on n'a pas deja memorise cet element
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

function aff_selection (arg, idom, url)
{

	noeud = findObj_forcer(idom);
	if (noeud) {
		noeud.style.display = "none";
		charger_node_url(url+arg, noeud);
	}
	return false;
}

// selecteur de rubrique et affichage de son titre dans le bandeau

function aff_selection_titre(titre, id, idom, nid)
{
	t = findObj_forcer('titreparent');
	t.value= titre;
	t=findObj_forcer(nid);
	t.value=id;
	t=findObj_forcer(idom);
	t.style.display='none';
}

function aff_selection_provisoire(id, racine, url, col, sens,informer)
{
    charger_id_url(url.href,
		   racine + '_col_' + (col+1),
		   function() {
		     slide_horizontal(racine + 'principal', ((col-1)*150), sens);
		     aff_selection (id, racine + "_selection", informer);
		   }
		   );
  // empecher le chargement non Ajax
  return false;
}

// Lanche une requete Ajax a chaque frappe au clavier dans une balise de saisie.
// Si l'entree redevient vide, rappeler l'URL initiale si dispo.
// Sinon, controler au retour si le resultat est unique, 
// auquel cas forcer la selection.

function onkey_rechercher(valeur, rac, url, img, nid, init) {
	var Field = findObj_forcer(rac);
	if (!valeur.length) {	
		init = findObj_forcer(init);
		if (init && init.href) { charger_node_url(init.href, Field);}
	} else {	
	  charger_node_url(url+valeur,
			 Field,
			 function () {
			   	var n = Field.childNodes.length - 1;
				// Safari = 0  & Firefox  = 1 !
				// et gare aux negatifs en cas d'abort
				if ((n == 1)) {
				  noeud = Field.childNodes[n].firstChild;
				  if (noeud.title)
				    // cas de la rubrique, pas des auteurs
					  aff_selection_titre(noeud.firstChild.nodeValue, noeud.title, rac, nid);
				}
			   },
			   img);
	}
	return false;
}

function lancer_recherche(champ, cible) {} // obsolete

//
// Cette fonction charge du contenu - dynamiquement - dans un 
// Ajax

function createXmlHttp() {
	if(window.XMLHttpRequest)
		return new XMLHttpRequest(); 
	else if(window.ActiveXObject)
		return new ActiveXObject("Microsoft.XMLHTTP");
}

function verifForm(racine) {
	if(!jQuery.browser.mozilla) return;
  racine = racine || document;
  $("input.forml,input.formo,textarea.forml,textarea.formo",racine)
  .each(function(){
  	var jField = $(this);
    var w = jField.width();
    if(!w) {
      jField.width("95%");
    } else {
      w -= (parseInt(jField.css("borderLeftWidth"))+parseInt(jField.css("borderRightWidth"))+
    	parseInt(jField.css("paddingLeft"))+parseInt(jField.css("paddingRight")));
    	jField.width(w+"px");
    }
  });
}

// Alloue un gestionnaire Ajax, et le lance sur les parametres donnes
// Retourne False si l'allocation est impossible,
// le gestionnaire lui-meme autrement (utile pour memoriser ceux alloues).

function ajah(method, url, flux, rappel)
{
	var xhr = createXmlHttp();
	if (!xhr) return false;
        xhr.onreadystatechange = function () {ajahReady(xhr, rappel);}
        xhr.open(method, url, true);
	// Necessaire au mode POST
	// Il manque la specification du charset
	// mais le mode_security d'Apache n'admet rien de plus
	if (flux) {
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	}
	xhr.send(flux);
	return xhr;
}

// Avancees de la requete HTTP.
// Attention:
// 1. Opera dit toujours 0, pas 200 ou 404 etc
// 2. si la methode abort a ete invoquee, acceder au status provoque
// 	NS_ERROR_NOT_AVAILABLE 
// il faut try/catch pour rester discret (l'avortement decidement ...)

function ajahReady(xhr, f) {
	if (xhr.readyState == 4) {
	  try { s = xhr.status > 200} catch (e) {s = 0; }
	  if (s) // 
	    {f('Erreur HTTP :  ' +  xhr.status);}
	  else  { f(xhr.responseText); }
        }
}

// Si Ajax est disponible, cette fonction l'utilise pour envoyer la requete.
// Si le premier argument n'est pas une url, ce doit etre un formulaire.
// Le deuxieme argument doit etre l'ID d'un noeud qu'on animera pendant Ajax.
// Le troisieme, optionnel, est la fonction traitant la réponse.
// La fonction par defaut affecte le noeud ci-dessus avec la reponse Ajax.
// En cas de formulaire, AjaxSqueeze retourne False pour empecher son envoi
// Le cas True ne devrait pas se produire car le cookie spip_accepte_ajax
// a du anticiper la situation.
// Toutefois il y toujours un coup de retard dans la pose d'un cookie:
// eviter de se loger avec redirection vers un telle page

function AjaxSqueeze(trig, id, f)
{
	// position du demandeur dans le DOM (le donner direct serait mieux)
	id = document.getElementById(id);
	if (!id) {return true;}

	// animation immediate pour faire patienter (vivement jquery !)
	if (typeof ajax_image_searching != 'undefined') {
		g = document.createElement('div');
		g.innerHTML = ajax_image_searching;
		id.insertBefore(g, id.firstChild);
	}
	return  !AjaxSqueezeNode(trig, id, f);
}

// La fonction qui fait vraiment le travail decrit ci-dessus.
// Son premier argument est deja le noeud du DOM
// et son resultat booleen est inverse ce qui lui permet de retourner 
// le gestionnaire Ajax comme valeur non fausse

function AjaxSqueezeNode(trig, noeud, f)
{
	var i, s, g, callback;
	var u = '';
	
	// retour std si pas precise: affecter ce noeud avec ce retour
	if (!f) callback = function(r) { noeud.innerHTML = r; verifForm(noeud); triggerAjaxLoad(noeud);}
	else callback = function(r) { f(r,noeud); verifForm(noeud); triggerAjaxLoad(noeud);}
	
	if (typeof(trig) == 'string') {
		i = trig.split('?');
		trig = i[0] +'?var_ajaxcharset=utf-8&' + i[1];
		return ajah('GET', trig, null, callback);
	}

	for (i=0;i < trig.elements.length;i++) {
		n = trig.elements[i];
		s = ((n.type != 'checkbox')&&(n.type != 'radio')) ? n.name : n.checked;
		if (s) {
			u += n.name+"="+ encodeURIComponent(n.value) + '&';
		}
	}
	u += 'var_ajaxcharset=utf-8'; // encodeURIComponent

	s = trig.getAttribute('action');
	if (typeof(s)!='string') // pour IE qui a foire la ligne precedente
		s = trig.attributes.action.value;
	return ajah('POST', // ou 'GET'
		     s ,     // s + '?'+ u,
		     u,      // null,
		     callback);
}


// Comme AjaxSqueeze, 
// mais avec un cache sur le noeud et un cache sur la reponse
// et une memorisation des greffes en attente afin de les abandonner
// (utile surtout a la frappe interactive au clavier)
// De plus, la fonction optionnelle n'a pas besoin de greffer la reponse.

function charger_id_url(myUrl, myField, jjscript) 
{
	var Field = findObj_forcer(myField);
	if (!Field) return true;

	if (!myUrl) {
		retour_id_url('', Field, jjscript);
		return true; // url vide, c'est un self complet
	} else {
		return charger_node_url(myUrl, Field, jjscript, findObj_forcer('img_' + myField));
	}
}

// La suite

function charger_node_url(myUrl, Field, jjscript, img) 
{
	// disponible en cache ?
	if (url_chargee[myUrl]) {
			retour_id_url(url_chargee[myUrl], Field, jjscript);
			triggerAjaxLoad(Field);
			return false; 
	  } else {
		if (img) img.style.visibility = "visible";
		if (xhr_actifs[Field]) { xhr_actifs[Field].abort(); }
		xhr_actifs[Field] = AjaxSqueezeNode(myUrl,
				'',
				function (r) {
					xhr_actifs[Field] = undefined;
					if (img) img.style.visibility = "hidden";
					url_chargee[myUrl] = r;
					retour_id_url(r, Field, jjscript);
								   });
		return !xhr_actifs[Field];
	}
}

function retour_id_url(r, Field, jjscript)
{
	Field.innerHTML = r;
	Field.style.visibility = "visible";
	Field.style.display = "block";
	if (jjscript) jjscript();
}

function ajax_double(id, id2, r)
{
	noeud = document.getElementById(id);  
	noeud.innerHTML = r;
	noeud = document.getElementById(id2);
	noeud.style.visibility = "visible";
}

function charger_node_url_si_vide(url, noeud, gifanime, jjscript) {

	if  (noeud.style.display !='none') {
		noeud.style.display='none';}
	else {if (noeud.innerHTML != "") {
		noeud.style.visibility = "visible";
		noeud.style.display = "block";
	} else {
		charger_node_url(url, noeud,'',gifanime);
	      }
	}
}

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

