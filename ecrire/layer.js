var vis = new Array();


	var memo_obj = new Array();

	function findObj(n) { 
		var p,i,x;

		// Voir si on n'a pas deja memoriser cet element		
		if (memo_obj[n]) {
			return memo_obj[n];
		}
		
		d = document; 
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
		memo_obj[n] = x;
		
		return x;
	}
	
	function hide_obj(obj) {
		element = findObj(obj);
		if(element) {
			if (element.style.visibility != "hidden") element.style.visibility = "hidden";
		}
	}

function swap_couche(couche, rtl, dir, no_swap) {
	triangle = findObj('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (vis[couche] == 'hide'){
		if (!no_swap && triangle) triangle.src = dir + 'deplierbas.gif';
		layer.style.display = 'block';
		vis[couche] = 'show';
	} else {
		if (!no_swap && triangle) triangle.src = dir + 'deplierhaut' + rtl + '.gif';
		layer.style.display = 'none';
		vis[couche] = 'hide';
	}
}
function ouvrir_couche(couche, rtl,dir) {
	triangle = findObj('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (triangle) triangle.src = dir + 'deplierbas.gif';
	layer.style.display = 'block';
	vis[couche] = 'show';
}
function fermer_couche(couche, rtl, dir) {
	triangle = findObj('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (triangle) triangle.src = dir + 'deplierhaut' + rtl + '.gif';
	layer.style.display = 'none';
	vis[couche] = 'hide';
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

function acceder_couche(couches, n, dir, icone, texte, sens) {
	  javasc = ''
	  for (j=0; j<couches.length; j+=1)
	  	javasc += 'swap_couche(' + couches[j][0] + ", '" + sens + "','" + dir + "', " + couches[j][1] + ");";
	  
	document.write('<a class="triangle_block" href="javascript:' +
	    javasc +
	    '"><img name="triangle' + n + '" src="' +
	    dir + icone + '" alt="" title="' +
	    texte +
	    '" width="10" height="10" border="0"></a>');
}
