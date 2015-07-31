var memo_obj = new Array();
var url_chargee = new Array();
var xhr_actifs = {};

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
		jQuery(element).css("visibility","hidden");
	}
}

function toggleBouton(bouton,cible){
	//alert(jQuery(bouton));
	if (jQuery(bouton).is('.deplie')){
		jQuery(bouton).addClass('replie').removeClass('deplie');
		jQuery(cible).addClass('replie').removeClass('deplie');
	}
	else if (jQuery(bouton).is('.replie')){
		jQuery(bouton).addClass('deplie').removeClass('replie');
		jQuery(cible).addClass('deplie').removeClass('replie');
	}
}

function swap_couche(couche, rtl, dir, no_swap) {
	var layer;
	var triangle = document.getElementById('triangle' + couche);
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
	var triangle = document.getElementById('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (triangle) triangle.src = dir + 'deplierbas.gif';
	layer.style.display = 'block';
}
function fermer_couche(couche, rtl, dir) {
	var layer;
	var triangle = document.getElementById('triangle' + couche);
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

function aff_selection (arg, idom, url, event)
{

	noeud = findObj_forcer(idom);
	if (noeud) {
		noeud.style.display = "none";
		charger_node_url(url+arg, noeud, '','',event);
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
	jQuery(t).trigger('change'); // declencher le onchange
	t=findObj_forcer(idom);
	t.style.display='none';
}

function aff_selection_provisoire(id, racine, url, col, sens,informer,event)
{
    charger_id_url(url.href,
		   racine + '_col_' + (col+1),
		   function() {
		     slide_horizontal(racine + 'principal', ((col-1)*150), sens);
		     aff_selection (id, racine + "_selection", informer);
		   },
		   event);
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


// Recupere tous les formulaires de la page
// (ou du fragment qu'on vient de recharger en ajax)
// et leur applique les comportements js souhaites
// ici :
// * retailler les input
// * utiliser ctrl-s, F8 etc comme touches de sauvegarde
function verifForm(racine) {
	if(!jQuery.browser.mozilla) return;
	jQuery("input.forml,input.formo,textarea.forml,textarea.formo", racine||document)
	.each(function() {
		var jField = jQuery(this);
		var w = jField.css('width');
		if (!w || w == '100%') {
			jField.css('width','95%');
		} else {
			w = parseInt(w) -
			(parseInt(jField.css("borderLeftWidth")) +
				parseInt(jField.css("borderRightWidth")) +
				parseInt(jField.css("paddingLeft")) +
				parseInt(jField.css("paddingRight")
			));
			jField.width(w+'px');
		}
	});

	// Clavier pour sauver (cf. crayons)
	jQuery('form', racine||document)
	.keypress(function(e){
		if (
		(e.ctrlKey && (
			/* ctrl-s ou ctrl-maj-S, firefox */
			((e.charCode||e.keyCode) == 115) || ((e.charCode||e.keyCode) == 83))
			/* ctrl-s, safari */
			|| (e.charCode==19 && e.keyCode==19)
		) || (!e.charCode && e.keyCode == 119 /* F8, windows */)
		) {
			jQuery(this)
			.submit();
			return false;
		}
	});
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

function AjaxSqueeze(trig, id, callback, event)
{
	var target = jQuery('#'+id);

	// position du demandeur dans le DOM (le donner direct serait mieux)
	if (!target.size()) {return true;}

	// animation immediate pour faire patienter
	if (typeof ajax_image_searching != 'undefined') {
		target.prepend(ajax_image_searching);
	}
	return !AjaxSqueezeNode(trig, target, callback, event);
}

// La fonction qui fait vraiment le travail decrit ci-dessus.
// Son premier argument est deja le noeud du DOM
// et son resultat booleen est inverse ce qui lui permet de retourner 
// le gestionnaire Ajax comme valeur non fausse

function AjaxSqueezeNode(trig, target, f, event)
{
	var i, callback;

	// retour std si pas precise: affecter ce noeud avec ce retour
	if (!f) {
    callback = function() { verifForm(this);}
  }
	else {
    callback = function(res,status) { f.apply(this,[res,status]); verifForm(this);}
  }

	valid = false;
	if (typeof(window['_OUTILS_DEVELOPPEURS']) != 'undefined'){
		if (!(navigator.userAgent.toLowerCase().indexOf("firefox/1.0")))
			valid = (typeof event != 'object') ? false : ((event.altKey || event.metaKey) == true);
	}

	if (typeof(trig) == 'string') {
		i = trig.split('?');
		trig = i[0] +'?var_ajaxcharset=utf-8&' + i[1];
		// laisser le choix de la touche enfoncee au moment du clic
		// car beaucoup de systemes en prenne une a leur usage
		if  (valid) {
		   window.open(trig+'&transformer_xml=valider_xml');
		}
		res = jQuery.ajax({"url":trig,
	       "complete":function(r,s){AjaxRet(r,s,target, callback)}});
		return res;
		
	}
	
	if(valid) {
		//open a blank window
		var doc = window.open("","valider").document;
		//create a document to enable receiving the result of the ajax post
		doc.open();
		doc.close();
		//store the searching image to be able to remove it after the post completes 
		var searching_img = $(">:first",target);
		//set the element receiving the ajax post
		target = doc.body;
	}
	
	jQuery(trig).ajaxSubmit({"target":target,
				    "success":function(res,status){
		if(valid) searching_img.remove();
		if(status=='error') return this.html('Erreur HTTP');
		callback.apply(this,[res,status]);
	},
			"beforeSubmit":function (vars){
			 vars.push({"name":"var_ajaxcharset","value":"utf-8"});
			 if (valid)
				vars.push({"name":"transformer_xml","value":"valider_xml"});
			 return true;
				  }});
	return true; 
}

// Les Submit avec attribut name ne sont pas transmis par JQuery
// Cette fonction clone le bouton de soumission en hidden
// Voir l'utilisation dans ajax_action_post dans inc/actions

function AjaxNamedSubmit(input)
{
	x=document.createElement('input');
	x.setAttribute('type', 'hidden');
	x.setAttribute('name', input.name);
	x.setAttribute('value', input.value)
	input.parentNode.appendChild(x);
	return true;
}

function AjaxRet(res,status, target, callback)
{
	if (res.aborted) return;
	if (status=='error') return jQuery(target).html('HTTP Error');

	// Inject the HTML into all the matched elements
	jQuery(target).html(res.responseText)
	  // Execute all the scripts inside of the newly-injected HTML
	  .evalScripts()
	  // Execute callback
	  .each( callback, [res.responseText, status] );
	//callback(res,status);
}


// Comme AjaxSqueeze, 
// mais avec un cache sur le noeud et un cache sur la reponse
// et une memorisation des greffes en attente afin de les abandonner
// (utile surtout a la frappe interactive au clavier)
// De plus, la fonction optionnelle n'a pas besoin de greffer la reponse.

function charger_id_url(myUrl, myField, jjscript, event) 
{
	var Field = findObj_forcer(myField);
	if (!Field) return true;

	if (!myUrl) {
		jQuery(Field).empty();
		retour_id_url(Field, jjscript);
		return true; // url vide, c'est un self complet
	} else  return charger_node_url(myUrl, Field, jjscript, findObj_forcer('img_' + myField), event);
}

// La suite

function charger_node_url(myUrl, Field, jjscript, img, event) 
{
	// disponible en cache ?
	if (url_chargee[myUrl]) {
			var el = jQuery(Field).html(url_chargee[myUrl])[0];
			retour_id_url(el, jjscript);
			triggerAjaxLoad(el);
			return false; 
	  } else {
		if (img) img.style.visibility = "visible";
		if (xhr_actifs[Field]) { xhr_actifs[Field].aborted = true;xhr_actifs[Field].abort(); }
		xhr_actifs[Field] = AjaxSqueezeNode(myUrl,
				Field,
				function (r) {
					xhr_actifs[Field] = undefined;
					if (img) img.style.visibility = "hidden";
					url_chargee[myUrl] = r;
					retour_id_url(Field, jjscript);
						    },
				event);
		return false;
	}
}

function retour_id_url(Field, jjscript)
{
	jQuery(Field).css({'visibility':'visible','display':'block'});
	if (jjscript) jjscript();
}

function charger_node_url_si_vide(url, noeud, gifanime, jjscript,event) {

	if  (noeud.style.display !='none') {
		noeud.style.display='none';}
	else {if (noeud.innerHTML != "") {
		noeud.style.visibility = "visible";
		noeud.style.display = "block";
	} else {
		charger_node_url(url, noeud,'',gifanime,event);
	      }
	}
  return false;
}

function charger_id_url_si_vide (myUrl, myField, jjscript, event) {
	var Field = findObj_forcer(myField); // selects the given element
	if (!Field) return;

	if (Field.innerHTML == "") {
		charger_id_url(myUrl, myField, jjscript, event) 
	}
	else {
		Field.style.visibility = "visible";
		Field.style.display = "block";
	}
}

